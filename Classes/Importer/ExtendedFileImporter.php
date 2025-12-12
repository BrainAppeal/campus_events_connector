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

namespace BrainAppeal\CampusEventsConnector\Importer;

use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExtendedFileImporter extends AbstractFileImporter implements SingletonInterface
{

    /**
     * @var string
     */
    private $baseUri;

    /**
     * @param int $storageId
     * @param string $storageFolder
     * @param string $baseUri
     */
    public function initialize($storageId, $storageFolder, $baseUri)
    {
        $this->storageId = $storageId;
        $this->storageFolder = $storageFolder;
        $this->baseUri = $baseUri;
        DBALFactory::getInstance()->fixImportSourceNames('sys_file_reference', $baseUri);
    }

    /**
     * @param ImportedModelInterface $object
     * @param string $property
     * @param array $data
     * @param string $tempFilenameAndPath
     * @param string $url
     */
    private function addToQueue(ImportedModelInterface $object, string $property, array $data, string $tempFilenameAndPath, string $url, string $targetFileName): void
    {
        $importId = (int) (!empty($data['id']) ? $data['id'] : ($object->getCeImportId() ?: $object->getUid()));
        $this->newReferenceQueue[] = [
            'object' => $object,
            'property' => $property,
            'data' => $data,
            'download' => [
                'file' => $tempFilenameAndPath,
                'url' => $url,
            ],
            'import_id' => $importId,
            'target_file_name' => $targetFileName,
        ];
        $this->mappingOfUsedFileNamesToReferenceUid[$targetFileName] = null;

    }

    /**
     * @param ImportedModelInterface $object
     * @param string $property
     * @param array<string, mixed> $data
     */
    public function enqueueFileMapping(ImportedModelInterface $object, string $property, array $data)
    {
        if (empty($data['url']) || empty($this->baseUri)) {
            return;
        }
        $importId = (int) (!empty($data['id']) ? $data['id'] : ($object->getCeImportId() ?: $object->getUid()));
        $fileBaseName = basename((string) $data['url']);
        $targetFileName = $this->getImportFileName($importId, $fileBaseName);
        if (!$targetFileName) {
            return;
        }
        $existingReference = $this->getFileReferenceIfExists($object, $property, $targetFileName);

        $fileExists = null !== $existingReference && $this->originalResourceIsValid($existingReference)
            && $existingReference->getOriginalResource()->getName() === $targetFileName;
        if ($fileExists) {
            $fileReferenceUid = $existingReference->getOriginalResource()->getUid();
            $this->updateReferenceIds[$fileReferenceUid] = $fileReferenceUid;
            $this->mappingOfUsedFileNamesToReferenceUid[$targetFileName] = $fileReferenceUid;
        } else {
            if ($existingReference) {
                $this->deleteObsoleteFileReferenceOnImport($existingReference);
            }
            $tempFilenameAndPath = $this->getTempFilePath();
            $downloadUrl = rtrim($this->baseUri, '/') . '/' . ltrim((string) $data['url'], '/');
            $this->addToQueue($object, $property, $data, $tempFilenameAndPath, $downloadUrl, $targetFileName);
        }
    }

    /**
     * @param array<string, mixed> $queueEntry
     * @return string|null
     */
    protected function getDownloadFromQueueEntry(array $queueEntry): ?string
    {
        $downloadUrl = $queueEntry['download']['url'];
        $downloadFile = $queueEntry['download']['file'];
        $fileContent = GeneralUtility::getUrl($downloadUrl);
        if (false !== $fileContent) {
            file_put_contents($downloadFile, $fileContent);
            return $downloadFile;
        }

        return null;
    }

}
