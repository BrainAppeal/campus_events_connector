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
 * Location
 */
class Location extends AbstractImportedEntity
{

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
     * listViewDisplayName
     *
     * @var string
     */
    protected $listViewDisplayName = '';

    /**
     * building
     *
     * @var string
     */
    protected $building = '';

    /**
     * room
     *
     * @var string
     */
    protected $room = '';

    /**
     * longitude
     *
     * @var string
     */
    protected $longitude = '';

    /**
     * latitude
     *
     * @var string
     */
    protected $latitude = '';

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

    /**
     * @return string
     */
    public function getListViewDisplayName()
    {
        return $this->listViewDisplayName;
    }

    /**
     * @param string $listViewDisplayName
     */
    public function setListViewDisplayName($listViewDisplayName)
    {
        $this->listViewDisplayName = $listViewDisplayName;
    }

    /**
     * @return string
     */
    public function getBuilding()
    {
        return $this->building;
    }

    /**
     * @param string $building
     */
    public function setBuilding($building)
    {
        $this->building = $building;
    }

    /**
     * @return string
     */
    public function getRoom()
    {
        return $this->room;
    }

    /**
     * @param string $room
     */
    public function setRoom($room)
    {
        $this->room = $room;
    }

    /**
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param string $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    /**
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param string $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }
}
