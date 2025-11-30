<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * A utility class to handle parsing and formatting of various data types.
 */
class DataParser
{
    public const int UNIX_TIMESTAMP_MAX = 2147483647;

    /**
     * @var array<string, array<string, string>>
     */
    protected static array $tableFormatFunctions = [];

    /**
     * Retrieves and returns an array of format functions for the specified table.
     *
     * @param string $table The name of the table for which to retrieve format functions.
     * @return array An associative array where the keys are target field names, and the values are the corresponding format function names.
     */
    public static function getFormatFunctionsForTable(string $table): array
    {
        if (!isset(self::$tableFormatFunctions[$table])) {
            $fieldMap = TableFieldMapHelper::getImportFieldMap($table);
            $parser = new self();
            $formatFunctions = [];
            foreach ($fieldMap as $mapEntry) {
                $type = $mapEntry['type'];
                $ccKey = ucwords(str_replace('_', ' ', (string)$type));
                $formatFunction = 'format' . str_replace(' ', '', $ccKey);
                if (method_exists($parser, $formatFunction)) {
                    $targetField = $mapEntry['target_field'];
                    $formatFunctions[$targetField] = 'format' . str_replace(' ', '', $ccKey);
                }
            }
            self::$tableFormatFunctions[$table] = $formatFunctions;
        }
        return self::$tableFormatFunctions[$table];
    }

    public function formatText(?string $value): string
    {
        return $this->formatString($value);
    }

    /**
     * Replace some characters
     *
     * @param mixed $value
     * @return string
     */
    public function formatString(mixed $value): string
    {
        // Empty XML tags will result in empty array values
        if (is_array($value) && empty($value)) {
            return '';
        }
        if (empty($value) || is_numeric($value)) {
            return (string)$value;
        }
        $trgEnc = 'UTF-8';
        $cleanedValue = $value;
        if (function_exists('mb_detect_encoding')) {
            $enc = mb_detect_encoding((string)$value, $trgEnc, true);
            if (empty($enc)) {
                $enc = mb_detect_encoding((string)$value, 'ISO-8859-1', true);
            }
        }
        if (!empty($enc) && $enc !== 'UTF-8' && function_exists('iconv')) {
            $cleanedValue = iconv($enc, $trgEnc, $cleanedValue);
        }
        return (string)$cleanedValue;
    }

    /**
     * Format value to 1 or 0
     *
     * @param mixed $value
     *
     * @return int
     */
    public function formatBoolAsInt(mixed $value): int
    {
        return $this->formatBoolean($value) ? 1 : 0;
    }

