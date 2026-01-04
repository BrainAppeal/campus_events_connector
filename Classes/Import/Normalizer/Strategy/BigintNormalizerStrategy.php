<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy;

/**
 * Strategy for normalizing bigint values.
 */
readonly class BigintNormalizerStrategy extends AbstractNormalizerStrategy
{
    public function normalize(mixed $value): int
    {
        return $this->parser->formatInt($value);
    }

    public static function supports(string $normalizerKey): bool
    {
        return $normalizerKey === 'bigint';
    }
}
