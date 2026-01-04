<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\DataTransformer;

use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy\NormalizerStrategyInterface;
use BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy\NormalizerStrategyRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * An abstract class that provides a base for data transformers,
 * offering functionality for processing and formatting import data records.
 */
abstract class AbstractDataTransformer implements ImportDataTransformerInterface
{
    /**
     * @var array<string, string>
     */
    private array $existingIdentifiers = [];
    private readonly string $table;
    private int $priority;
    /**
     * @var RawDataToTcaNormalizer
     */
    private RawDataToTcaNormalizer $rawDataConverter;
    /**
     * Data collection normalizers by field name
     * @var array<string, NormalizerStrategyInterface>
     */
    private array $fieldsWithDataCollectionNormalizer = [];
    private ?FileDataTransformerHelper $fileDataTransformerHelper = null;

    public function __construct(protected ImportTableConfigurationModel $importConfiguration)
    {
        $this->table = $importConfiguration->getTableName();
        $this->priority = $this->importConfiguration->getPriority();
        $this->rawDataConverter = GeneralUtility::makeInstance(RawDataToTcaNormalizer::class, $this->importConfiguration);
        foreach ($this->importConfiguration->getImportFieldMap() as $mapEntry) {
            if ($normalizerKey = $mapEntry->getDataCollectionNormalizer()) {
                $normalizerStrategy = NormalizerStrategyRegistry::getNormalizerForImportField($normalizerKey, $mapEntry);
                if ($normalizerStrategy) {
                    $this->fieldsWithDataCollectionNormalizer[$mapEntry->getSourceField()] = $normalizerStrategy;
                }
            }
        }
        $fileImportMap = $this->importConfiguration->getImportFieldMap('files');
        if (!empty($fileImportMap)) {
            $this->fileDataTransformerHelper = new FileDataTransformerHelper($fileImportMap);
        }
    }

    /**
     * Retrieves the converter responsible for normalizing raw data to TCA format.
     *
     * @return RawDataToTcaNormalizer The instance of the raw data to TCA normalizer.
     */
    final public function getRawDataConverter(): RawDataToTcaNormalizer
    {
        return $this->rawDataConverter;
    }

    /**
     * Returns a list of import keys that need to be imported for the current source type. The value of each array
     * entry indicates if the child type is a record list (true) or a single record
     * @return ?array<string, array{identifier: string|string[], isListType: bool}>
     */
    public function getChildRecordTypesForSourceType(): ?array
    {
        return $this->importConfiguration->get('childRecordTypes');
    }

    final public function getTable(): string
    {
        return $this->table;
    }

    final public function getImportConfiguration(): ImportTableConfigurationModel
    {
        return $this->importConfiguration;
    }

    public static function getApiField(): ?string
    {
        return null;
    }

    /**
     * Extracts a record from the import data based on a specific field.
     *
     * @param array $importData The data array from which the record will be extracted. Passed by reference.
     * @param string|string[]|null $identifier Optional identifier for the value to be extracted
     * @param bool $removeFromData Optional, whether to remove the extracted record from the data. Defaults to false.
     * @return array|null Returns the extracted record as an array or null if not found.
     */
    public function extractRecordFromImportData(array &$importData, array|string|null $identifier = null, bool $removeFromData = false): ?array
    {
        if ($importField = $this->importConfiguration->getImportField()) {
            return $this->extractValueFromImportData($importData, $importField, $removeFromData);
        }
        return null;
    }

    /**
     * Extracts a list of records from the import data using a specified list import field.
     *
     * @param array $importData The import data from which the records will be extracted. This array may be modified if $removeFromData is true.
     * @param string|string[]|null $identifier Optional identifier for the value to be extracted
     * @param bool $removeFromData Whether to remove the extracted data from the original import data. Defaults to false.
     *
     * @return array The extracted list of records or an empty array if the list import field is not set or no records are found.
     */
    public function extractRecordListFromImportData(array &$importData, array|string|null $identifier = null, bool $removeFromData = false): array
    {
        if (!$identifier) {
            $identifier = $this->importConfiguration->get('listImportField');
        }
        if ($identifier) {
            return $this->extractValueFromImportData($importData, $identifier, $removeFromData) ?? [];
        }
        return [];
    }

    /**
     * Extracts a value from the import data for a specified field.
     *
     * @param array<string, mixed> $importData The array of import data, passed by reference, which may be modified if $removeFromData is true.
     * @param string $field The key of the field to be extracted from the import data.
     * @param bool $removeFromData Optional. Whether to remove the extracted value from the import data. Default is false.
     * @return array|null The extracted value as an array if the field exists, or null otherwise.
     */
    protected function extractValueFromImportData(array &$importData, string $field, bool $removeFromData = false): ?array
    {
        if (str_contains($field, '.')) {
            $rootField = substr($field, 0, strpos($field, '.'));
            $record = $this->extractValueFromImportData($importData, $rootField, $removeFromData);
            if ($record !== null) {
                $restField = substr($field, strpos($field, '.') + 1);
                return $this->extractValueFromImportData($record, $restField, $removeFromData);
            }
            return null;
        }
        $record = null;
        if (isset($importData[$field])) {
            $record = $importData[$field];
            if ($removeFromData) {
                unset($importData[$field]);
            }
        }
        return $record;
    }

    /**
     * Registers a record identifier if it does not already exist in the collection.
     *
     * @param string $identifier The unique record identifier to be registered.
     * @return bool Returns true if the record was successfully registered, false if it already exists.
     */
    protected function registerRecordIfNotExists(string $identifier, $languageId): bool
    {
        $identifierWithLangId = $identifier . '-' . $languageId;
        if (!in_array($identifierWithLangId, $this->existingIdentifiers, false)) {
            $this->existingIdentifiers[] = $identifierWithLangId;
            return true;
        }
        return false;
    }

