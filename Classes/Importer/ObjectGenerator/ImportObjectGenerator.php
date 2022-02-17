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

namespace BrainAppeal\CampusEventsConnector\Importer\ObjectGenerator;

use BrainAppeal\CampusEventsConnector\Domain\Model\Category;
use BrainAppeal\CampusEventsConnector\Domain\Model\Event;
use BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory;
use BrainAppeal\CampusEventsConnector\Domain\Model\ImportedModelInterface;
use BrainAppeal\CampusEventsConnector\Domain\Model\Location;
use BrainAppeal\CampusEventsConnector\Domain\Model\Organizer;
use BrainAppeal\CampusEventsConnector\Domain\Model\Speaker;
use BrainAppeal\CampusEventsConnector\Domain\Model\TargetGroup;
use BrainAppeal\CampusEventsConnector\Domain\Model\TimeRange;
use BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALInterface;
use TYPO3\CMS\Core\SingletonInterface;

abstract class ImportObjectGenerator implements SingletonInterface
{
    const UNIX_TIMESTAMP_MAX = 2147483647;

    /**
     * @var string
     */
    private $importSource = null;

    /**
     * @var int
     */
    private $pid = null;

    /**
     * @var DBALInterface
     */
    private $dbal = null;

    /**
     * @var array
     */
    private $objects;

    /**
     * @var bool
     */
    private $dataChanged = false;

    /**
     * @return DBALInterface
     */
    private function getDBAL()
    {
        if (null === $this->dbal) {
            $this->dbal = \BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALFactory::getInstance();
        }

        return $this->dbal;
    }

    public function init($importSource, $pid)
    {
        $this->importSource = $importSource;
        $this->pid = (int)$pid;
        $this->objects = [];
    }

    /**
     * @return string[]
     */
    public function getModifiedObjectClasses()
    {
        return array_keys($this->objects);
    }

    /**
     * @param string $class
     * @param array $dataArray
     * @return ImportedModelInterface[]
     */
    public function generateMultiple($class, $dataArray)
    {
        $objects = [];
        if(!empty($dataArray)) {
            foreach ($dataArray as $data) {
                if (is_array($data)) {
                    $object = $this->generate($class, $data['id'], $data);
                } else {
                    $object = $this->generate($class, $data, null);
                }
                if (null !== $object) {
                    $objects[] = $object;
                }
            }
        }

        return $objects;
    }

    /**
     * @param string $class
     * @param int $importId
     * @param array $data
     * @return ImportedModelInterface
     */
    public function generate($class, $importId, $data = null)
    {
        if (empty($importId)) {
            return null;
        }

        $importId = (int)$importId;
        $importSource = $this->importSource;
        $pid = $this->pid;

        if (!isset($this->objects[$class])) {
            $this->objects[$class] = [];
        }
        if (!isset($this->objects[$class][$importId])) {

            $object = $this->getDBAL()->findByImport($class, $importSource, $importId, $pid);

            if (null === $object) {
                /** @var ImportedModelInterface $object */
                $object = new $class;
                $object->setCeImportId($importId);
                $object->setCeImportSource($importSource);
                $object->setPid($pid);
            }
            $this->objects[$class][$importId] = $object;

        }

        $object = $this->objects[$class][$importId];
        if (isset($data)) {
            $object->setCeImportedAt(time());
            $this->assignClassSpecificProperties($class, $object, $data);
        }

        return $object;
    }

    /**
     * @param string $class
     * @param ImportedModelInterface $object
     * @param array $data
     */
    protected function assignClassSpecificProperties($class, $object, $data)
    {
        switch ($class) {
            case Category::class:
                $this->assignCategoryProperties($class, $object, $data);
                break;

            case Event::class:
                $this->assignEventProperties($class, $object, $data);
                break;

            case FilterCategory::class:
                $this->assignFilterCategoryProperties($class, $object, $data);
                break;

            case Location::class:
                $this->assignLocationProperties($class, $object, $data);
                break;

            case Organizer::class:
                $this->assignOrganizerProperties($class, $object, $data);
                break;

            case Speaker::class:
                $this->assignSpeakerProperties($class, $object, $data);
                break;

            case TargetGroup::class:
                $this->assignTargetGroupProperties($class, $object, $data);
                break;

            case TimeRange::class:
                $this->assignTimeRangeProperties($class, $object, $data);
                break;
        }
    }

    protected function setDataChanged()
    {
        $this->dataChanged = true;
    }

    /**
     * @return bool
     */
    public function getDataChanged()
    {
        return $this->dataChanged;
    }

    /**
     * @param string $class
     * @param ImportedModelInterface|Category $object
     * @param array $data
     */
    protected abstract function assignCategoryProperties($class, $object, $data);

    /**
     * @param string $class
     * @param ImportedModelInterface|Event $object
     * @param array $data
     */
    protected abstract function assignEventProperties($class, $object, $data);

    /**
     * @param string $class
     * @param ImportedModelInterface|FilterCategory $object
     * @param array $data
     */
    protected abstract function assignFilterCategoryProperties($class, $object, $data);

    /**
     * @param string $class
     * @param ImportedModelInterface|Location $object
     * @param array $data
     */
    protected abstract function assignLocationProperties($class, $object, $data);

    /**
     * @param string $class
     * @param ImportedModelInterface|Organizer $object
     * @param array $data
     */
    protected abstract function assignOrganizerProperties($class, $object, $data);

    /**
     * @param string $class
     * @param ImportedModelInterface|Speaker $object
     * @param array $data
     */
    protected abstract function assignSpeakerProperties($class, $object, $data);

    /**
     * @param string $class
     * @param ImportedModelInterface|TargetGroup $object
     * @param array $data
     */
    protected abstract function assignTargetGroupProperties($class, $object, $data);

    /**
     * @param string $class
     * @param ImportedModelInterface|TimeRange $object
     * @param array $data
     */
    protected abstract function assignTimeRangeProperties($class, $object, $data);
}
