<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Model;

/**
 * Import file mapping for ImportDataTransformerInterface
 */
class ImportFileMappingModel
{
    public const PROCESS_TYPE_CHECK_DELETE_LOCAL = 'checkDeleteLocal';
    public const PROCESS_TYPE_CHECK_UPDATE_LOCAL = 'checkUpdateLocal';
    public const PROCESS_TYPE_FETCH_REMOTE = 'fetchRemote';

    private string $targetField;
    private int $timestamp;
    private string $uri;
    private string $alternative;
    private string $processType;

    /**
     * @var int<0, max>|null The id of the page the record is "stored".
     */
    protected ?int $pid = null;

    /**
     * @var int
     */
    protected int $targetRecordId = 0;

    /**
     * @var int The language id
     */
    protected int $languageUid = 0;

    private ?ImportFileReferenceModel $fileReferenceModel = null;
    private array $clientOptions = [];
    private array $metaData = [];
    private string $fileName;

    public function __construct(
        string $targetField,
        int $targetRecordId,
        int $pid,
        int $timestamp,
        string $fileName,
        string $uri = '',
        string $alternative = '',
        string $processType = self::PROCESS_TYPE_CHECK_UPDATE_LOCAL,
        int $languageUid = 0
    ) {
        $this->targetField = $targetField;
        $this->timestamp = $timestamp;
        $this->uri = $uri;
        $this->alternative = $alternative;
        $this->validateProcessType($processType);
        $this->processType = $processType;
        $this->targetRecordId = $targetRecordId;
        $this->pid = $pid;
        $this->fileName = $fileName;
        $this->languageUid = $languageUid;
    }

    public function getTargetField(): string
    {
        return $this->targetField;
    }

    public function getPid(): ?int
    {
        return $this->pid;
    }

    public function getTargetRecordId(): int
    {
        return $this->targetRecordId;
    }

    public function getLanguageUid(): int
    {
        return $this->languageUid;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function setTimestamp(int $timestamp): void
    {
        $this->timestamp = $timestamp;
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

    public function getAlternative(): string
    {
        return $this->alternative;
    }

    public function setAlternative(string $alternative): void
    {
        $this->alternative = $alternative;
    }

    public function getProcessType(): string
    {
        return $this->processType;
    }

    public function setProcessType(string $processType): void
    {
        $this->validateProcessType($processType);
        $this->processType = $processType;
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
        return $this->timestamp > 0;
    }

    /**
     * Check if the file should be processed for deletion.
     *
     * @return bool
     */
    public function isCheckDeleteProcess(): bool
    {
        return $this->processType === self::PROCESS_TYPE_CHECK_DELETE_LOCAL;
    }

    /**
     * Check if the file should be processed for update.
     *
     * @return bool
     */
    public function isCheckUpdateProcess(): bool
    {
        return $this->processType === self::PROCESS_TYPE_CHECK_UPDATE_LOCAL;
    }

    /**
     * Check if the file should be fetched remotely.
     *
     * @return bool
     */
    public function isFetchRemoteProcess(): bool
    {
        return $this->processType === self::PROCESS_TYPE_FETCH_REMOTE;
    }

    public function getFileReferenceModel(): ?ImportFileReferenceModel
    {
        return $this->fileReferenceModel;
    }

    public function setFileReferenceModel(?ImportFileReferenceModel $fileReferenceModel): void
    {
        if ($fileReferenceModel && $fileReferenceModel->getPid() !== $this->pid) {
            $fileReferenceModel->setPid($this->pid);
        }
        $this->fileReferenceModel = $fileReferenceModel;
    }

    /**
     * @param array $clientOptions
     * @return void
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
        $this->metaData[$key] = $value;;
    }

    public function getMetaData(): array
    {
        if (!isset($this->metaData['alternative'])) {
            $this->metaData['alternative'] = $this->alternative;
        }
        return $this->metaData;
    }

    /**
     * Validates the ProcessType against allowed values.
     *
     * @param string $processType
     * @throws \InvalidArgumentException
     */
    private function validateProcessType(string $processType): void
    {
        $allowedTypes = [
            self::PROCESS_TYPE_CHECK_DELETE_LOCAL,
            self::PROCESS_TYPE_CHECK_UPDATE_LOCAL,
            self::PROCESS_TYPE_FETCH_REMOTE,
        ];

        if (!in_array($processType, $allowedTypes, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid processType "%s". Allowed types are: %s',
                    $processType,
                    implode(', ', $allowedTypes)
                )
            );
        }
    }

    /**
     * String representation for debugging
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf(
            'ImportFileMappingModel(field: %s, uri: %s, processType: %s, timestamp: %d)',
            $this->targetField,
            $this->uri ?: '(empty)',
            $this->processType,
            $this->timestamp
        );
    }
}
