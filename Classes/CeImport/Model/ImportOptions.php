<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\CeImport\Model;

use BrainAppeal\CampusEventsConnector\Utility\TCAUtility;
use BrainAppeal\CampusEventsConnector\Import\Configuration\ImportTableConfigurationProvider;
use BrainAppeal\CampusEventsConnector\Import\Model\AbstractImportOptions;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Represents the options and configuration for importing data.
 *
 * Provides properties and methods for configuring and managing data imports,
 * including controls for processing limits, API interaction, and debugging.
 *
 * The options can be provided through a combination of a configuration array
 * and command line input interface.
 */
class ImportOptions extends AbstractImportOptions
{
    /**
     * @var ?string
     */
    private ?string $apiKey;
    /**
     * @var ?string
     */
    private ?string $baseUri = null;

    /**
     * @param array<string, mixed> $extensionConfiguration
     * @param InputInterface|null $input
     */
    public function __construct(array $extensionConfiguration, ?InputInterface $input = null)
    {
        $importSource = ImportTableConfigurationProvider::getImportGroupKeyForTable(TCAUtility::TABLE_EVENTS);
        parent::__construct($importSource, $extensionConfiguration, $input);
    }

    /**
     * @param array<string, mixed> $config
     * @param InputInterface|null $input
     */
    public function updateConfiguration(array $config, ?InputInterface $input = null): void
    {
        if ($input) {
            $baseUri = $input->getArgument('baseuri');
            $apiKey = $input->getArgument('apikey');
        } else {
            $baseUri = $config['baseUri'] ?? null;
            $apiKey = $config['apiKey'] ?? null;
        }
        parent::updateConfiguration($config, $input);
        $this->apiKey = $apiKey;
        $this->baseUri = $baseUri;
        $importSource = preg_replace("/['\"]/", '', $baseUri);
        $host = parse_url($importSource, PHP_URL_HOST);
        if ($host) {
            $this->targetImportSource = (string)$host;
        } else {
            $this->targetImportSource = $importSource;
        }
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function getBaseUri(): ?string
    {
        return $this->baseUri;
    }

    public function getRelativeLocalCachePath(): ?string
    {
        return '/transient/ce-api-test/';
    }

    public function getCachePrefix(): string
    {
        return TCAUtility::CACHE_TAG_PREFIX;
    }
}
