<?php

declare(strict_types=1);

/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2026 Brain Appeal GmbH
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
     */
    public function setName($name): void
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
     */
    public function setStreetName($streetName): void
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
     */
    public function setTown($town): void
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
     */
    public function setZipCode($zipCode): void
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
    public function setListViewDisplayName($listViewDisplayName): void
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
    public function setBuilding($building): void
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
    public function setRoom($room): void
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
    public function setLongitude($longitude): void
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
    public function setLatitude($latitude): void
    {
        $this->latitude = $latitude;
    }
}
