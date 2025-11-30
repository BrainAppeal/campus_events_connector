<?php

declare(strict_types=1);

/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2025 Brain Appeal GmbH
 *
 * @copyright 2025 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Importer;

use BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALFactory;
use BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALInterface;
use BrainAppeal\CampusEventsConnector\Utility\DataParser;
use BrainAppeal\CampusEventsConnector\Utility\TableFieldMapHelper;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

class DataHandlerProcessor implements SingletonInterface
{
    private ?DBALInterface $dbal = null;

    private bool $debug = false;

    /**
     * @param array<string, array<int, ImportMappingModel>> $groupedImportMappingModels
     * @param int $pid The target page id
     * @param array<int, array{language_code: string, pid: int, l10n_parent: int, sys_language_uid: int, language_aspect: LanguageAspect, uid: int}> $languageOverlays
     * @param bool $debug
     */
    public function processImportModels(array $groupedImportMappingModels, int $pid, array $languageOverlays, bool $debug = false): void
    {
        if (empty($groupedImportMappingModels)) {
            return;
        }
        $this->debug = $debug;
        // The sort order in the array is important here, as the order of the tables in the array determines the order of the processing
        foreach (ExtendedApiConnector::IMPORT_TYPE_TABLE_MAP as $importType => $table) {
            $importModelsForType = $groupedImportMappingModels[$importType] ?? [];
            if (empty($importModelsForType)) {
                continue;
            }
            $parser = new DataParser();
            $formatFunctions = DataParser::getFormatFunctionsForTable($table);
            foreach ($importModelsForType as $importMappingModel) {
                $this->updateImportItemWithDataHandler($importMappingModel, $pid, $languageOverlays, $parser, $formatFunctions);
            }
        }
    }

    /**
     * @return DBALInterface
     */
    private function getDBAL(): DBALInterface
    {
        if ($this->dbal === null) {
            $this->dbal = DBALFactory::getInstance();
        }

        return $this->dbal;
    }

    /**
     * Updates an import item using the provided data handler, ensuring the record is correctly processed
     * and updated or created as necessary.
     *
     * @param ImportMappingModel $importMappingModel The model representing the mapping data for the import.
     * @param int $pid The parent ID under which the record should be processed or created.
     * @param array<int, array{language_code: string, pid: int, l10n_parent: int, sys_language_uid: int, language_aspect: LanguageAspect, uid: int}> $languageOverlays An array of language overlays used for multiple language processing.
     * @param DataParser $parser The parser used to apply the defined format functions.
     * @param array<string, callable|null> $formatFunctions An array mapping target fields to their respective format functions.
     */
    protected function updateImportItemWithDataHandler(ImportMappingModel $importMappingModel, int $pid, array $languageOverlays, DataParser $parser, array $formatFunctions): void
    {
        $rawData = $importMappingModel->getImportData();
        if (empty($rawData) || !$importMappingModel->existsInApi()) {
            return;
        }
        $table = $importMappingModel->getTable();
        $data = $this->transformForDataHandler($rawData, $importMappingModel, $parser, $formatFunctions);
        if (empty($data)) {
            return;
        }
        $importSource = $importMappingModel->getImportSource();
        $importId = $importMappingModel->getImportId();
        $record = $this->getDBAL()->findRowByImport($table, $importSource, $importId, $pid);
        if (empty($record)) {
            $theNewID = StringUtility::getUniqueId('NEW');
            $importData = array_merge($data, [
                'pid' => $pid,
                'ce_import_source' => $importSource,
                'ce_import_id' => $importId,
                'ce_imported_at' => time(),
            ]);

            $data = [
                $table => [
                    $theNewID => $importData,
                ],
            ];
            /** @var DataHandler $dataHandler */
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start($data, []);
            $dataHandler->enableLogging = false;
            $dataHandler->process_datamap();
            if (isset($dataHandler->substNEWwithIDs[$theNewID])) {
                $recordUid = $dataHandler->substNEWwithIDs[$theNewID];
                $record = BackendUtility::getRecord($table, $recordUid);
            }
            if (empty($record)) {
                return;
            }
        } else {
            $this->updateChangedFieldsWithDataHandler($table, (int)$record['uid'], $record, $data);
        }
        $rowTranslations = $rawData['translations'] ?? [];
        if (!empty($rowTranslations) && !empty($languageOverlays)) {
            $this->processTranslations($record, $rowTranslations, $importMappingModel, $parser, $formatFunctions, $languageOverlays);
        }
    }

