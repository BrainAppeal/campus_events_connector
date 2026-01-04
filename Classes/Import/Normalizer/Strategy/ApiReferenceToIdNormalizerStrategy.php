<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Normalizer\Strategy;

/**
 * Strategy for normalizing integer values.
 */
readonly class ApiReferenceToIdNormalizerStrategy extends AbstractNormalizerStrategy
{

    public function normalize(mixed $value): int
    {
        if (empty($value)) {
            return 0;
        }
        if (is_array($value) && isset($value['@id'])) {
            return $this->formatApiReferenceToId($value['@id']);
        }
        if (ctype_digit((string)$value)) {
            return (int)$value;
        }
        return $this->formatApiReferenceToId($value);
    }

    /**
     * Returns the id for the given reference string
     *
     * @param string $apiReferenceId
     * @return int
     */
    private function formatApiReferenceToId(string $apiReferenceId): int
    {
        if (is_numeric($apiReferenceId)) {
            return (int)$apiReferenceId;
        }
        $parts = explode('/', $apiReferenceId);
        return (int) end($parts);
    }

    public static function supports(string $normalizerKey): bool
    {
        return $normalizerKey === 'api_reference_to_id';
    }
}
