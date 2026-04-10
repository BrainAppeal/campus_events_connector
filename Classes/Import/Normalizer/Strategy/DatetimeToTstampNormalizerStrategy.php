<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy;

/**
 * Strategy for normalizing datetime strings to timestamps.
 */
readonly class DatetimeToTstampNormalizerStrategy extends AbstractNormalizerStrategy
{

    public function normalize(mixed $value): ?int
    {
        if ($value === null && $this->isNullable) {
            return null;
        }
        if (!is_string($value)) {
            $value = (string)$value;
        }
        if ($value && !str_starts_with($value, '9999-12-31')) {
            $dateTimeObject = date_create($value);
            if ($dateTimeObject instanceof \DateTimeInterface) {
                $convertedValue = $dateTimeObject->getTimestamp();
            } else {
                $convertedValue = strtotime($value);
            }
            if ((int)$convertedValue > 0) {
                return $convertedValue;
            }
        }
        return 0;
    }

    public static function supports(string $normalizerKey): bool
    {
        return $normalizerKey === 'datetime_to_tstamp';
    }
}
