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


trait ImportedModelTrait
{

    /**
     * importSource
     *
     * @var string
     */
    protected $importSource = '';

    /**
     * importId
     *
     * @var int
     */
    protected $importId = 0;

    /**
     * importedAt
     *
     * @var int
     */
    protected $importedAt = 0;

    /**
     * @return string
     */
    public function getImportSource()
    {
        return $this->importSource;
    }

    /**
     * @param string $importSource
     * @return ImportedModelTrait
     */
    public function setImportSource($importSource)
    {
        $this->importSource = $importSource;

        return $this;
    }

    /**
     * @return int
     */
    public function getImportId()
    {
        return $this->importId;
    }

    /**
     * @param int $importId
     * @return ImportedModelTrait
     */
    public function setImportId($importId)
    {
        $this->importId = $importId;

        return $this;
    }

    /**
     * @return int
     */
    public function getImportedAt()
    {
        return $this->importedAt;
    }

    /**
     * @param int $importedAt
     * @return ImportedModelTrait
     */
    public function setImportedAt($importedAt)
    {
        $this->importedAt = $importedAt;

        return $this;
    }
}
