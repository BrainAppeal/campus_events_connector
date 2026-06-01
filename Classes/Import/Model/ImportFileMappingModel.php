<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Model;

/**
 * Import file mapping for ImportDataTransformerInterface
 */
class ImportFileMappingModel extends AbstractImportModel
{
    protected string $targetField;
    protected string $uri;

    protected ?ImportFileReferenceModel $fileReferenceModel = null;
    protected array $clientOptions = [];
    protected array $metaData = [];
    protected string $fileName;

    protected bool $isLocal = false;

    protected bool $isForceUpdate = false;

    protected ?array $categoryUidList = null;

    public function __construct(
        string $targetTable,
        string|int $sourceRecordIdentifier,
        string $targetField,
        int $targetRecordId,
        int $pid,
        int $lastUpdated,
        string $fileName,
        string $uri = '',
        int $languageUid = 0
    ) {
        parent::__construct($targetTable, $sourceRecordIdentifier);
        $this->targetField = $targetField;
        $this->lastUpdated = $lastUpdated;
        $this->uri = $uri;
        $this->targetRecordId = $targetRecordId;
        $this->pid = $pid;
        $this->fileName = $fileName;
        $this->languageUid = $languageUid;
    }

    public function getTargetField(): string
    {
        return $this->targetField;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    /**
     * Check if a URI exists.
     *
     * @return bool
     */
    public function hasUri(): bool
    {
        return !empty($this->uri);
    }

    /**
     * Check if a timestamp exists (greater than 0).
     *
     * @return bool
     */
    public function hasTimestamp(): bool
    {
        return $this->lastUpdated > 0;
    }

    public function getFileReferenceModel(): ?ImportFileReferenceModel
    {
        return $this->fileReferenceModel;
    }

    public function setFileReferenceModel(ImportFileReferenceModel $fileReferenceModel): void
    {
        $fileReferenceModel->setPid($this->pid);
        $this->fileReferenceModel = $fileReferenceModel;
    }

    /**
     * Retrieves the synchronized file reference model, initializing it if necessary.
     *
     * @return ImportFileReferenceModel The synchronized file reference model.
     */
    public function getSynchronizedFileReferenceModel(): ImportFileReferenceModel
    {
        if ($this->fileReferenceModel === null) {
            $this->fileReferenceModel = new ImportFileReferenceModel();
        }
        $this->fileReferenceModel->setTablenames($this->targetTable);
        $this->fileReferenceModel->setFieldname($this->targetField);
        $this->fileReferenceModel->setPid($this->pid);
        $this->fileReferenceModel->setUidForeign($this->targetRecordId);
        $this->fileReferenceModel->setLanguageUid($this->getLanguageUid());
        return $this->fileReferenceModel;
    }

    public function hasExistingFileReference(): bool
    {
        return $this->fileReferenceModel && $this->fileReferenceModel->getUid() > 0;
    }

    /**
     * @param array $clientOptions
     */
    public function setClientOptions(array $clientOptions): void
    {
        $this->clientOptions = $clientOptions;
    }

    public function getClientOptions(): array
    {
        return $this->clientOptions;
    }

    public function setMetaData(array $metaData): void
    {
        $this->metaData = $metaData;
    }

    public function addMetaData(string $key, string $value): void
    {
        $this->metaData[$key] = $value;
    }

    public function getMetaData(): array
    {
        return $this->metaData;
    }

    public function isLocal(): bool
    {
        return $this->isLocal;
    }

    public function setIsLocal(bool $isLocal): void
    {
        $this->isLocal = $isLocal;
    }

    public function getCategoryUidList(): ?array
    {
        return $this->categoryUidList;
    }

    public function setCategoryUidList(?array $categoryUidList): void
    {
        $this->categoryUidList = $categoryUidList;
    }

    public function isForceUpdate(): bool
    {
        return $this->isForceUpdate;
    }

    public function setIsForceUpdate(bool $isForceUpdate): void
    {
        $this->isForceUpdate = $isForceUpdate;
    }

    /**
     * String representation for debugging
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf(
            'ImportFileMappingModel(field: %s, uri: %s, timestamp: %d)',
            $this->targetField,
            $this->uri ?: '(empty)',
            $this->lastUpdated
        );
    }
}
