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
    public function __construct(private readonly array $fileImportMap)
    {
        $this->rawValueExtractor = GeneralUtility::makeInstance(RawValueExtractor::class);
    }

    /**
     * Determines if file processing is required based on the provided import data.
     * If no import data is provided, the method will return true if the import configuration contains any file import fields.
     *
     * @param ?array $importData An array containing the data to be imported.
     * @return bool Returns true if file processing is required, otherwise false.
     */
    public function requiresFileProcessing(?array $importData): bool
    {
        foreach ($this->fileImportMap as $fieldMap) {
            if (!empty($importData[$fieldMap->getSourceField()]) || $fieldMap->get('force_processing')) {
                return true;
            }
            $fileModifiedAt = $this->getFileModifiedAtValue($importData, $fieldMap);
            if (!empty($fileModifiedAt)) {
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
        $targetRecordId = $importRecordModel->getTargetRecordId();
        if (!$targetRecordId) {
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
            $fileName = $this->getFileName($importRecordModel, $fieldMap);
            $importFileModel = new ImportFileMappingModel(
                $importRecordModel->getTargetTable(),
                $importRecordModel->getSourceRecordIdentifier(),
                $fieldMap->getTargetField(),
                $targetRecordId,
                $importRecordModel->getPid(),
                $fileModifiedAt,
                $fileName,
                trim((string)$fileUri),
                $importRecordModel->getLanguageUid(),
            );
            $this->addImportFileMetaData($importFileModel, $importRecordModel, $fieldMap);
            $mapping[] = $importFileModel;
        }
        return $mapping;
    }

    protected function addImportFileMetaData(ImportFileMappingModel $importFileModel, ImportRecordModel $importRecordModel, ImportFieldConfigurationModel $fieldMap): void
    {
        $metaDataImportFields = $fieldMap->get('file_meta_data_fields');
        if (empty($metaDataImportFields)) {
            $metaDataImportFields = [];
        }
        $alternativeImportField = $fieldMap->get('alt_text_source_field');
        if (!empty($alternativeImportField)) {
            $metaDataImportFields['alternative'] = $alternativeImportField;
        }
        if (empty($metaDataImportFields)) {
            return;
        }
        $importData = $importRecordModel->getImportData();
        foreach ($metaDataImportFields as $metaKey => $importField) {
            try {
                $rawValue = $this->rawValueExtractor->getRawValueForMapEntry($importData, $fieldMap, $importField);
                if (!empty($rawValue) && is_scalar($rawValue)) {
                    $importFileModel->addMetaData($metaKey, (string)$rawValue);
                }
            } catch (\Exception) {
            }
        }
    }

    /**
     * Generates the file name based on the provided import record and field mapping configuration.
     *
     * @param ImportRecordModel $importRecordModel The import record model containing the target table and associated data.
     * @param ImportFieldConfigurationModel $fieldMap The configuration model that defines how to map and optionally extract the name from import data.
     * @return string The generated file name as a string.
     */
    protected function getFileName(ImportRecordModel $importRecordModel, ImportFieldConfigurationModel $fieldMap): string
    {
        $targetField = $fieldMap->getTargetField();
        $fileName = $importRecordModel->getTargetTable() . '-' . $targetField;
        if ($nameImportField = $fieldMap->get('name_import_field')) {
            $importData = $importRecordModel->getImportData();
            try {
                $rawValue = $this->rawValueExtractor->getRawValueForMapEntry($importData, $fieldMap, $nameImportField);
                if (!empty($rawValue) && is_scalar($rawValue)) {
                    $lastDotPos = strrpos($rawValue, '.');
                    $fileName = $lastDotPos > 0 ? substr($rawValue, 0, $lastDotPos) : $rawValue;
                }
            } catch (\Exception) {
            }
        }
        return $fileName;
    }

    /**
     * Retrieves the file modified timestamp value from the provided import data using the specified field mapping configuration.
     *
     * @param array<string, mixed> $importData The import data, where keys represent identifiers and values represent the associated data.
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

    public function getBaseUri(): ?string
    {
        return $this->baseUri;
    }

    public function setBaseUri(?string $baseUri): void
    {
        $this->baseUri = $baseUri;
    }
}
