<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Repository;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;

abstract class AbstractFileRepository
{
    /**
     * @var array<string, \Doctrine\DBAL\Schema\Column[]>
     */
    protected array $tableColumns = [];

    public function __construct(
        protected readonly ResourceFactory $resourceFactory,
        protected readonly LoggerInterface $logger,
        protected readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * @return \Doctrine\DBAL\Schema\Column[]
     */
    final protected function getTableColumns(string $table): array
    {
        if (!isset($this->tableColumns[$table])) {
            $connectionPool = $this->connectionPool;
            $connection = $connectionPool->getConnectionForTable($table);
            $this->tableColumns[$table] = $connection->createSchemaManager()->listTableColumns($table);
        }
        return $this->tableColumns[$table];
    }

    /**
     * Ensures that the given value does not exceed the specified maximum byte length.
     *
     * @param mixed $value The value to be checked and potentially truncated.
     * @param int $maxBytes The maximum allowed byte length. A value of 0 allows any length.
     * @return mixed The processed value, truncated if necessary to meet the byte length requirement.
     */
    final protected function ensureMaxBytes(mixed $value, int $maxBytes): mixed
    {
        if ($maxBytes === 0 || is_numeric($value)) {
            return $value;
        }
        while (strlen($value) > $maxBytes) {
            $value = mb_substr($value, 0, -1, 'UTF-8');
        }
        return $value;
    }

    /**
     * Handles the deletion of a single file reference if its associated local file is missing or removed.
     *
     * @param int $sysFileUid Mapping info for a file reference (field name, table, target record UID)
     * @return bool
     */
    public function deleteFileByUid(int $sysFileUid): bool
    {
        if (!($sysFileUid > 0)) {
            return false;
        }
        $isDeleted = false;
        // Check mtime of the local file
        $file = null;
        try {
            $file = $this->resourceFactory->getFileObject($sysFileUid);
            $isDeleted = $file->delete();
        } catch (FileDoesNotExistException) {
            $this->logger->warning(sprintf('File with UID %d does not exist and cannot be deleted', $sysFileUid));
            // Nothing needs to be done, since we want the file to not exist
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Error while deleting file with UID %d: %s', $sysFileUid, $e->getMessage()));
        }
        if (!$isDeleted) {
            // Hard deletion of the file if normal deletion failed
            if ($file && $file->getStorage()->getDriverType() === 'Local') {
                $publicUrl = $file->getPublicUrl();
                $absolutePath = Environment::getPublicPath() . '/' . $publicUrl;
                if (file_exists($absolutePath)) {
                    @unlink($absolutePath);
                }
            }
            $connectionPool = $this->connectionPool;
            $fileTable = 'sys_file';
            $connection = $connectionPool->getConnectionForTable($fileTable);
            // Delete the file reference
            $connection->delete($fileTable, [
                'uid' => $sysFileUid,
            ]);
            $fileMetaDataTable = 'sys_file_metadata';
            $connection = $connectionPool->getConnectionForTable($fileMetaDataTable);
            // Delete the file reference
            $connection->delete($fileMetaDataTable, [
                'file' => $sysFileUid,
            ]);
        }
        return $isDeleted;
    }

    /**
     * Returns a valid file object based on the given UID.
     *
     * @param int $sysFileUid The UID of the system file to be validated, or null if not provided.
     * @return ?File A file object that is not deleted or missing, or null if the UID is invalid or the file is missing.
     */
    public function getValidFileOrNull(int $sysFileUid): ?File
    {
        if (!($sysFileUid > 0)) {
            return null;
        }
        try {
            $fileObject = $this->resourceFactory->getFileObject($sysFileUid);
            // Content is out of date if the local modification time is older than the remote timestamp.
            if (!$fileObject->isDeleted() && !$fileObject->isMissing()) {
                return $fileObject;
            }
        } catch (FileDoesNotExistException) {
            $this->logger->info(sprintf('Local file UID %d for import item is not found. Will attempt to fetch if URI exists.', $sysFileUid));
        }
        return null;
    }
}