    /**
     * Sets the registered identifiers for the current instance.
     *
     * @param string[] $identifiers An array of identifiers to be registered.
     */
    public function setRegisteredIdentifiers(array $identifiers): void
    {
        $this->existingIdentifiers = $identifiers;
    }

    /**
     * Pre-processes the raw data before further processing.
     *
     * @param array $importData The raw data to be pre-processed.
     * @return ?array The processed data. Returns null if the data array is invalid
     */
    protected function preProcessRawData(array $importData): ?array
    {
        if (!empty($this->fieldsWithDataCollectionNormalizer)) {
            foreach ($this->fieldsWithDataCollectionNormalizer as $field => $normalizerStrategy) {
                if (!array_key_exists($field, $importData)) {
                    throw new \InvalidArgumentException(sprintf('Field "%s" is missing in the import data for table %s', $field, $this->getTable()));
                }
                $importData[$field] = $normalizerStrategy->normalize($importData[$field]);
            }
        }
        return $importData;
    }

    /**
     * Initializes a new import record based on the provided data.
     *
     * @param array $importData An associative array containing the data to initialize the import record.
     * @return ImportRecordModel|null The initialized import record model, or null if initialization fails.
     */
    public function initializeImportRecord(array $importData): ?ImportRecordModel
    {
        $rawData = $this->preProcessRawData($importData);
        if ($rawData === null) {
            return null;
        }
        $identifier = $this->rawDataConverter->getImportIdentifier($rawData);
        if (empty($identifier)) {
            return null;
        }
        $languageId = 0;
        if ($languageFieldMapping = $this->importConfiguration->getLanguageFieldMapping()) {
            $languageId = (int)$this->rawDataConverter->getRawValueForMapEntry($rawData, $languageFieldMapping, true);
        }
        // We can't import a record without an identifier and ensure that the record is only added once
        if (!$this->registerRecordIfNotExists((string)$identifier, $languageId)) {
            return null;
        }
        $typedIdentifier = $this->importConfiguration->hasIntegerIdentifiers() ? (int)$identifier : $identifier;
        $model = new ImportRecordModel(
            $rawData,
            $this->getTable(),
            $typedIdentifier
        );
        if ($this instanceof HasLastUpdateFieldDataTransformerInterface) {
            $targetField = $this->getLastUpdateTargetTcaField();
            $fieldImportConfiguration = $this->importConfiguration->getImportConfigurationForField($targetField);
            if ($fieldImportConfiguration !== null) {
                $lastUpdated = (int)$this->rawDataConverter->getRawValueForMapEntry($rawData, $fieldImportConfiguration, true);
                if ($lastUpdated) {
                    $model->setLastUpdated($lastUpdated);
                }
            }
        }
        $model->setLanguageUid($languageId);
        return $model;
    }

    /**
     * Updates the given import record using processed raw import data.
     *
     * @param ImportRecordModel $model The import record model to be updated.
     * @param array<string, mixed> $importData The raw import data to process and apply to the model.
     * @param bool $overwriteHash Determines whether to overwrite the existing data hash. Defaults to true.
     */
    public function updateImportRecord(ImportRecordModel $model, array $importData, bool $overwriteHash = false): void
    {
        $rawData = $this->preProcessRawData($importData);
        if ($rawData !== null) {
            // Don't update the import hash after the data are loaded fully
            // We want to skip loading the details if the limited data array has not changed
            $model->updateImportData($rawData, $overwriteHash);
        }
    }

    /**
     * Post-processing after the import record has been added to the list.
     * This is called after the data have been fully loaded
     *
     * @param ImportRecordModel $model The import record model to be updated.
     */
    public function postProcessAfterModelAdded(ImportRecordModel $model): void
    {
        // No post-processing required
    }

    /**
     * Post-processes the converted data by augmenting it with additional information from the model.
     *
     * @param ImportRecordModel $model The model containing additional data to be injected into the converted data.
     * @param array<string, mixed> $data The array of converted data to be post-processed.
     * @return array<string, mixed> The updated data array after applying the post-processing steps.
     */
    public function postProcessConvertedData(ImportRecordModel $model, array $data): array
    {
        return $data;
    }

    /**
     * Retrieves information about the cropped field.
     *
     * @return array<string, array{targetField: string, maxLength: int, allowedLength: int, croppedValueCount: int, valueBeforeCrop: string}> An array containing details related to the cropped field.
     */
    public function getCroppedFieldInfo(): array
    {
        return $this->rawDataConverter->getCroppedFieldInfo();
    }

    /**
     * Returns the import priority for this data type and record
     *
     * @param ?array $importData
     * @return int
     */
    public function getPriority(?array $importData): int
    {
        if (empty($importData)) {
            return $this->priority;
        }
        $offset = 0;
        // increase priority if internal references exist, but the import value is empty (record has no references)
        if (!empty($internalDependencies = $this->importConfiguration->getInternalDependencies())) {
            foreach ($internalDependencies as $importField) {
                if (array_key_exists($importField, $importData) && empty($importData[$importField])) {
                    $offset += 10;
                }
            }
        }
        return $this->priority + $offset;
    }

    /**
     * Sets the import priority for this data type
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    final public function hasFileTransformations(): bool
    {
        return $this->fileDataTransformerHelper !== null;
    }

    final public function getFileDataTransformerHelper(): FileDataTransformerHelper
    {
        if ($this->fileDataTransformerHelper === null) {
            throw new \RuntimeException('File data transformer helper is not initialized');
        }
        return $this->fileDataTransformerHelper;
    }
}
