<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy;

/**
 * Strategy for normalizing boolean values.
 */
readonly class BooleanNormalizerStrategy extends AbstractNormalizerStrategy
{
    public function normalize(mixed $value): int
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
    protected function formatBoolean(mixed $value): bool
    {
        if (empty($value)) {
            return false;
        }
        if (is_string($value)) {
            return in_array(mb_strtolower($value), ['ja', 'yes', 'j', '1', 'true', 't'], false);
        }
        return (bool)$value;
    }

    public static function supports(string $normalizerKey): bool
    {
        return $normalizerKey === 'bool' || $normalizerKey === 'boolean';
    }
}
