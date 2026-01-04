<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy;

/**
 * Strategy for normalizing boolean values as integers.
 */
readonly class BoolAsIntNormalizerStrategy extends BooleanNormalizerStrategy
{

    public static function supports(string $normalizerKey): bool
    {
        return $normalizerKey === 'bool_as_int';
    }
}
