<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Model;

/**
 * There currently is no way in the TYPO3 core for loading all file references for all fields of multiple records
 * in one SQL query.
 * Additionally, we can't use the core FileReference because by calling the constructor, the File object is automatically
 * initialized. Since we don't need that, we don't use the FileReference model.
 * @See \TYPO3\CMS\Core\Resource\FileReference
 */
class ImportFileReferenceModel
{
    protected int $uid = 0;
    protected int $pid = 0;
    protected string $tablenames = '';
    protected string $fieldname = '';
    protected int $uidForeign = 0;
    protected int $sortingForeign = 0;
    protected int $uidLocal = 0;
    protected bool $hidden = false;
    protected ?string $alternative = null;
    protected ?string $identifier = null;
    protected bool $missing = false;
    protected ?string $fileName = null;
    protected string $fileSha1 = '';
    protected int $fileSize = 0;
    protected int $fileModificationDate = 0;
    protected bool $valid = false;

    /**
     * @var int The language id
     */
    protected int $languageUid = 0;

    /**
     * @var int The language id
     */
    protected int $l10nParent = 0;

    public function __construct(array $row = [])
    {
        if (!empty($row)) {
            $this->uid = (int)($row['uid'] ?? 0);
            $this->pid = (int)($row['pid'] ?? 0);
            $this->tablenames = (string)($row['tablenames'] ?? '');
            $this->fieldname = (string)($row['fieldname'] ?? '');
            $this->uidForeign = (int)($row['uid_foreign'] ?? 0);
            $this->sortingForeign = (int)($row['sorting_foreign'] ?? 0);
            $this->uidLocal = (int)($row['uid_local'] ?? 0);
            $this->hidden = (bool)($row['hidden'] ?? false);
            $this->alternative = isset($row['alternative']) ? (string)$row['alternative'] : null;
            $this->identifier = isset($row['identifier']) ? (string)$row['identifier'] : null;
            $this->missing = (bool)($row['missing'] ?? false);
            $this->fileName = isset($row['file_name']) ? (string)$row['file_name'] : null;
            $this->fileSha1 = (string)($row['file_sha1'] ?? '');
            $this->fileSize = (int)($row['file_size'] ?? 0);
            $this->fileModificationDate = (int)($row['file_modification_date'] ?? 0);
            $this->languageUid = (int)($row['sys_language_uid'] ?? 0);
            $this->l10nParent = (int)($row['l10n_parent'] ?? 0);
        }
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }

    public function getTablenames(): string
    {
        return $this->tablenames;
    }

    public function setTablenames(string $tablenames): void
    {
        $this->tablenames = $tablenames;
    }

    public function getFieldname(): string
    {
        return $this->fieldname;
    }

    public function setFieldname(string $fieldname): void
    {
        $this->fieldname = $fieldname;
    }

    public function getUidForeign(): int
    {
        return $this->uidForeign;
    }

    public function setUidForeign(int $uidForeign): void
    {
        $this->uidForeign = $uidForeign;
    }

    public function getUidLocal(): int
    {
        return $this->uidLocal;
    }

    public function setUidLocal(int $uidLocal): void
    {
        $this->uidLocal = $uidLocal;
    }

    public function getSortingForeign(): int
    {
        return $this->sortingForeign;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    public function getAlternative(): ?string
    {
        return $this->alternative;
    }

    public function setAlternative(?string $alternative): void
    {
        $this->alternative = $alternative;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): void
    {
        $this->valid = $valid;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function isMissing(): bool
    {
        return $this->missing;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function getFileSha1(): string
    {
        return $this->fileSha1;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function getFileModificationDate(): int
    {
        return $this->fileModificationDate;
    }

    public function getLanguageUid(): int
    {
        return $this->languageUid;
    }

    public function getL10nParent(): int
    {
        return $this->l10nParent;
    }

}
