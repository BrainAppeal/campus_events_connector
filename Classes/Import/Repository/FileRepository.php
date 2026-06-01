<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Repository;

use BrainAppeal\CampusEventsConnector\Import\Event\FileDownloadFailedException;
use BrainAppeal\CampusEventsConnector\Import\Utility\CategoryUtility;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Mime\MimeTypes;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileRepository extends AbstractFileRepository
{
    /**
     * Local cache for allowed file extension for given table and field names
     * @var array<string, string[]>
     */
    protected array $localCacheAllowedFileTypes = [];

    private static bool $storageIndexUpdated = false;

    public function checkTargetFolderIsAccessible(string $targetFolderIdentifier): ?Folder
    {
        self::$storageIndexUpdated = false;
        try {
            return $this->initializeFolder($targetFolderIdentifier);
        } catch (FolderDoesNotExistException) {
            $this->logger->error(sprintf('The target folder identifier %s does not exist', $targetFolderIdentifier));
        }
        return null;
    }

    /**
     * @param string $targetFolderIdentifier
     * @return Folder
     * @throws FolderDoesNotExistException
     */
    public function getFolderByIdentifier(string $targetFolderIdentifier): Folder
    {
        return $this->initializeFolder($targetFolderIdentifier);
    }

    /**
     * Initializes a folder based on the given target folder identifier. If the folder does not exist,
     * it will attempt to create the folder. Handles errors and logs issues during the process.
     *
     * @param string $targetFolderIdentifier The identifier of the target folder to initialize.
     * @return Folder The initialized or newly created folder object.
     * @throws FolderDoesNotExistException If the folder cannot be found or created.
     * @throws \Exception If any unexpected error occurs during the folder initialization.
     */
    protected function initializeFolder(string $targetFolderIdentifier): Folder
    {
        try {
            $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($targetFolderIdentifier);
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (FolderDoesNotExistException $e) {
            $parts = GeneralUtility::trimExplode(':', $targetFolderIdentifier);
            if (count($parts) === 2) {
                /** @var StorageRepository $storageRepository */
                $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
                $storage = $storageRepository->findByCombinedIdentifier($targetFolderIdentifier);
                /** @noinspection NullPointerExceptionInspection */
                $folder = $storage->createFolder($parts[1]);
            } else {
                $this->logger->error(sprintf('The target folder identifier %s is invalid', $targetFolderIdentifier));
                throw $e;
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Initializing the target folder failed for %s: %s', $targetFolderIdentifier, $e->getMessage()));
            throw $e;
        }
        if (!self::$storageIndexUpdated) {
            $this->runStorageIndexing($folder->getStorage());
        }
        return $folder;
    }

    /**
     * Runs the storage indexing for the specified storage ID.
     *
     * @param ResourceStorage $storage The resource storage to index.
     */
    protected function runStorageIndexing(ResourceStorage $storage): void
    {
        // Only run this once
        if (self::$storageIndexUpdated) {
            return;
        }
        try {
            $currentEvaluatePermissionsValue = $storage->getEvaluatePermissions();
            $storage->setEvaluatePermissions(false);
            $indexer = $this->getIndexer($storage);
            $indexer->processChangesInStorages();
            $storage->setEvaluatePermissions($currentEvaluatePermissionsValue);
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Storage indexing failed for storage %d. Message: %s',
                $storage->getUid(),
                $e->getMessage()
            ));
        }
        self::$storageIndexUpdated = true;
    }

    /**
     * Gets the indexer
     *
     * @return \TYPO3\CMS\Core\Resource\Index\Indexer
     */
    protected function getIndexer(ResourceStorage $storage)
    {
        return GeneralUtility::makeInstance(Indexer::class, $storage);
    }

    /**
     * Downloads a file from a given URL and stores it in a temporary location.
     *
     * @param string $url URL of the file to be downloaded.
     * @param string[] $allowedExtensions Optional list of allowed file extensions
     * @param array $clientOptions
     * @param int $downloadAttemptCount
     * @return array{file: string, extension: string, mimeType: string|null}
     */
    public function downloadFile(
        string $url,
        array $allowedExtensions = [],
        array $clientOptions = [],
        int $downloadAttemptCount = 0
    ): array {
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
                    $waitForSeconds = $retryAfter + 3;
                    $this->logger->warning(sprintf('Rate limit exceeded for URL %s. Retrying in %d seconds.', $url, $waitForSeconds));
                    sleep($waitForSeconds);
                    return self::downloadFile($url, $allowedExtensions, $clientOptions, $downloadAttemptCount + 1);
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
        $mimeType = $response->getHeader('Content-Type')[0] ?? null;
        $extension = null;
        if ($mimeType) {
            $mimeTypes = new MimeTypes();
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
        return ['file' => $tempFile, 'mimeType' => $mimeType, 'extension' => $extension];
    }

    /**
     * Downloads a file from a given URL, stores it in a temporary location,
     * and moves it to the specified target folder in the File Abstraction Layer (FAL) with a given prefix.
     *
     * @param Folder $targetFolder The target folder where the downloaded file should be stored.
     * @param string $tempFile The path to the temporary file
     * @param string $filename The target filename
     * @param int|null $existingFileUid Optional id of an existing file to be replaced.
     * @return File The resulting file object created in the FAL storage.
     * @throws ExistingTargetFileNameException
     */
    public function saveTempFileToFal(
        Folder $targetFolder,
        string $tempFile,
        string $filename,
        ?int $existingFileUid
    ): File {
        $storage = $targetFolder->getStorage();
        // TYPO3 > 13
        if (class_exists(DuplicationBehavior::class)) {
            $conflictMode = DuplicationBehavior::REPLACE;
        } /** @noinspection PhpUndefinedClassInspection */ elseif (class_exists(\TYPO3\CMS\Core\Resource\DuplicationBehavior::class)) {
            // TYPO3 < 14
            $conflictMode = \TYPO3\CMS\Core\Resource\DuplicationBehavior::REPLACE;
        } else {
            throw new \RuntimeException('Unsupported TYPO3 version for file conflict handling', 1694575200);
        }
        if ($existingFileUid) {

            try {
                $fileObject = $this->resourceFactory->getFileObject($existingFileUid);
                if ($fileObject->getName() !== $filename) {
                    $storage->renameFile($fileObject, $filename, $conflictMode);
                }
                $storage->replaceFile($fileObject, $tempFile);
                // Delete the temporary file
                @unlink($tempFile);
                return $fileObject;
            } catch (\Throwable) {
            }
        }
        $file = $storage->addFile($tempFile, $targetFolder, $filename, $conflictMode);
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
     * @param int[]|null $categoryUidList Optional category UIDs to be added to the file's metadata.'
     */
    public function updateFileMetaData(File $file, array $metaDataValues, ?array $categoryUidList = null): void
    {
        if (empty($metaDataValues) && empty($categoryUidList)) {
            return;
        }
        $table = 'sys_file_metadata';
        $tableColumns = $this->getTableColumns($table);
        $metaDataAspect = $file->getMetaData();
        $hasChanges = false;
        if (!empty($categoryUidList)) {
            $metaDataValues['categories'] = count($categoryUidList);
        }
        foreach ($metaDataValues as $key => $value) {
            $column = $tableColumns[$key] ?? null;
            if ($column && $column->getLength() > 0) {
                $value = $this->ensureMaxBytes((string)$value, $column->getLength());
            }
            if (!$metaDataAspect->offsetExists($key) || $metaDataAspect->offsetGet($key) !== $value) {
                $metaDataAspect->offsetSet($key, $value);
                $hasChanges = true;
            }
        }
        if ($hasChanges) {
            try {
                $metaDataAspect->save();
            } catch (\Throwable $e) {
                $this->logger->error(sprintf('Saving the file metadata failed for %s: %s (%s:%s; %s)', $file->getPublicUrl(), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()), ['metadata' => $metaDataAspect->get()]);
            }
        }
        if (!empty($categoryUidList)) {
            foreach ($categoryUidList as $categoryUid) {
                CategoryUtility::addReference($file->getUid(), $categoryUid, $table, 'categories');
            }
        }
    }

    /**
     * Returns the list of allowed file extension for the given table and field.
     * Returns an empty array if no restrictions exist
     *
     * @return string[]
     */
    public function getAllowedFileExtensionsForTableAndField(string $table, string $field): array
    {
        $cacheKey = $table . '-' . $field;
        if (!array_key_exists($cacheKey, $this->localCacheAllowedFileTypes)) {
            $allowedTypesGroup = $GLOBALS['TCA'][$table]['columns'][$field]['config']['allowed'] ?? null;
            $returnValue = [];
            if ($allowedTypesGroup) {
                if (is_array($allowedTypesGroup)) {
                    $returnValue = $allowedTypesGroup;
                } elseif ((string)$allowedTypesGroup !== '') {
                    $returnValue = GeneralUtility::trimExplode(',', $allowedTypesGroup);
                }

                if (!empty($returnValue)) {
                    $returnValue = array_unique(array_map('strtolower', $returnValue));
                }
            }
            $this->localCacheAllowedFileTypes[$cacheKey] = $returnValue;
        }
        return $this->localCacheAllowedFileTypes[$cacheKey];
    }
}
