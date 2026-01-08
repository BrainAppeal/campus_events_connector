<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\DataTransformer;

use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportFieldConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportFileMappingModel;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy\NormalizerStrategyRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A helper class for transforming and processing file-related import data.
 * This class provides utilities for determining file processing requirements
 * and generating import file mappings with associated metadata and processing types.
 */
class FileDataTransformerHelper
{
    /**
     * @var RawValueExtractor
     */
    private RawValueExtractor $rawValueExtractor;

    /**
     * Optional base URI for retrieving remote files.
     * @var string|null $baseUri
     */
    private ?string $baseUri = null;

    /**
     * @param ImportFieldConfigurationModel[] $fileImportMap
     */
    public function __construct(private array $fileImportMap)
    {
        $this->rawValueExtractor = GeneralUtility::makeInstance(RawValueExtractor::class);
    }

    /**
     * Determines if file processing is required based on the provided import data.
     *  If no import data is provided, the method will return true if the import configuration contains any file import fields.
     *
     * @param ?array $importData An array containing the data to be imported.
     * @return bool Returns true if file processing is required, otherwise false.
     */
    public function requiresFileProcessing(?array $importData): bool
    {
        foreach ($this->fileImportMap as $fieldMap) {
            if (!empty($importData[$fieldMap->getSourceField()]) || !empty($importData[$fieldMap->get('timestamp_import_field')])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generates a mapping of importable file data with associated metadata and processing types.
     * This method evaluates the presence and modification timestamps of files within the import data
     * to determine the required processing action (e.g. check for deletion, update, or creation).
     *
     * @param ImportRecordModel $importRecordModel
     * @return array<ImportFileMappingModel> An array representing the mapping of file fields to their metadata and processing instructions.
     */
    public function getImportFileMapping(ImportRecordModel $importRecordModel): array
    {
        $targetRecordsId = $importRecordModel->getTargetRecordId();
        if (!$targetRecordsId) {
            return [];
        }
        $mapping = [];
        $importData = $importRecordModel->getImportData();
        foreach ($this->fileImportMap as $fieldMap) {
            $fileUri = $this->rawValueExtractor->getRawValueForMapEntry($importData, $fieldMap);
            if (!empty($fileUri) && $this->baseUri && !str_starts_with($fileUri, 'http')) {
                $fileUri = rtrim($this->baseUri, '/') . '/' . ltrim($fileUri, '/');
            }
            $fileModifiedAt = $this->getFileModifiedAtValue($importData, $fieldMap);
            $hasModifiedValue = !empty($fileModifiedAt);
            if (!$fileUri && $hasModifiedValue) {
                // If the record has no file and a last modified timestamp, we can check an existing file needs to be deleted
                $processType = ImportFileMappingModel::PROCESS_TYPE_CHECK_DELETE_LOCAL;
            } elseif ($fileUri && $hasModifiedValue) {
                // If the record has a file and a last modified timestamp, we can check if the file needs to be updated
                $processType = ImportFileMappingModel::PROCESS_TYPE_CHECK_UPDATE_LOCAL;
            } elseif ($fileUri) {
                // Otherwise we need to retrieve the file
                $processType = ImportFileMappingModel::PROCESS_TYPE_FETCH_REMOTE;
            } else {
                // This should not occur since we have the timestamp values in the import data.
                // But since we need to load the person data anyway, we might as well check all fields for this record
                // Records without any image uri or change timestamp will not be processed (see requiresFileProcessing)
                $processType = ImportFileMappingModel::PROCESS_TYPE_CHECK_DELETE_LOCAL;
            }
            $targetField = $fieldMap->getTargetField();
            $mapping[] = new ImportFileMappingModel(
                $targetField,
                $targetRecordsId,
                $importRecordModel->getPid(),
                $fileModifiedAt,
                $importRecordModel->getTargetTable() . '-' . $targetField,
                trim((string)$fileUri),
                (string)$fieldMap->get('alt_text_source_field'),
                $processType,
                $importRecordModel->getLanguageUid(),
            );
        }
        return $mapping;
    }

    /**
     * Retrieves the file modified timestamp value from the provided import data using the specified field mapping configuration.
     *
     * @param array $importData The data set from which to extract the file modified timestamp.
     * @param ImportFieldConfigurationModel $fieldMap The configuration model that defines how to map and normalize the timestamp field.
     * @return int The normalized file modified timestamp as an integer.
     */
    protected function getFileModifiedAtValue(array $importData, ImportFieldConfigurationModel $fieldMap): int
    {
        $fileModifiedAt = null;
        if ($timestampImportField = $fieldMap->get('timestamp_import_field')) {
            try {
                $fileModifiedAt = $this->rawValueExtractor->getRawValueForMapEntry($importData, $fieldMap, $timestampImportField);
            } catch (\Exception) {
                return 0;
            }
            if (!empty($fileModifiedAt) && $normalizerKey = (string)$fieldMap->get('timestamp_normalizer')) {
                $normalizer = NormalizerStrategyRegistry::getNormalizerForImportField($normalizerKey, $fieldMap);
                if ($normalizer) {
                    $fileModifiedAt = $normalizer->normalize($fileModifiedAt);
                }
            }
        }
        return (int)$fileModifiedAt;
    }

    public function setBaseUri(?string $baseUri): void
    {
        $this->baseUri = $baseUri;
    }
}
