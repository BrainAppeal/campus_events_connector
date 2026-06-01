<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy;

use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportFieldConfigurationModel;

/**
 * Strategy for normalizing decimal values.
 */
readonly class DecimalNormalizerStrategy extends AbstractNormalizerStrategy
{
    private int $precision;

    public function __construct(ImportFieldConfigurationModel $mapEntry)
    {
        parent::__construct($mapEntry);
        $this->precision = (int)$mapEntry->get('precision', 2);
    }

    public function normalize(mixed $value): ?string
    {
        if ($value === null && $this->isNullable) {
            return null;
        }
        return number_format(round(((float)$value), $this->precision), $this->precision, '.', '');
    }

    public static function supports(string $normalizerKey): bool
    {
        return $normalizerKey === 'decimal';
    }
}
