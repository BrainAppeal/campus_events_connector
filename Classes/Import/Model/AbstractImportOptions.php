<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Model;

use BrainAppeal\CampusEventsConnector\Import\Exception\ImportOptionsConfigurationException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Represents the options and configuration for importing data.
 *
 * Provides properties and methods for configuring and managing data imports,
 * including controls for processing limits, API interaction, and debugging.
 *
 * The options can be provided through a combination of a configuration array
 * and command line input interface.
 */
abstract class AbstractImportOptions
{
    public const DEVELOPMENT_LOCAL_CACHE_DAYS = 28;

    /**
     * Maximum number of rows to process per iteration for batch processing of records.
     */
    private const MAX_ROWS_TO_PROCESS_PER_ITERATION = 1000;

    /**
     * Fields that are optional and can be omitted from the configuration array.
     * @var string[]
     */
    protected array $optionalExtensionConfigurationFields = ['developmentLocalCacheAgeInDays', 'forceUpdate', 'completeUpdate', 'debug', 'targetImportSource'];

    /**
     * @var int<1, max>|null The uid of the import record.
     */
    protected ?int $importId = null;
    /**
     * The storage page ID
     */
    protected int $pid;

    /**
     * Maximum number of processed records per run
     */
    protected int $limit;
    /**
     * Force update of all records, i.e., skip checks for unchanged records
     */
    protected bool $forceUpdate;
    /**
     * Import all records at once
     */
    protected bool $completeUpdate;
    /**
     * Stop import entry marked as running (used in case something went wrong in the previous run)
     */
    protected bool $stopPrevious;
    /**
     * Enables or disables gathering of statistics
     */
    protected bool $enableStatistics;
    /**
     * Enables or disables debug mode
     */
    protected bool $debug;

    /**
     * Resource identifier / storage folder for downloaded files
     */
    protected string $fileTargetResourceIdentifier;

    /**
     * Defines the cache age in days for local development
     */
    public int $developmentLocalCacheAgeInDays;
    private string $importSource;
    /**
     * The storage page ID
     */
    protected int $maxRowsToProcessPerIteration = self::MAX_ROWS_TO_PROCESS_PER_ITERATION;

    /**
     * Verbosity level of the output
     * @var int
     */
    private int $verbosity = OutputInterface::VERBOSITY_NORMAL;
    /**
     * Optional value for the import source key in target tables.
     */
    protected ?string $targetImportSource;

    /**
     * @param string $importSource
     * @param array<string, mixed> $extensionConfiguration
     * @param InputInterface|null $input
     * @throws ImportOptionsConfigurationException
     */
    public function __construct(string $importSource, array $extensionConfiguration, ?InputInterface $input = null)
    {
        $this->validateExtensionConfiguration($extensionConfiguration);
        $this->updateConfiguration($extensionConfiguration, $input);
        $this->importSource = $importSource;
    }

    /**
     * @param array<string, mixed> $config
     * @param InputInterface|null $input
     */
    public function updateConfiguration(array $config, ?InputInterface $input = null): void
    {
        if ($input) {
            $this->forceUpdate = $input->hasOption('force-update') && $input->getOption('force-update');
            $this->completeUpdate = $input->hasOption('complete') && $input->getOption('complete');
            $this->stopPrevious = $input->hasOption('stop-previous') && $input->getOption('stop-previous');
            $this->enableStatistics = $input->hasOption('enable-statistics') && $input->getOption('enable-statistics');
            $this->debug = $input->hasOption('debug') && $input->getOption('debug');
            $pid = $input->hasArgument('pid') ? (int)$input->getArgument('pid') : null;
            $optLimit = $input->hasOption('limit') ? $input->getOption('limit') : 0;
        } else {
            $this->forceUpdate = $config['forceUpdate'] ?? false;
            $this->completeUpdate = $config['completeUpdate'] ?? false;
            $this->stopPrevious = $config['stopPrevious'] ?? false;
            $this->enableStatistics = $config['enableStatistics'] ?? false;
            $this->debug = $config['debug'] ?? false;

            $pid = null;
            $optLimit = null;
        }
        $this->developmentLocalCacheAgeInDays = (int)($config['developmentLocalCacheAgeInDays'] ?? self::DEVELOPMENT_LOCAL_CACHE_DAYS);
        $this->fileTargetResourceIdentifier = (string)($config['importTargetResourceIdentifier'] ?? null);
        $this->targetImportSource = (string)($config['targetImportSource'] ?? null);
        if (!$pid) {
            $pid = $config['importStoragePid'] ?? null;
        }
        if ($pid) {
            $this->pid = $pid;
        }
        $this->limit = $this->getLimitOption($optLimit, $config);
    }

