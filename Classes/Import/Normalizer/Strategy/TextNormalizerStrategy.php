<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy;

/**
 * Strategy for normalizing text values.
 */
readonly class TextNormalizerStrategy extends AbstractNormalizerStrategy
{

    public function normalize(mixed $value): ?string
    {
        if ($value === null && $this->isNullable) {
            return null;
        }
        return $this->parser->formatString(is_string($value) ? $value : (string)$value);
    }

    public static function supports(string $normalizerKey): bool
    {
        return $normalizerKey === 'text';
    }
}
