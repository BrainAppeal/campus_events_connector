<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy;

use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportFieldConfigurationModel;

/**
 * Strategy registry for value normalizers
 */
readonly class NormalizerStrategyRegistry
{

    /**
     * Determines and returns the fully qualified class name of the appropriate
     * strategy class based on the provided normalizer key.
     *
     * This method checks various normalizer strategy classes to identify which
     * class supports the provided key and returns its class name. If no matching
     * class is found, it returns null.
     *
     * @param string $normalizerKey The key used to identify the appropriate normalizer strategy class.
     * @return string|null The fully qualified class name of the matching normalizer strategy, or null if no match is found.
     */
    protected static function getSupportingClass(string $normalizerKey): ?string
    {
        if (IntegerNormalizerStrategy::supports($normalizerKey)) {
            return IntegerNormalizerStrategy::class;
        }
        if (BooleanNormalizerStrategy::supports($normalizerKey)) {
            return BooleanNormalizerStrategy::class;
        }
        if (StringNormalizerStrategy::supports($normalizerKey)) {
            return StringNormalizerStrategy::class;
        }
        if (TextNormalizerStrategy::supports($normalizerKey)) {
            return TextNormalizerStrategy::class;
        }
        if (BigintNormalizerStrategy::supports($normalizerKey)) {
            return BigintNormalizerStrategy::class;
        }
        if (BoolAsIntNormalizerStrategy::supports($normalizerKey)) {
            return BoolAsIntNormalizerStrategy::class;
        }
        if (DatetimeToTstampNormalizerStrategy::supports($normalizerKey)) {
            return DatetimeToTstampNormalizerStrategy::class;
        }
        if (HtmlForRteNormalizerStrategy::supports($normalizerKey)) {
            return HtmlForRteNormalizerStrategy::class;
        }
        if (ApiReferenceToIdNormalizerStrategy::supports($normalizerKey)) {
            return ApiReferenceToIdNormalizerStrategy::class;
        }
        if (DecimalNormalizerStrategy::supports($normalizerKey)) {
            return DecimalNormalizerStrategy::class;
        }
        return null;
    }

    /**
     * @param string $normalizerKey
     * @param ImportFieldConfigurationModel $mapEntry
     * @return ?NormalizerStrategyInterface
     */
    public static function getNormalizerForImportField(string $normalizerKey, ImportFieldConfigurationModel $mapEntry): ?NormalizerStrategyInterface
    {
        $class = self::getSupportingClass($normalizerKey);
        if ($class) {
            return new $class($mapEntry);
        }
        return null;
    }
}
