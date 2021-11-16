<?php
/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2021 Brain Appeal GmbH
 *
 * @copyright 2021 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Importer;

use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;

class ImportMappingModel
{
    /**
     * @var array
     */
    protected $queueItem;

    /**
     * @var ImportedModelInterface|null
     */
    protected $domainModel;

    protected $isProcessed = false;
    /**
     * @var int
     */
    private $importId;
    /**
     * @var string
     */
    private $importType;

    public function __construct(int $importId, string $importType, $queueItem = null)
    {
        $this->importId = $importId;
        $this->importType = $importType;
        $this->queueItem = $queueItem;
    }

    /**
     * @return bool
     */
    public function existsInApi()
    {
        return null !== $this->queueItem;
    }

    /**
     * @return int
     */
    public function getImportId(): int
    {
        return $this->importId;
    }

    /**
     * @return string
     */
    public function getImportType(): string
    {
        return $this->importType;
    }

    /**
     * @return array
     */
    public function getQueueItem(): array
    {
        return $this->queueItem;
    }

    /**
     * @return ImportedModelInterface|null
     */
    public function getDomainModel(): ?ImportedModelInterface
    {
        return $this->domainModel;
    }

    /**
     * @param ImportedModelInterface|null $domainModel
     */
    public function setDomainModel(?ImportedModelInterface $domainModel): void
    {
        $this->domainModel = $domainModel;
    }


    /**
     * @return bool
     */
    public function isProcessed(): bool
    {
        return $this->isProcessed;
    }

    /**
     * @param bool $isProcessed
     */
    public function setIsProcessed(bool $isProcessed): void
    {
        $this->isProcessed = $isProcessed;
    }

    /**
     * @return int|null
     */
    public function getQueueItemUid()
    {
        if (null !== $this->queueItem) {
            return (int) $this->queueItem['uid'];
        }
        return null;
    }

    /**
     * @return int|null
     */
    public function getTargetModelUid()
    {
        if (null !== $this->domainModel) {
            return $this->domainModel->getUid();
        }
        return null;
    }

    /**
     * @return array|null
     */
    public function getImportData()
    {
        if (null !== $this->queueItem && !empty($this->queueItem['import_data'])) {
            return json_decode($this->queueItem['import_data'], true);
        }
        return null;
    }
}