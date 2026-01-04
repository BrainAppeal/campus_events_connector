<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy;

/**
 * Strategy for normalizing integer values.
 */
readonly class IntegerNormalizerStrategy extends AbstractNormalizerStrategy
{

    public function normalize(mixed $value): ?int
    {
        if ($value === null && $this->isNullable) {
            return null;
        }
        return $this->parser->formatInt($value);
    }

    public static function supports(string $normalizerKey): bool
    {
        return $normalizerKey === 'int' || $normalizerKey === 'integer';
    }
}
