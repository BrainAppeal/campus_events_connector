<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\DataTransformer;

use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportFieldConfigurationModel;
use BrainAppeal\CampusEventsConnector\Import\Exception\MappingException;

/**
 * An abstract class that provides a base for data transformers,
 * offering functionality for processing and formatting import data records.
 */
class RawValueExtractor
{
    /**
     * Determines the raw value based on the provided mapping entry configuration and raw data.
     *
     * @param array<string, mixed> $rawData The source data array used for fetching the raw value.
     * @param ImportFieldConfigurationModel $mapEntry An associative array containing mapping configuration, such as field, attribute, default value, language, and value map.
     * @param string|array|null $importField Custom import field to use for fetching the raw value.
     * @return mixed The resolved raw value after applying the mapping rules, or the default value if no specific mappings apply.
     * @throws MappingException
     */
    public function getRawValueForMapEntry(array $rawData, ImportFieldConfigurationModel $mapEntry, string|array|null $importField = null): mixed
    {
        $rawValue = $mapEntry->getDefault();
        if (empty($importField)) {
            $importField = $mapEntry->getFieldOrFieldList();
        }
        if (!empty($importField)) {
            $rawValue = $this->getRawValueForField($importField, $rawData);
        } elseif ($mapEntry->has('attribute')) {
            $rawValue = $this->getRawValueForAttribute($mapEntry->get('attribute'), $rawData, $mapEntry->get('language'));
        }
        if ($mapValues = $mapEntry->get('mapValues')) {
            $rawValue = $this->getValueFromMap($rawValue, $mapValues);
        }
        return $rawValue;
    }

    /**
     * Retrieves a raw value from the provided array based on the given field or field list.
     *
     * @param string|string[] $fieldOrFieldList A single field name or a list of field names (representing a hierarchy)
     * @param array<string, mixed> $rawData The raw import data
     * @param bool $checkDotNotation Optional, whether to check for dot notation in the field name
     * @return mixed The raw value for the field or NULL
     * @throws MappingException
     */
    protected function getRawValueForField(string|array $fieldOrFieldList, array $rawData, bool $checkDotNotation = false): mixed
    {
        if (is_array($fieldOrFieldList)) {
            // get the field's value from the incoming array structure
            $rawValue = $rawData;
            $firstField = current(array_values($fieldOrFieldList));
            if (!isset($rawValue[$firstField])) {
                return null;
            }
            while (($key = array_shift($fieldOrFieldList)) !== null) {
                if (!is_array($rawValue) || !array_key_exists($key, $rawValue)) {
                    throw new MappingException(sprintf('Can\'t get raw value for field %s": Field not found in data.', implode('.', $fieldOrFieldList)), 1751549549);
                }
                $rawValue = $rawValue[$key];
            }
            return $rawValue;
        }
        if ($checkDotNotation && str_contains($fieldOrFieldList, '.')) {
            return $this->getRawValueForField(explode('.', $fieldOrFieldList), $rawData, false);
        }
        if (!array_key_exists($fieldOrFieldList, $rawData)) {
            throw new MappingException(sprintf('Can\'t get raw value for field %s": Field not found in data.', $fieldOrFieldList), 1751549550);
        }
        return $rawData[$fieldOrFieldList];
    }

    /**
     * Retrieves a mapped value from the provided array based on the given raw value.
     *
     * @param string|null $rawValue The raw value used to find a corresponding mapped value.
     * @param array<string, mixed> $mapValues An associative array where keys represent source values and values represent the target values.
     * @return mixed The mapped value if a match is found; otherwise, the original raw value is returned.
     */
    protected function getValueFromMap(?string $rawValue, array $mapValues): mixed
    {
        if (isset($mapValues[$rawValue])) {
            return $mapValues[$rawValue];
        }
        foreach ($mapValues as $mapSourceValue => $mapTargetValue) {
            if (str_starts_with(strtolower((string)$rawValue), $mapSourceValue)) {
                return $mapTargetValue;
            }
        }
        return $rawValue;
    }

    /**
     * Retrieves a raw value from the provided array based on the given attribute.
     *
     * @param string $mapAttribute An attribute field name
     * @param array<string, mixed> $rawData The raw import data
     * @param ?string $mustMatchLanguage Optional language
     * @return mixed The raw value for the field or NULL
     */
    private function getRawValueForAttribute(string $mapAttribute, array $rawData, ?string $mustMatchLanguage): mixed
    {
        $rawValue = null;
        $attributes = $rawData['attributes'] ?? [];
        foreach ($attributes as $attribute) {
            if (($attribute['attributeName'] ?? null) === $mapAttribute) {
                if (
                    // take any language
                    !$mustMatchLanguage ||
                    // match the desired language
                    $mustMatchLanguage === ($attribute['language'] ?? null)
                ) {
                    $rawValue = $attribute['value'] ?? null;
                    if ($rawValue === 'null') {
                        $rawValue = null;
                    }
                    break;
                }
            }
        }
        return $rawValue;
    }
}
