<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy;

/**
 * Interface for normalization strategies.
 */
interface NormalizerStrategyInterface
{
    /**
     * Normalizes a raw value according to the strategy.
     *
     * @param mixed $value The raw value to normalize.
     * @return mixed The normalized value.
     */
    public function normalize(mixed $value): mixed;

    /**
     * Returns whether this strategy supports the given mapping entry.
     *
     * @param string $normalizerKey The normalizer key to check.
     * @return bool
     */
    public static function supports(string $normalizerKey): bool;
}
