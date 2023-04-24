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


namespace BrainAppeal\CampusEventsConnector\Domain\Model;

/**
 * FilterCategory
 */
class FilterCategory extends AbstractImportedEntity
{

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * parent
     *
     * @var \BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory
     */
    protected $parent;

    /**
     * __construct
     */
    public function __construct()
    {
        //Do not remove the next line: It would break the functionality
        $this->initStorageObjects();
    }

    /**
     * Initializes all ObjectStorage properties
     * Do not modify this method!
     * It will be rewritten on each save in the extension builder
     * You may modify the constructor of this class instead
     *
     * @return void
     */
    protected function initStorageObjects()
    {

    }

    /**
     * Returns the name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the parent
     *
     * @return \BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory $parent
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets the parent
     *
     * @param \BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory|null $parent
     * @return void
     */
    public function setParent(\BrainAppeal\CampusEventsConnector\Domain\Model\FilterCategory $parent = null)
    {
        $this->parent = $parent;
    }
}
