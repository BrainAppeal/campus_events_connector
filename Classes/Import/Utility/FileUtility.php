<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Utility;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use BrainAppeal\CampusEventsConnector\Import\Event\FileDownloadFailedException;
use BrainAppeal\CampusEventsConnector\Import\Model\ImportFileReferenceModel;
use Symfony\Component\Mime\MimeTypes;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Utility functions for files
 */
class FileUtility
{
    /**
     * Executes a file reference query with additional file information (SELECT with JOIN) and returns a Result.
     * @param string $table
     * @param array $recordIdList
     * @param int $languageUid
     * @return Result
     */
    public static function getFileReferencesForTableRecordList(string $table, array $recordIdList, int $languageUid = 0): Result
    {
        $refTable = 'sys_file_reference';
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($refTable);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder->select(
            'r.uid',
            'r.pid',
            'r.tablenames',
            'r.fieldname',
            'r.uid_foreign',
            'r.sorting_foreign',
            'r.uid_local',
            'r.hidden',
            'r.alternative',
            'r.sys_language_uid',
            'r.l10n_parent',
            'f.identifier',
            'f.missing',
            'f.name AS file_name',
            'f.sha1 as file_sha1',
            'f.size as file_size',
            'f.modification_date AS file_modification_date'
        )
            ->from($refTable, 'r')
            ->leftJoin('r', 'sys_file', 'f', 'f.uid = r.uid_local')
            ->where(
                $queryBuilder->expr()->eq(
                    'r.tablenames',
                    $queryBuilder->createNamedParameter($table, Connection::PARAM_STR)
                )
            )
            ->andWhere(
                $queryBuilder->expr()->eq(
                    'r.sys_language_uid',
                    $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT)
                )
            );
        if (!empty($recordIdList)) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->in(
                        'uid_foreign',
                        $queryBuilder->createNamedParameter($recordIdList, ArrayParameterType::INTEGER)
                    )
                );
        }
        $queryBuilder->orderBy('r.uid_foreign', 'ASC');
        $queryBuilder->addOrderBy('r.sorting_foreign', 'ASC');
        return $queryBuilder->executeQuery();
    }

    /**
     * Deletes a file reference and safely decrements the corresponding reference counter field of a record.
     *
     * This method removes the given file reference (from sys_file_reference) and updates the specified
     * reference count field in the associated table. The counter is decremented by one but never below zero.
     *
     * @param ImportFileReferenceModel $fileReferenceModel
     */
    public static function deleteFileReference(ImportFileReferenceModel $fileReferenceModel): void
    {
        $table = $fileReferenceModel->getTablenames();
        $fieldName = $fileReferenceModel->getFieldname();
        $fileReferenceUid = $fileReferenceModel->getUid();
        $uid = $fileReferenceModel->getUid();
        $refTable = 'sys_file_reference';
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable($refTable);
        // Delete the file reference
        $connection->delete($refTable, [
            'uid' => $fileReferenceUid,
        ]);
        // Decrease the reference count
        /** @noinspection SqlResolve */
        $sql = sprintf(
            'UPDATE %s SET %s = GREATEST(%s - 1, 0) WHERE uid = :uid',
            $table,
            $fieldName,
            $fieldName
        );
        try {
            $connection->executeStatement($sql, ['uid' => $uid]);
        } catch (Exception) {
            // Error is ignored because it has no consequences
        }
    }

    /**
     * Downloads a file from a given URL, stores it in a temporary location,
     * and moves it to the specified target folder in the File Abstraction Layer (FAL) with a given prefix.
     *
     * @param Folder $targetFolder The target folder where the downloaded file should be stored.
     * @param string $url URL of the file to be downloaded.
     * @param string $filenamePrefix Prefix to be used for the generated file's name.
     * @param array<string, string>|null $metaData
     * @param string[] $allowedExtensions Optional list of allowed file extensions
     * @param array $clientOptions
     * @param int $downloadAttemptCount The number of download attempts in case there are problems
     * @return ?File The resulting file object created in the FAL storage.
     * @throws ExistingTargetFileNameException
     */
    public static function downloadFileToFal(
        Folder $targetFolder,
        string $url,
        string $filenamePrefix,
        ?array $metaData = null,
        array $allowedExtensions = [],
        array $clientOptions = [],
        int $downloadAttemptCount = 0
    ): ?File
    {
        $tempFile = GeneralUtility::tempnam('import_');

        $client = GeneralUtility::makeInstance(Client::class, $clientOptions);
        try {
            $response = $client->get($url, ['sink' => $tempFile]);
        } catch (GuzzleException $e) {
            $keepExistingFile = true;
            $statusCode = $e->getCode();
            // Add detailed information about the Request and Response (if available).
            if (($e instanceof RequestException) && $e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                if ($downloadAttemptCount < 3 && $response->getStatusCode() === 429) {
                    $retryAfter = (int)($response->getHeader('Retry-After')[0] ?? 5);
                    if ($retryAfter < 5) {
                        $retryAfter = 5;
                    }
                    sleep($retryAfter + 3);
                    return self::downloadFileToFal($targetFolder, $url, $filenamePrefix, $metaData, $allowedExtensions, $clientOptions, $downloadAttemptCount + 1);
                }
                $keepExistingFile = $response->getStatusCode() !== 404;
            }
            $message = sprintf('Download of %s failed: %s', $url, $e->getMessage());
            throw new FileDownloadFailedException($message, $keepExistingFile, $statusCode, $e);
        } catch (\Throwable $e) {
            $message = sprintf('An unexpected error occurred during attempted file download of %s: %s', $url, $e->getMessage());
            throw new FileDownloadFailedException($message, true, $e->getCode(), $e);
        }

        if ($response->getStatusCode() !== 200) {
            $keepExistingFile = $response->getStatusCode() !== 404;
            $message = sprintf('Download of %s failed: %s', $url, $response->getReasonPhrase());
            throw new FileDownloadFailedException($message, $keepExistingFile, $response->getStatusCode());
        }
        $mimeTypes = new MimeTypes();
        $mimeType = $response->getHeader('Content-Type')[0] ?? null;
        $extension = null;
        if ($mimeType) {
            if (str_contains($mimeType, ';')) {
                $mimeType = explode(';', $mimeType)[0];
            }
            $extensions = $mimeTypes->getExtensions($mimeType);
            if (!empty($extensions[0])) {
                $extension = $extensions[0];
            }
        }
        if (!$extension) {
            @unlink($tempFile);
            $message = sprintf('The file extension cannot be determined for the url: %s (Response content type: %s)', $url, $mimeType);
            throw new FileDownloadFailedException($message, true);
        }
        if (!empty($allowedExtensions) && !in_array($extension, $allowedExtensions, true)) {
            @unlink($tempFile);
            $message = sprintf('The file extension %s for the url %s is not allowed (%s)', $extension, $url, implode(', ', $allowedExtensions));
            throw new FileDownloadFailedException($message, false);
        }
        $filename = $filenamePrefix . '.' . $extension;
        $storage = $targetFolder->getStorage();
        $file = $storage->addFile($tempFile, $targetFolder, $filename, DuplicationBehavior::REPLACE);
        if ($metaData) {
            self::updateFileMetaData($file, $metaData);
        }
        /** @var File $file */

        // Delete the temporary file
        @unlink($tempFile);
        return $file;
    }

    /**
     * Updates the metadata for a specified file with the provided metadata values.
     *
     * @param File $file The file object whose metadata is to be updated.
     * @param array $metaDataValues An associative array of metadata keys and corresponding values to update.
     */
    public static function updateFileMetaData(File $file, array $metaDataValues): void
    {
        $metaDataAspect = $file->getMetaData();
        foreach ($metaDataValues as $key => $value) {
            $metaDataAspect->offsetSet($key, $value);
        }
        $metaDataAspect->save();
    }

    /**
     * Saves a file reference to the database using the provided file reference model information.
     *
     * @param ImportFileReferenceModel $fileReferenceModel The model containing the file reference details,
     * such as table names, field names, record ID, PID, and file UID.
     * @return bool Returns true if the file reference was saved without errors, otherwise false.
     */
    public static function saveFileReference(ImportFileReferenceModel $fileReferenceModel): bool
    {
        $table = $fileReferenceModel->getTablenames();
        $fieldName = $fileReferenceModel->getFieldname();
        $recordId = $fileReferenceModel->getUidForeign();
        $pid = $fileReferenceModel->getPid();
        $fileUid = $fileReferenceModel->getUidLocal();
        $existingFileReferenceUid = $fileReferenceModel->getUid();
        // Assemble DataHandler data
        $referenceId = $existingFileReferenceUid ?: StringUtility::getUniqueId('NEW'); // random string prefixed with NEW
        $data = [];
        $fileReferenceRecord = [
            'uid_local' => $fileUid,
            'tablenames' => $table,
            'uid_foreign' => $recordId,
            'fieldname' => $fieldName,
            'hidden' => 0,
            'pid' => $pid,
            // The alternative text is already saved in the file meta-data
            //'alternative' => $alternative,
        ];
        $data['sys_file_reference'][$referenceId] = $fileReferenceRecord;
        $data[$table][$recordId] = [
            'pid' => $pid,
            $fieldName => $referenceId, // For multiple new references $referenceId is a comma-separated list
        ];
        /** @var DataHandler $dataHandler */
        // Do not inject or reuse the DataHandler as it holds state!
        // Do not use `new` as GeneralUtility::makeInstance handles dependencies
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);

        // Process the DataHandler data
        $dataHandler->start($data, []);
        $dataHandler->enableLogging = false;
        $dataHandler->process_datamap();

        return $dataHandler->errorLog === [];
    }

    /**
     * Handles the deletion of a single file reference if its associated local file is missing or removed.
     *
     * @param ImportFileReferenceModel $fileReferenceModel
     *        Mapping info for a file reference (field name, table, target record UID)
     * @return bool
     */
    public static function deleteFileAndFileReference(ImportFileReferenceModel $fileReferenceModel): bool
    {
        $isDeleted = false;
        // Check mtime of the local file
        $sysFileUid = $fileReferenceModel->getUidLocal();
        try {
            $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
            $file = $resourceFactory->getFileObject($sysFileUid);
            $isDeleted = $file->delete();
        } catch (FileDoesNotExistException) {
            // Nothing needs to be done, since we want the file to not exist
        }
        self::deleteFileReference($fileReferenceModel);
        return $isDeleted;
    }

    /**
     * Returns the list of allowed file extension for the given table and field.
     * Returns an empty array if no restrictions exist
     *
     * @return string[]
     */
    public static function getAllowedFileExtensionsForTableAndField(string $table, string $field): array
    {
        $allowedTypesGroup = $GLOBALS['TCA'][$table]['columns'][$field]['config']['allowed'] ?? null;
        if ($allowedTypesGroup) {
            return array_unique(GeneralUtility::trimExplode(',', $allowedTypesGroup, true));
        }
        return [];
    }
}
