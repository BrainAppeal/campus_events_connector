<?php
namespace BrainAppeal\BrainEventConnector\Domain\Model;

/***
 *
 * This file is part of the "BrainAppeal CampusEvents Connector" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Joshua Billert <joshua.billert@brain-appeal.com>, Brain Appeal GmbH
 *
 ***/

/**
 * FilterCategory
 */
class FilterCategory extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity implements ImportedModelInterface
{
    use ImportedModelTrait;

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * parent
     *
     * @var \BrainAppeal\BrainEventConnector\Domain\Model\FilterCategory
     */
    protected $parent = null;

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
     * @return \BrainAppeal\BrainEventConnector\Domain\Model\FilterCategory $parent
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets the parent
     *
     * @param \BrainAppeal\BrainEventConnector\Domain\Model\FilterCategory|null $parent
     * @return void
     */
    public function setParent(\BrainAppeal\BrainEventConnector\Domain\Model\FilterCategory $parent = null)
    {
        $this->parent = $parent;
    }
}
