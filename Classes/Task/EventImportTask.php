<?php

/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2026 Brain Appeal GmbH
 *
 * @copyright 2019 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Task;

use BrainAppeal\CampusEventsConnector\CeImport\ImportRunner;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class EventImportTask extends AbstractTask
{
    use EventImportValidationTrait;

    public const BASE_URI_DEFAULT = 'https://campusevents.example.com/';

    /**
     * @var string
     */
    public $apiKey = '';

    /**
     * @var string
     */
    public $baseUri;

    /**
     * @var int|null
     */
    public $pid;

    /**
     * @var int
     */
    public $storageId = 0;

    /**
     * @var ?string
     */
    public ?string $storageFolder = null;

    public bool $forceUpdate = false;

    /**
     * @inheritdoc
     */
    public function execute(): bool
    {
        $pid = (int)$this->pid;
        $config = [
            'importStoragePid' => $pid,
            'forceUpdate' => $this->forceUpdate,
            'stopPrevious' => true,
            'completeUpdate' => true,
            'importTargetResourceIdentifier' => '',
            'baseUri' => $this->baseUri,
            'apiKey' => $this->apiKey,
            //'debug' => false,
        ];
        if (!empty($this->storageId) && !in_array($this->storageFolder, [null, ''], true)) {
            $config['importTargetResourceIdentifier'] = $this->storageId . ':' . trim($this->storageFolder, '/') . '/';
        }
        $importRunner = GeneralUtility::makeInstance(ImportRunner::class);
        try {
            $importRunner->run($pid, $config);
        } catch (\Exception $e) {
            $this->logException($e);
            return false;
        } catch (\Throwable $e) {
            $this->logger->error('Event import task failed: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }

        return true;
    }
    /**
     * Return current field values as associative array.
     * This method is called during migration from old serialized tasks
     * and when displaying task information.
     */
    public function getTaskParameters(): array
    {
        $tcaPrefix = 'ce_event_import_';
        return [
            $tcaPrefix . 'base_uri' => $this->baseUri,
            $tcaPrefix . 'pid' => $this->pid,
            'file_storage' => $this->storageId,
            $tcaPrefix . 'storage_folder' => $this->storageFolder,
            $tcaPrefix . 'force_update' => $this->forceUpdate,
            $tcaPrefix . 'api_key' => $this->apiKey,
        ];
    }
    /**
     * Set field values from an associative array.
     * This method handles both old and new parameter formats for migration.
     *
     * @param array $parameters Values from either old AdditionalFieldProvider or new TCA fields
     */
    public function setTaskParameters(array $parameters): void
    {
        $prefix = 'campusEventsConnector_eventImport_';
        $tcaPrefix = 'ce_event_import_';
        // Handle migration: check old parameter names first, then new TCA field names
        $baseUri = $parameters[$prefix . 'baseUri'] ?? $parameters[$tcaPrefix . 'base_uri'] ?? '';
        if (!empty($baseUri) && !str_starts_with((string)$baseUri, 'http')) {
            $baseUri = 'https://' . $baseUri;
        }
        $this->apiKey = $parameters[$prefix . 'apiKey'] ?? $parameters[$tcaPrefix . 'api_key'] ?? '';
        $this->baseUri = $baseUri;
        $this->pid = $parameters[$prefix . 'pid'] ?? $parameters[$tcaPrefix . 'pid'] ?? null;
        $this->storageId = (int)($parameters[$prefix . 'storageId'] ?? $parameters['file_storage'] ?? 0);
        $this->storageFolder = $parameters[$prefix . 'storageFolder'] ?? $parameters[$tcaPrefix . 'storage_folder'] ?? null;
        if (empty($this->storageFolder)) {
            $taskKey = $this->getTaskUid();
            if (empty($taskKey)) {
                $taskKey = time() % 10000;
            }
            $this->storageFolder = 'campus_events_import/task-' . $taskKey . '/';
        }
        $this->forceUpdate = $parameters[$prefix . 'forceUpdate'] ?? $parameters[$tcaPrefix . 'force_update'] ?? false;
    }
    public function validateTaskParameters(array $parameters): bool
    {
        $validData = true;
        $validData &= $this->validateBaseUriAndApiKeyAdditionalField($parameters);
        $validData &= $this->validatePidAdditionalField($parameters);
        $validData &= $this->validateStorageIdAdditionalField($parameters);
        $validData &= $this->validateStorageFolderAdditionalField($parameters);
        return (bool)$validData;
    }

    /**
     * @return ?string
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * @return string
     */
    public function getBaseUri(): string
    {
        return $this->baseUri ?: self::BASE_URI_DEFAULT;
    }

    /**
     * @return int|null
     */
    public function getPid(): ?int
    {
        return $this->pid;
    }

    /**
     * @return int
     */
    public function getStorageId(): int
    {
        return $this->storageId;
    }

    /**
     * @return string
     */
    public function getStorageFolder(): string
    {
        return $this->storageFolder;
    }

}
