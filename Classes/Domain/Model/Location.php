<?php
namespace BrainAppeal\BrainEventConnector\Domain\Model;

/***
 *
 * This file is part of the "BrainEventConnector" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018
 *
 ***/

/**
 * Location
 */
class Location extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity implements ImportedModelInterface
{
    use ImportedModelTrait;

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * streetName
     *
     * @var string
     */
    protected $streetName = '';

    /**
     * town
     *
     * @var string
     */
    protected $town = '';

    /**
     * zipCode
     *
     * @var string
     */
    protected $zipCode = '';

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
     * Returns the streetName
     *
     * @return string $streetName
     */
    public function getStreetName()
    {
        return $this->streetName;
    }

    /**
     * Sets the streetName
     *
     * @param string $streetName
     * @return void
     */
    public function setStreetName($streetName)
    {
        $this->streetName = $streetName;
    }

    /**
     * Returns the town
     *
     * @return string $town
     */
    public function getTown()
    {
        return $this->town;
    }

    /**
     * Sets the town
     *
     * @param string $town
     * @return void
     */
    public function setTown($town)
    {
        $this->town = $town;
    }

    /**
     * Returns the zipCode
     *
     * @return string $zipCode
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * Sets the zipCode
     *
     * @param string $zipCode
     * @return void
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;
    }
}
