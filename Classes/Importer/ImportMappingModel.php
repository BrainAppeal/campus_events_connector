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
     * @var ?ImportedModelInterface
     */
    protected ?ImportedModelInterface $domainModel = null;

    protected bool $isProcessed = false;

    protected bool $isInvalid = false;

    protected ?string $invalidReason = null;

    protected bool $dataMapped = false;

    protected ?string $table = null;
    private string $importSource;

    /**
     * @param int $importId
     * @param string $importType
     * @param string $importSource
     * @param ?array<string, mixed> $queueItem The record from the import queue
     */
    public function __construct(private readonly int $importId, private readonly string $importType, string $importSource, protected ?array $queueItem = null)
    {
        if (isset(ExtendedApiConnector::IMPORT_TYPE_TABLE_MAP[$importType])) {
            $this->table = ExtendedApiConnector::IMPORT_TYPE_TABLE_MAP[$importType];
        }
        $this->importSource = $importSource;
        if ($queueItem['data_processed']??false) {
            $this->isProcessed = true;
        }
    }

    public function getImportSource(): string
    {
        return $this->importSource;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function useDataHandler(): bool
    {
        return $this->table !== null;
    }

    /**
     * @return bool
     */
    public function existsInApi(): bool
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

    public function isDataMapped(): bool
    {
        return $this->dataMapped;
    }

    public function setDataMapped(bool $dataMapped): void
    {
        $this->dataMapped = $dataMapped;
    }

    public function isInvalid(): bool
    {
        return $this->isInvalid;
    }

    public function setIsInvalid(bool $isInvalid, ?string $invalidReason = null): void
    {
        $this->isInvalid = $isInvalid;
        $this->invalidReason = $invalidReason;
    }

    /**
     * @return int|null
     */
    public function getQueueItemUid(): ?int
    {
        if (null !== $this->queueItem) {
            return (int) $this->queueItem['uid'];
        }
        return null;
    }

    /**
     * @return int|null
     */
    public function getTargetModelUid(): ?int
    {
        return $this->domainModel?->getUid();
    }

    /**
     * @return array|null|bool
     */
    public function getImportData(): bool|array|null
    {
        if (null !== $this->queueItem && !empty($this->queueItem['import_data'])) {
            return json_decode((string) $this->queueItem['import_data'], true);
        }
        return null;
    }
}
