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
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExtendedFileImporter extends AbstractFileImporter implements \TYPO3\CMS\Core\SingletonInterface
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
    }

    /**
     * @param ImportedModelInterface $object
     * @param string $property
     * @param array $data
     * @param string $tempFilenameAndPath
     * @param string $url
     */
    private function addToQueue($object, $property, $data, $tempFilenameAndPath, $url, $targetFileName)
    {
        $importId = (int) (!empty($data['id']) ? $data['id'] : $object->getUid());
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
    }

    /**
     * @param ImportedModelInterface $object
     * @param string $property
     * @param array $data
     */
    public function enqueueFileMapping($object, $property, $data)
    {
        if (empty($data['url']) || empty($this->baseUri)) {
            return;
        }

        $importId = (int) (!empty($data['id']) ? $data['id'] : $object->getUid());
        $fileBaseName = basename($data['url']);
        $targetFileName = $this->getImportFileName($importId, $fileBaseName);
        $existingReference = $this->getFileReferenceIfExists($object, $property, $targetFileName);

        if (null !== $existingReference) {
            $fileReferenceUid = $existingReference->getOriginalResource()->getUid();
            $this->updateReferenceIds[$fileReferenceUid] = $fileReferenceUid;
        } else {
            $tempFilenameAndPath = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('tx_campuseventsconnector_');
            $downloadUrl = rtrim($this->baseUri, '/') . '/' . ltrim($data['url'], '/');
            $this->addToQueue($object, $property, $data, $tempFilenameAndPath, $downloadUrl, $targetFileName);
        }
    }

    /**
     * @param array $queueEntry
     * @return string|null
     */
    protected function getDownloadFromQueueEntry($queueEntry)
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
