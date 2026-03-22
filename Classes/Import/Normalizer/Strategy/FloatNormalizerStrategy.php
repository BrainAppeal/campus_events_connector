<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy;

/**
 * Strategy for normalizing decimal values.
 */
readonly class FloatNormalizerStrategy extends AbstractNormalizerStrategy
{

    public function normalize(mixed $value): ?float
    {
        if ($value === null && $this->isNullable) {
            return null;
        }
        return $this->formatFloat((string)$value);
    }

    /**
     * Parses the given string value to extract a number. The function can
     * create valid float and integer values for different input formats,
     * e.g. if "," is used for float numbers instead of ".".
     *
     * Usage: Helper::parseNumber($value);
     *
     * @param string $string A string containing a number
     *
     * @return float A valid number
     */
    private function formatFloat(string $string): float
    {
        if ($string === '') {
            return 0;
        }
        $value = $string;
        $commaPos = strpos($value, ',');
        $commaSet = $commaPos !== false;
        $pointPos = strpos($value, '.');
        $pointSet = $pointPos !== false;
        if ($commaSet) {
            if ($pointSet) {
                //12,345.67
                if ($pointPos > $commaPos) {
                    $value = str_replace(',', '', $value);
                    //12.345,67
                } else {
                    $value = str_replace(['.', ','], ['', '.'], $value);
                }
                $value = (float)$value;
                //12345,67
            } else {
                $value = str_replace(',', '.', $value);
                $value = (float)$value;
            }
        } elseif (!$pointSet) {
            //invalid number e.g. 1a4c
            $value = $this->formatInt($value);
        } else {
            $value = (float)$value;
        }
        if (empty($value)) {
            $value = 0.0;
        } else {
            $value = (float)str_replace(',', '.', (string)$value);
        }
        return $value;
    }

    /**
     * Parses the given string value to extract an integer.
     *
     * @param string $value A string containing an integer
     *
     * @return int A valid integer
     */
    private function formatInt(string $value): int
    {
        $intVal = preg_replace('/[^0-9]/', '', trim($value));
        if (strlen((string)$intVal) > 1) {
            $intVal = ltrim((string)$intVal, '0');
        }
        //Return 0 if string is empty
        if ($intVal === '') {
            $intVal = 0;
        }
        //if value starts with minus sign, add sign to intVal (negative value)
        if (str_starts_with($value, '-')) {
            $intVal *= '-' . $intVal;
        }
        return (int)$intVal;
    }

    public static function supports(string $normalizerKey): bool
    {
        return $normalizerKey === 'float';
    }
}
