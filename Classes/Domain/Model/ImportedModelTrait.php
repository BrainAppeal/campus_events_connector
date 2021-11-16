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
    protected $ceImportSource = '';

    /**
     * importId
     *
     * @var int
     */
    protected $ceImportId = 0;

    /**
     * importedAt
     *
     * @var int
     */
    protected $ceImportedAt = 0;

    /**
     * @return string
     */
    public function getCeImportSource()
    {
        return $this->ceImportSource;
    }

    /**
     * @param string $ceImportSource
     * @return ImportedModelInterface
     */
    public function setCeImportSource($ceImportSource)
    {
        $this->ceImportSource = $ceImportSource;

        return $this;
    }

    /**
     * @return int
     */
    public function getCeImportId()
    {
        return $this->ceImportId;
    }

    /**
     * @param int $ceImportId
     * @return ImportedModelInterface
     */
    public function setCeImportId($ceImportId)
    {
        $this->ceImportId = $ceImportId;

        return $this;
    }

    /**
     * @return int
     */
    public function getCeImportedAt()
    {
        return $this->ceImportedAt;
    }

    /**
     * @param int $ceImportedAt
     * @return ImportedModelInterface
     */
    public function setCeImportedAt($ceImportedAt)
    {
        $this->ceImportedAt = $ceImportedAt;

        return $this;
    }
}
