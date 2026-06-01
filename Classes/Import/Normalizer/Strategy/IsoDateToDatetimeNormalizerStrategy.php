<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy;

/**
 * Strategy for normalizing iso datetime strings to MySQL datetime format.
 */
readonly class IsoDateToDatetimeNormalizerStrategy extends AbstractNormalizerStrategy
{
    public function normalize(mixed $value): ?string
    {
        if ($value === null && $this->isNullable) {
            return null;
        }
        if (!is_string($value)) {
            $value = (string)$value;
        }

        $dt = new \DateTime($value);
        $dt->setTimezone(new \DateTimeZone('Europe/Berlin'));
        return $dt->format('Y-m-d H:i:s');
    }

    public static function supports(string $normalizerKey): bool
    {
        return $normalizerKey === 'iso_date_to_datetime';
    }
}
