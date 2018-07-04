<?php
/**
 * Mindbase 3
 *
 * PHP version 5.6
 *
 * @author    joshua.billert <joshua.billert@brain-appeal.com>
 * @copyright 2018 Brain Appeal GmbH (www.brain-appeal.com)
 * @license
 * @link      http://www.brain-appeal.com/
 * @since     2018-07-03
 */

namespace BrainAppeal\BrainEventConnector\Importer\DBAL;



use BrainAppeal\BrainEventConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\BrainEventConnector\Domain\Repository\AbstractImportedRepository;

class DBAL implements DBALInterface, \TYPO3\CMS\Core\SingletonInterface
{

    /**
     * @var AbstractImportedRepository[]
     */
    private $repositories = [];

    /**
     * @param string $modelClass
     * @return AbstractImportedRepository
     */
    private function getRepository($modelClass)
    {
        if (!isset($this->repositories[$modelClass])) {

            $repository = null;
            $repositoryClass = str_replace('\\Model\\', '\\Repository\\', $modelClass) . 'Repository';
            if (class_exists($repositoryClass)) {
                $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
                /** @var AbstractImportedRepository $repository */
                $repository = $objectManager->get($repositoryClass);
            }
            $this->repositories[$modelClass] = $repository;
        }

        return $this->repositories[$modelClass];
    }

    public function findByImport($modelClass, $importSource, $importId, $pid)
    {
        $repository = $this->getRepository($modelClass);
        if (null === $repository) {
            return null;
        }

        return $repository->findByImport($importSource, $importId, $pid);
    }

    /**
     * @param ImportedModelInterface[] $objects
     */
    public function updateObjects($objects)
    {
        foreach ($objects as $object) {
            $repository = $this->getRepository(get_class($object));

            $object->setImportedAt(time());
            if ($object->getUid() > 0) {
                $repository->update($object);
            } else {
                $repository->add($object);
            }
        }
        if (isset($repository)) {
            $repository->persistAll();
        }
    }

    public function removeNotUpdatedObjects($modelClass, $importSource, $pid, $importTimestamp)
    {
        $repository = $this->getRepository($modelClass);

        if (null !== $repository) {
            $results = $repository->findByNotImportedSince($importTimestamp, $importSource, $pid);
            foreach ($results as $result) {
                $repository->remove($result);
            }

            $repository->persistAll();
        }
    }
}