    /**
     * Processes translations for a given record, handling language overlays
     * and updating translations using a data handler.
     *
     * @param array<string, mixed> $record The primary record to process.
     * @param array $rowTranslations An array of translations indexed by language codes.
     * @param ImportMappingModel $importMappingModel The import mapping model containing table mappings and configurations.
     * @param DataParser $parser A parser to process and transform data.
     * @param array $formatFunctions Functions to format the data during transformation.
     * @param array $languageOverlays An array of language overlay configurations including language code, sys_language_uid, and aspect information.
     */
    protected function processTranslations(array $record, array $rowTranslations, ImportMappingModel $importMappingModel, DataParser $parser, array $formatFunctions, array $languageOverlays): void
    {
        $table = $importMappingModel->getTable();
        $tableControl = $GLOBALS['TCA'][$table]['ctrl'] ?? [];
        $languageField = $tableControl['languageField'] ?? '';
        if (empty($languageField)) {
            return;
        }
        $incomingLanguageId = (int)($record[$languageField] ?? 0);

        // Return record for ALL languages untouched
        if ($incomingLanguageId === -1) {
            return;
        }
        $context = GeneralUtility::makeInstance(Context::class);
        /** @var PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
        foreach ($languageOverlays as $language) {
            $languageCode = $language['language_code'];
            $rawDataTranslation = $rowTranslations[$languageCode] ?? [];
            if (empty($rawDataTranslation)) {
                continue;
            }
            $sysLanguageUid = (int)$language['sys_language_uid'];
            $languageAspect = $language['language_aspect'];
            $localizedRecord = $pageRepository->getLanguageOverlay($table, $record, $languageAspect);
            if (!$localizedRecord || empty($localizedRecord['_LOCALIZED_UID'])) {
                $cmd = [];
                $cmd[$table][$record['uid']]['localize'] = $sysLanguageUid;
                $tce = GeneralUtility::makeInstance(DataHandler::class);
                $tce->start([], $cmd);
                $tce->enableLogging = false;
                $tce->process_cmdmap();
                $localizedRecord = $pageRepository->getLanguageOverlay($table, $record, $languageAspect);
            }
            if (!empty($localizedRecord['_LOCALIZED_UID'])) {
                $translatedUid = (int)$localizedRecord['_LOCALIZED_UID'];
                $translatedData = $this->transformForDataHandler($rawDataTranslation, $importMappingModel, $parser, $formatFunctions);
                $this->updateChangedFieldsWithDataHandler($table, $translatedUid, $localizedRecord, $translatedData);
            }
        }
    }

    /**
     * Transforms the raw import data of the provided model using a field map and optional format functions.
     *
     * @param array<string, mixed> $rawData
     * @param ImportMappingModel $model The model containing the raw import data to be transformed.
     * @param DataParser $parser The parser used to apply the defined format functions.
     * @param array<string, callable|null> $formatFunctions An array mapping target fields to their respective format functions.
     * @return ?array The transformed data mapped to the specified target fields.
     */
    protected function transformForDataHandler(array $rawData, ImportMappingModel $model, DataParser $parser, array $formatFunctions): ?array
    {
        if (!$model->existsInApi()) {
            return null;
        }
        $rawData = $this->preProcessImportData($rawData, $model);
        $table = $model->getTable();
        $importFieldMap = TableFieldMapHelper::getImportFieldMap($table);
        $data = [];
        foreach ($importFieldMap as $mapEntry) {
            $targetField = $mapEntry['target_field'];
            $sourceField = $mapEntry['field'];
            $rawValue = $rawData[$sourceField] ?? null;
            if (!empty($rawValue) && !empty($mapEntry['import_type']) && !empty($mapEntry['reference_type'])) {
                $val = 0;
                if (is_array($rawValue)) {
                    $refId = ExtendedApiConnector::filterId($rawValue['@id'], $mapEntry['reference_type']);
                } else {
                    $refId = ExtendedApiConnector::filterId($rawValue, $mapEntry['reference_type']);
                }
                $refTable = ExtendedApiConnector::IMPORT_TYPE_TABLE_MAP[$mapEntry['reference_type']] ?? null;
                if ($refTable && $refId) {
                    $refRecord = $this->getDBAL()->findRowByImport($refTable, $model->getImportSource(), $refId);
                    if (!empty($refRecord)) {
                        $val = (int)$refRecord['uid'];
                    } else {
                        continue;
                    }
                }
            } elseif ($formatFunction = $formatFunctions[$targetField] ?? null) {
                $val = $parser->$formatFunction($rawValue);
            } else {
                $val = $rawValue;
            }
            if (is_string($val)) {
                $val = $this->decodeEscapedUtf8($val);
            }
            if (!empty($mapEntry['length']) && !is_numeric($val) && mb_strlen((string)$val) > $mapEntry['length']) {
                $val = mb_substr($val, 0, $mapEntry['length']);
            }
            $data[$targetField] = $val;
        }
        return $data;
    }

    /**
     * Updates the changed fields of a record using the provided data handler.
     *
     * @param string $table The name of the database table where the record resides.
     * @param int $recordId The unique identifier of the record to update.
     * @param array $record The current state of the record in the system.
     * @param array $recordFromApi The updated record data retrieved from an external API.
     * @return array An array containing the fields that have been changed and their new values.
     */
    protected function updateChangedFieldsWithDataHandler(string $table, int $recordId, array $record, array $recordFromApi): array
    {
        $changedData = [];
        foreach ($recordFromApi as $field => $value) {
            if (isset($record[$field]) && ($this->debug || $record[$field] !== $value)) {
                $changedData[$field] = $value;
            }
        }
        if ($record['hidden'] ?? null) {
            $changedData['hidden'] = 0;
        }
        if ($record['starttime'] ?? null) {
            $changedData['starttime'] = 0;
        }
        if ($record['endtime'] ?? null) {
            $changedData['endtime'] = 0;
        }
        if ($record['deleted'] ?? null) {
            $cmd = [];
            $cmd[$table][$recordId]['undelete'] = 1;
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->start([], $cmd);
            $tce->enableLogging = false;
            $tce->process_cmdmap();
        }
        if (!empty($changedData)) {
            $changedData['ce_imported_at'] = time();
            /** @var DataHandler $dataHandler */
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start([
                $table => [
                    $recordId => $changedData,
                ],
            ], []);
            $dataHandler->enableLogging = false;
            $dataHandler->process_datamap();
        }
        return $changedData;
    }

    /**
     * Preprocesses the import data based on the specified import type and updates
     * the relevant fields accordingly.
     *
     * @param array<string, mixed> $rawData
     * @param ImportMappingModel $model The model containing the import data and metadata.
     * @return array<string, mixed> Returns the processed import data as an array, or null if the input data is empty.
     */
    protected function preProcessImportData(array $rawData, ImportMappingModel $model): array
    {
        $importType = $model->getImportType();
        $urls = array_key_exists('@urls', $rawData) ? $rawData['@urls'] : [];
        switch ($importType) {
            case 'EventTicketPriceVariant':
                if (empty($urls['directCheckoutUrl']) && !empty($rawData['directCheckoutUrl'])) {
                    $urls['directCheckoutUrl'] = $rawData['directCheckoutUrl'];
                }
                $rawData['directCheckoutUrl'] = $urls['directCheckoutUrl'] ?? '';
                unset($rawData['@urls']);
                break;
            case 'Event':
                if (empty($urls['eventUrl']) && !empty($model->getImportSource())) {
                    $urls['eventUrl'] = rtrim($model->getImportSource(), '/') . '/event/' . $model->getImportId();
                }
                $rawData['directRegistrationUrl'] = $urls['directRegistrationUrl'] ?? '';
                $rawData['eventUrl'] = $urls['eventUrl'] ?? '';
                $rawData['seoRobotsIndex'] = ($rawData['seoRobotsIndex'] ?? '') !== 'noindex';
                $rawData['seoRobotsFollow'] = ($rawData['seoRobotsFollow'] ?? '') !== 'nofollow';
                unset($rawData['@urls']);
                break;
        }
        return $rawData;
    }

    /**
     * Decodes a string containing escaped UTF-8 characters into its proper representation.
     *
     * @param string $value The string containing escaped UTF-8 characters to decode.
     * @return string The decoded string with UTF-8 characters properly converted.
     */
    private function decodeEscapedUtf8(string $value): string
    {
        // Decode escaped UTF-8 characters
        $decodedValue = preg_replace_callback(
            '/\\\\([0-3][0-7]{2})/',
            static function ($matches) {
                return chr(octdec($matches[1]));
            },
            $value
        );
        return $decodedValue ?: '';
    }

    /**
     * These fields are currently not imported with the data handler but still with extbase
     * @param string $importType
     * @return array
     */
    private function getExcludeFields(string $importType): array
    {
        $excludeFields = [];
        switch ($importType) {
            case 'FilterCategory':
                $excludeFields[] = 'children';
                break;
            case 'Referent':
                $excludeFields[] = 'birthdate';
                $excludeFields[] = 'iban';
                $excludeFields[] = 'bic';
                $excludeFields[] = 'bankName';
                $excludeFields[] = 'taxNumber';
                $excludeFields[] = 'name';
                break;
            case 'Event':
                $excludeFields[] = 'seller';
                $excludeFields[] = 'eventTicketPriceVariants';
                $excludeFields[] = 'notOrderableMessage';
                $excludeFields[] = 'externalOrderEmailSubject';
                $excludeFields[] = 'externalOrderEmailBody';
                $excludeFields[] = 'waitingQueue';
                $excludeFields[] = 'alternativeEvents';
                $excludeFields[] = 'locations';
                $excludeFields[] = 'viewLists';
                $excludeFields[] = 'organizers';
                $excludeFields[] = 'filterCategories';
                $excludeFields[] = 'targetGroups';
                $excludeFields[] = 'sponsors';
                $excludeFields[] = 'attachments';
                $excludeFields[] = 'images';
                $excludeFields[] = 'categories';
                $excludeFields[] = 'mandator';
                $excludeFields[] = 'eventSessions';
                $excludeFields[] = 'dynamicAttributes';
                $excludeFields[] = 'location';
                $excludeFields[] = 'contactPersons';
                $excludeFields[] = 'referents';
                $excludeFields[] = 'sessionDates';
                break;
            case 'EventSession':
                $excludeFields[] = 'sessionDates';
                break;
            case 'EventAttachment':
                $excludeFields[] = 'attachmentFile';
                break;
            case 'EventImage':
            case 'Sponsor':
                $excludeFields[] = 'imageFile';
                break;
        }
        $excludeFields[] = 'translations';
        return $excludeFields;
    }
}
