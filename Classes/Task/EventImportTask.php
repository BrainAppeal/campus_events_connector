<?php
/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2019 Brain Appeal GmbH
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

    public const BASE_URI_DEFAULT = 'https://campusevents.example.com/';

    /**
     * @var string
     */
    public $apiKey;

    /**
     * @var string
     */
    public $baseUri;

    /**
     * @var int|null
     */
    public $pid;

    /**
     * @var ?int
     */
    public $storageId;

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
        if (!empty($this->storageId) && !empty($this->storageFolder)) {
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