    /**
     * Determines the limit value based on the provided option and configuration.
     *
     * @param mixed $optLimit The limit option provided by the input or null if not set.
     * @param array<string, mixed> $config The configuration array containing default values.
     *
     * @return int Returns the calculated limit value.
     */
    protected function getLimitOption(mixed $optLimit, array $config): int
    {
        if ($this->completeUpdate) {
            return 0;
        }
        $limit = (int)$optLimit;
        if ($optLimit === null || $limit < 0) {
            $limit = (int)($config['chunkSize'] ?? 500);
        }
        return $limit;
    }

    public function getImportId(): ?int
    {
        return $this->importId;
    }

    public function setImportId(?int $importId): void
    {
        $this->importId = $importId;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function isForceUpdate(): bool
    {
        return $this->forceUpdate;
    }

    public function enableForceUpdate(): void
    {
        $this->forceUpdate = true;
    }

    public function supportsContinuedImport(): bool
    {
        return false;
    }

    public function isCompleteUpdate(): bool
    {
        return $this->completeUpdate;
    }

    public function isStopPrevious(): bool
    {
        return $this->stopPrevious;
    }

    public function enableStatistics(): void
    {
        $this->enableStatistics = true;
    }

    public function isEnableStatistics(): bool
    {
        return $this->enableStatistics;
    }

    public function getVerbosity(): int
    {
        return $this->verbosity;
    }

    public function setVerbosity(int $verbosity): void
    {
        $this->verbosity = $verbosity;
    }

    /**
     * Returns whether verbosity is verbose (-v).
     */
    public function isVerbose(): bool
    {
        return OutputInterface::VERBOSITY_VERBOSE <= $this->verbosity;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function getFileTargetResourceIdentifier(): string
    {
        return $this->fileTargetResourceIdentifier;
    }

    public function getDevelopmentLocalCacheAgeInDays(): int
    {
        return $this->developmentLocalCacheAgeInDays;
    }

    /**
     * Returns the relative path to the local cache directory for development purposes.
     */
    public function getRelativeLocalCachePath(): ?string
    {
        return null;
    }

    public function getImportSource(): string
    {
        return $this->importSource;
    }

    public function getMaxRowsToProcessPerIteration(): int
    {
        return $this->maxRowsToProcessPerIteration;
    }

    public function getTargetImportSource(): ?string
    {
        return $this->targetImportSource;
    }

    abstract public function getCachePrefix(): string;

    /**
     * Validates the extension configuration.
     *
     * @param array<string, mixed> $config
     * @throws ImportOptionsConfigurationException
     */
    protected function validateExtensionConfiguration(array $config): bool
    {
        $hasMissingConfiguration = false;
        $missingValues = [];
        foreach ($config as $key => $value) {
            if (empty($value) && !in_array($key, $this->optionalExtensionConfigurationFields, false)) {
                $missingValues[] = $key;
                $hasMissingConfiguration = true;
            }
        }
        if (!empty($missingValues)) {
            throw new ImportOptionsConfigurationException('Missing configuration values: ' . implode(',', $missingValues));
        }
        return !$hasMissingConfiguration;
    }

    public function useDataHandlerForSlugUpdates(): bool
    {
        return true;
    }
}
