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

use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

interface ImportedModelInterface extends DomainObjectInterface
{
    /**
     * @return string
     */
    public function getCeImportSource();

    /**
     * @param string $importSource
     * @return ImportedModelInterface
     */
    public function setCeImportSource($importSource);

    /**
     * @return int
     */
    public function getCeImportId();

    /**
     * @param int $importId
     * @return ImportedModelInterface
     */
    public function setCeImportId($importId);

    /**
     * @return int
     */
    public function getCeImportedAt();

    /**
     * @param int $importedAt
     * @return ImportedModelInterface
     */
    public function setCeImportedAt($importedAt);
}
