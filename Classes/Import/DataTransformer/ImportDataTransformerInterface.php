<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\DataTransformer;

use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportFileMappingModel;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;

/**
 * Interface defining the structure and behaviour for data transformation during an import process.
 */
interface ImportDataTransformerInterface
{
    /**
     * Prefix used for internal keys.
     */
    public const INTERNAL_KEY_PREFIX = '_';

    /**
     * Retrieves the name of the table associated with the current entity or context.
     *
     * @return string The name of the table.
     */
    public function getTable(): string;

    /**
     * Return the name of the identifier field used for API calls if it exists; otherwise return null
     *
     * @return ?string
     */
    public static function getApiField(): ?string;

    /**
     * Retrieves the import configuration for the current table
     * @return ImportTableConfigurationModel
     */
    public function getImportConfiguration(): ImportTableConfigurationModel;

    /**
     * Initializes a new import record based on the provided data.
     *
     * @param array $importData An associative array containing the data to initialize the import record.
     * @return ImportRecordModel|null The initialized import record model, or null if initialization fails.
     */
    public function initializeImportRecord(array $importData): ?ImportRecordModel;

    /**
     * Updates the given import record using processed raw import data.
     * This is used if import records are extended with more data during the import process.
     *
     * @param ImportRecordModel $model The import record model to be updated.
     * @param array<string, mixed> $importData The raw import data to process and apply to the model.
     * @param bool $overwriteHash Determines whether to overwrite the existing data hash. Defaults to true.
     */
    public function updateImportRecord(ImportRecordModel $model, array $importData, bool $overwriteHash = false): void;

    /**
     * Post-processing after the import record has been added to the list.
     * This is called after the data have been fully loaded
     *
     * @param ImportRecordModel $model The import record model to be updated.
     */
    public function postProcessAfterModelAdded(ImportRecordModel $model): void;

    /**
     * Extracts a record from the import data based on a specific field.
     *
     * @param array $importData The data array from which the record will be extracted. Passed by reference.
     * @param string|string[]|null $identifier Optional identifier for the value to be extracted
     * @param bool $removeFromData Optional, whether to remove the extracted record from the data. Defaults to false.
     * @return array|null Returns the extracted record as an array or null if not found.
     */
    public function extractRecordFromImportData(array &$importData, array|string|null $identifier = null, bool $removeFromData = false): ?array;

    /**
     * Extracts a list of records from the import data using a specified list import field.
     *
     * @param array $importData The import data from which the records will be extracted. This array may be modified if $removeFromData is true.
     * @param string|string[]|null $identifier Optional identifier for the value to be extracted
     * @param bool $removeFromData Whether to remove the extracted data from the original import data. Defaults to false.
     *
     * @return array The extracted list of records or an empty array if the list import field is not set or no records are found.
     */
    public function extractRecordListFromImportData(array &$importData, array|string|null $identifier = null, bool $removeFromData = false): array;

    /**
     * Sets the registered identifiers for the current instance.
     *
     * @param string[] $identifiers An array of identifiers to be registered.
     */
    public function setRegisteredIdentifiers(array $identifiers): void;

    /**
     * Returns the import priority for this data type and record
     * @param ?array $importData
     * @return int
     */
    public function getPriority(?array $importData): int;

    /**
     * Sets the import priority for this data type
     */
    public function setPriority(int $priority): void;

    /**
     * Post-processes the converted data by augmenting it with additional information from the model.
     *
     * @param ImportRecordModel $model The model containing additional data to be injected into the converted data.
     * @param array<string, mixed> $data The array of converted data to be post-processed.
     * @return array<string, mixed> The updated data array after applying the post-processing steps.
     */
    public function postProcessConvertedData(ImportRecordModel $model, array $data): array;

    /**
     * Retrieves information about the cropped field.
     *
     * @return array<string, array{targetField: string, maxLength: int, allowedLength: int, valueBeforeCrop: string}> An array containing details related to the cropped field.
     */
    public function getCroppedFieldInfo(): array;

    /**
     * Retrieves the converter responsible for normalizing raw data to TCA format.
     *
     * @return RawDataToTcaNormalizer The instance of the raw data to TCA normalizer.
     */
    public function getRawDataConverter(): RawDataToTcaNormalizer;

    /**
     * Checks if there is a configuration defined for file import.
     *
     * @return bool Returns true if a file import configuration is present, otherwise false.
     */
    public function hasFileTransformations(): bool;

    public function getFileDataTransformerHelper(): FileDataTransformerHelper;

    /**
     * Initializes the import data by preparing file models, record list, and fields.
     *
     * @param array<ImportRecordModel> $importModelList
     * @return ImportFileMappingModel[]
     */
    public function getImportFileMappingModels(array $importModelList): array;
}
