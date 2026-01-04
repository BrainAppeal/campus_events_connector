<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy;

/**
 * Strategy for normalizing string values.
 */
readonly class StringNormalizerStrategy extends AbstractNormalizerStrategy
{

    public function normalize(mixed $value): ?string
    {
        if ($value === null && $this->isNullable) {
            return null;
        }
        return $this->parser->formatString($value);
    }

    public static function supports(string $normalizerKey): bool
    {
        return in_array($normalizerKey, ['string', 'varchar'], true);
    }
}
