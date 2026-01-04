<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\DataTransformer;

use BrainAppeal\CampusEventsConnector\Import\Exception\MappingException;
use BrainAppeal\CampusEventsConnector\Import\Exception\ValidationException;
use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportFieldConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportRecordModel;
use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy as NormalizerStrategy;
use BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy\NormalizerStrategyRegistry;
use BrainAppeal\CampusEventsConnector\Import\TargetResolution\ReferenceResolver;
use BrainAppeal\CampusEventsConnector\Import\Utility\SlugNormalizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * An abstract class that provides a base for data transformers,
 * offering functionality for processing and formatting import data records.
 */
class RawDataToTcaNormalizer
{
    private readonly string $table;

    /**
     * Information about cropped fields. This indicates that the database field must be changed
     * @var array<string, array{targetField: string, maxLength: int, allowedLength: int, croppedValueCount: int, valueBeforeCrop: string}>
     */
    protected array $croppedFieldInfo = [];
    /**
     * @var SlugNormalizer
     */
    private SlugNormalizer $slugNormalizer;

    /**
     * @var NormalizerStrategy\NormalizerStrategyInterface[]
     */
    private array $fieldStrategies = [];
    /**
     * @var RawValueExtractor
     */
    private RawValueExtractor $rawValueExtractor;

    public function __construct(private readonly ImportTableConfigurationModel $importConfiguration)
    {
        $this->table = $importConfiguration->getTableName();

        $this->slugNormalizer = GeneralUtility::makeInstance(SlugNormalizer::class);
        $this->rawValueExtractor = GeneralUtility::makeInstance(RawValueExtractor::class);

        $this->initializeNormalizerStrategies();
    }