    /**
     * Format string to boolean value
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function formatBoolean(mixed $value): bool
    {
        if (empty($value)) {
            return false;
        }
        if (is_bool($value)) {
            return true;
        }
        if (is_string($value)) {
            return in_array(mb_strtolower($value), ['ja', 'yes', 'j', '1', 'true', 't'], false);
        }
        return (bool)$value;
    }

    /**
     * Format csv string to array value
     *
     * @param string $value
     *
     * @return array
     */
    public function formatCsv($value): array
    {
        $possibleDelimiters = ['|', ',', ';'];
        $delimiter = ';';
        foreach ($possibleDelimiters as $checkDelimiter) {
            if (str_contains($value, $checkDelimiter)) {
                $delimiter = $checkDelimiter;
            }
        }
        return explode($delimiter, $value);
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
    public function formatFloat($string): float
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
                //12345,67
            } else {
                $value = str_replace(',', '.', $value);
            }
            $value = (float)$value;
        } //invalid number e.g. 1a4c
        elseif (!$pointSet) {
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

    public function formatInteger($value): int
    {
        return $this->formatInt($value);
    }
    public function formatBigint($value): int
    {
        return $this->formatInt($value);
    }

    /**
     * Parses the given string value to extract an integer.
     *
     * @param mixed $value A string containing an integer
     *
     * @return int A valid integer
     */
    public function formatInt($value): int
    {
        if (empty($value)) {
            return 0;
        }
        $value = trim((string)$value);
        $onlyDigitsStr = preg_replace('/[^0-9]/', '', $value);
        if (strlen((string)$onlyDigitsStr) > 1) {
            $onlyDigitsStr = ltrim((string)$onlyDigitsStr, '0');
        }
        //Return 0 if the string contains no numbers empty
        if ($onlyDigitsStr === '') {
            return 0;
        }
        $intVal = $onlyDigitsStr;
        //if value starts with minus sign, add sign to intVal (negative value)
        if (str_starts_with($value, '-')) {
            $intVal = '-' . $onlyDigitsStr;
        }
        return (int)$intVal;
    }

    /**
     * Try to convert a date value into the format "YYYY-MM-DD"
     *
     * @param string $value
     * @return string|null
     */
    public function formatDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $date = $this->convertDateString($value);
        // Value is already in the correct format
        if ($date instanceof \DateTime) {
            return $date->format('Y-m-d');
        }
        $year = 0;
        $month = 0;
        $day = 0;
        if (str_contains($value, '.')) {
            $dateParts = explode('.', $value);
            if (count($dateParts) === 3) {
                $year = (int)$dateParts[2];
                $month = (int)$dateParts[1];
                $day = (int)$dateParts[0];
            }
        } else {
            $dateParts = explode('-', $value);
            $year = (int)$dateParts[0];
            $month = (int)$dateParts[1];
            $day = (int)$dateParts[2];
        }
        $value = '';
        if ($day > 0 && $month > 0 && $year > 0) {
            if (strlen((string)$year) === 2) {
                $year += $year > 50 ? 1900 : 2000;
            }
            if ($month < 10) {
                $month = '0' . $month;
            }
            if ($day < 10) {
                $day = '0' . $day;
            }
            $value = $year . '-' . $month . '-' . $day;
        }
        return $value;
    }

    /**
     * Try to convert a datetime value into an unix timestamp
     *
     * @param ?string $value
     * @return int
     */
    public function formatDatetimeToTstamp(?string $value): int
    {
        if ($value && !str_starts_with($value, '9999-12-31')) {
            $convertedValue = strtotime($value);
            if ($convertedValue !== false && $convertedValue < self::UNIX_TIMESTAMP_MAX) {
                return $convertedValue;
            }
        }
        return 0;
    }

    /**
     * Attempts to convert a string into a DateTime object
     *
     * @param ?string $value
     *
     * @return ?\DateTime
     */
    protected function convertDateString($value): ?\DateTime
    {
        $date = self::dateGerman2Iso($value);
        if (!$date) {
            return null;
        }
        $date = \DateTime::createFromFormat('Y-m-d', $date);

        // Invalid dates can show up as warnings (i.e. "2007-02-99")
        // and still return a DateTime object.
        $errors = \DateTime::getLastErrors();
        if ($errors && $errors['warning_count'] > 0) {
            return null;
        }
        if ((int)$date->format('Y') >= 9999) {
            return null;
        }

        return $date;
    }

    /**
     * Transforms a german date to a MySQL date (ISO-Date).
     *
     * @param ?string $checkDate Date string with format dd.mm.YYYY (or YY)
     * @return ?string Iso date
     */
    public static function dateGerman2Iso(?string $checkDate): ?string
    {
        if (empty($checkDate)) {
            return null;
        }
        //If the date does not have format dd.mm.YYYY
        if (!str_contains($checkDate, '.')) {
            return $checkDate;
        }
        $date = $checkDate;
        [$day, $month, $year] = explode('.', $date);
        if (strlen($year) === 2) {
            $year = (int)$year + 2000;
        }
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * Remove line breaks after end tags in HTML to prevent RTE from adding unnecessary empty lines
     * @param ?string $html
     * @return string
     */
    public function formatHtmlForRTE(?string $html): string
    {
        if (empty($html)) {
            return '';
        }
        $replaceHtmlWith = [
            '<br>' => ['<br />', '<br/>'],
            'ä' => '&auml;',
            'ö' => '&ouml;',
            'ü' => '&uuml;',
            'Ä' => '&Auml;',
            'Ö' => '&Ouml;',
            'Ü' => '&Uuml;',
            'ß' => '&szlig;',
        ];
        $cleanedHtml = preg_replace("/<img[^>]+>/i", '', $html);
        if (mb_strlen((string) $cleanedHtml) > 65535) {
            $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $append = '...';
            $cleanedHtml = $contentObject->cropHTML($cleanedHtml, 65530 . '|' . $append . '|1');
        }
        foreach ($replaceHtmlWith as $replaceWith => $searchFor) {
            $cleanedHtml = str_replace($searchFor, $replaceWith, $cleanedHtml);
        }
        $cleanedHtmlRte = $cleanedHtml;
        $removeLineBreaksBeforeAndAfterTags = ['br', 'p', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'h5', 'div', 'table'];
        try {
            foreach ($removeLineBreaksBeforeAndAfterTags as $tag) {
                $tagStart = '<' . $tag . '>';
                $tagEnd = '</' . $tag . '>';
                if (stripos((string) $cleanedHtmlRte, $tagStart) !== false) {
                    $cleanedHtmlRte = preg_replace('/\s*(<' . $tag . '[^>]*>)\s*/i', '$1', (string) $cleanedHtmlRte);
                }
                if (stripos((string) $cleanedHtmlRte, $tagEnd) !== false) {
                    $cleanedHtmlRte = preg_replace('/\s*(<\/' . $tag . '>)\s*/i', '$1', (string) $cleanedHtmlRte);
                }
            }
        } catch (\Exception) {
            return $cleanedHtml;
        }
        return $cleanedHtmlRte;
    }
}