    /**
     * Retrieves and returns an array of format functions for the specified table.
     *
     * @return void
     */
    private function initializeNormalizerStrategies(): void
    {
        $fieldMap = $this->importConfiguration->getImportFieldMap();
        foreach ($fieldMap as $targetField => $mapEntry) {
            $normalizerKey = $mapEntry->getDataTransformationNormalizer();
            if (!$normalizerKey) {
                continue;
            }
            $normalizerStrategy = NormalizerStrategyRegistry::getNormalizerForImportField($normalizerKey, $mapEntry);
            if ($normalizerStrategy) {
                $this->fieldStrategies[$targetField] = $normalizerStrategy;
            }
        }
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
     * Retrieves the import identifier from the provided import data array.
     *
     * @param array $importData The array containing the import data.
     * @return string|int|null The import identifier if found, or null if not found.
     */
    public function getImportIdentifier(array $importData): int|string|null
    {
        $fieldMap = $this->importConfiguration->getImportFieldMap();
        $mapIdField = $this->importConfiguration->getSourceIdentifierField();
        $mapEntry = $fieldMap[$mapIdField];
        $identifier = $this->getRawValueForMapEntry($importData, $mapEntry, true);
        if (empty($identifier)) {
            return null;
        }
        if ($this->importConfiguration->hasIntegerIdentifiers()) {
            return (int)$identifier;
        }
        if (strlen($identifier) > 64) {
            $identifier = substr($identifier, 0, 48) . '_' . substr(md5($identifier), -15);
        }
        return $identifier;
    }

    /**
     * Transforms the raw import data of the provided model using a field map and optional format functions.
     *
     * @param ImportRecordModel $model The import record model containing the raw data to be transformed.
     * @param ReferenceResolver $referenceResolver The import target record mapping used to resolve references.
     * @return array The transformed data mapped to the specified target fields.
     */
    public function convert(ImportRecordModel $model, ReferenceResolver $referenceResolver): array
    {
        $data = [];
        $importFieldMap = $this->importConfiguration->getImportFieldMap();
        $rawData = $model->getImportData();
        $postProcessingFieldMap = [];
        foreach ($importFieldMap as $mapEntry) {
            $targetField = $mapEntry->getTargetField();
            if ($targetField === $this->importConfiguration->getSourceIdentifierField()) {
                $data[$targetField] = $this->getImportIdentifier($rawData);
                continue;
            }
            try {
                $rawValue = $this->getRawValueForMapEntry($rawData, $mapEntry);
            } catch (MappingException $e) {
                $model->addUnresolvedValue($targetField, sprintf('Raw value no found for %s.%s in source field %s. Message: %s', $this->table, $targetField, $mapEntry->getSourceField(), $e->getMessage()));
                continue;
            }
            if ($rawValue && $mapEntry->isReference()) {
                $rawValue = $referenceResolver->resolve($model, $rawValue, $mapEntry);
            }
            if ($strategy = $this->fieldStrategies[$targetField] ?? null) {
                $val = $strategy->normalize($rawValue);
            } else {
                $val = $rawValue;
            }
            if (is_string($val)) {
                $val = trim($this->decodeEscapedUtf8($val));
            }
            if ($mapEntry->getLength() > 0 && !is_numeric($val)) {
                $val = $this->ensureMaxLengthInBounds((string)$val, $mapEntry);
            }
            if ($mapEntry->has('postProcessing')) {
                $postProcessingFieldMap[$targetField] = $mapEntry;
            }
            $data[$targetField] = $val;
        }
        if ($targetRecordId = $model->getTargetRecordId()) {
            $data['uid'] = $targetRecordId;
        }
        $data['pid'] = $model->getPid();
        $data['crdate'] = $data['crdate'] ?? $model->getCrdate();
        if (empty($data['tstamp'])) {
            $data['tstamp'] = $model->getCrdate();
        }
        $data['data_hash'] = $model->getDataHash();
        if (!empty($postProcessingFieldMap)) {
            return $this->postProcessData($data, $postProcessingFieldMap);
        }
        return $data;
    }

    /**
     * Ensures the provided value does not exceed the maximum allowed length defined in the map entry.
     * If the value is too long, it is either trimmed or a validation exception is thrown, depending on the configuration.
     *
     * @param string $val The value to be validated and possibly cropped.
     * @param \BrainAppeal\CampusEventsConnector\Import\Configuration\ImportFieldConfigurationModel $mapEntry Configuration containing length constraints and field-specific settings.
     * @return string The processed value, trimmed to the allowed length if necessary.
     * @throws ValidationException If the value exceeds the maximum allowed length in strict mode.
     */
    private function ensureMaxLengthInBounds(string $val, ImportFieldConfigurationModel $mapEntry): string
    {
        if ($mapEntry->getLength() > 0 && !is_numeric($val) && ($valLength = mb_strlen($val)) > $mapEntry->getLength()) {
            $targetField = $mapEntry->getTargetField();
            if ($mapEntry->get('strict_length')) {
                throw new ValidationException(sprintf('Field "%s" exceeds maximum length of %d', $targetField, $mapEntry->getLength()), 1735485460);
            }
            $cropFieldInfo = $this->croppedFieldInfo[$targetField] ?? [];
            $prevMaxLength = $cropFieldInfo['maxLength'] ?? 0;
            if ($valLength > $prevMaxLength) {
                $croppedValueCount = (int)($cropFieldInfo['croppedValueCount'] ?? 0);
                $this->croppedFieldInfo[$targetField] = [
                    'targetField' => $targetField,
                    'maxLength' => $valLength,
                    'allowedLength' => $mapEntry->getLength(),
                    'croppedValueCount' => $croppedValueCount + 1,
                    'valueBeforeCrop' => $val,
                ];
            }
            $val = trim(mb_substr($val, 0, $mapEntry->getLength()));
        }
        return $val;
    }

    /**
     * Decodes escaped UTF-8 characters in the given string.
     *
     * @param string $value The string containing escaped UTF-8 sequences to decode.
     * @return string The decoded string, or an empty string if the input is null or decoding fails.
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
     * Processes and transforms data based on the provided field mapping configuration.
     *
     * @param array<string, mixed> $data The input data array that needs post-processing.
     * @param array<string, \BrainAppeal\CampusEventsConnector\Import\Configuration\ImportFieldConfigurationModel> $postProcessingFieldMap An associative array defining post-processing rules for specific fields. Each key is a target field and the value contains configuration such as normalization rules.
     * @return array<string, mixed> The updated data array after applying the post-processing rules.
     */
    protected function postProcessData(array $data, array $postProcessingFieldMap): array
    {
        foreach ($postProcessingFieldMap as $targetField => $mapEntry) {
            $postProcessing = $mapEntry->get('postProcessing');
            if (!empty($postProcessing)) {
                $normalize = $postProcessing['normalize'] ?? [];
                if (!empty($normalize['slug'])) {
                    $fieldSeparator = $normalize['slug']['fieldSeparator'] ?? '-';
                    $fields = $normalize['slug']['fields'] ?? [];
                    $values = [];
                    foreach ($fields as $field) {
                        $values[] = $data[$field] ?? '';
                    }
                    $data[$targetField] = $this->slugNormalizer->normalize(implode($fieldSeparator, $values), $fieldSeparator);
                }
            }
        }
        return $data;
    }

    /**
     * Retrieves information about the cropped field.
     *
     * @return array<string, array{targetField: string, maxLength: int, allowedLength: int, croppedValueCount: int, valueBeforeCrop: string}> An array containing details related to the cropped field.
     */
    public function getCroppedFieldInfo(): array
    {
        return $this->croppedFieldInfo;
    }

    /**
     * Determines the raw value based on the provided mapping entry configuration and raw data.
     *
     * @param array<string, mixed> $rawData The source data array used for fetching the raw value.
     * @param \BrainAppeal\CampusEventsConnector\Import\Configuration\ImportFieldConfigurationModel $mapEntry An associative array containing mapping configuration, such as field, attribute, default value, language, and value map.
     * @param bool $normalizeValue Whether to apply formatting functions to the raw value.
     * @return mixed The resolved raw value after applying the mapping rules, or the default value if no specific mappings apply.
     */
    public function getRawValueForMapEntry(array $rawData, ImportFieldConfigurationModel $mapEntry, bool $normalizeValue = false): mixed
    {
        try {
            $rawValue = $this->rawValueExtractor->getRawValueForMapEntry($rawData, $mapEntry);
        } catch (MappingException $e) {
            throw new MappingException(sprintf('Mapping in table %s is incorrect.', $this->table) . ': ' . $e->getMessage(), 1767026655, $e);
        }
        if ($normalizeValue && $strategy = $this->fieldStrategies[$mapEntry->getTargetField()] ?? null) {
            return $strategy->normalize($rawValue);
        }
        return $rawValue;
    }
}
