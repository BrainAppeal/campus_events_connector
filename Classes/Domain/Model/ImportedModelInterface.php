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

namespace BrainAppeal\CampusEventsConnector\Domain\Model;


use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

interface ImportedModelInterface extends DomainObjectInterface
{
    /**
     * @return string
     */
    public function getImportSource();

    /**
     * @param string $importSource
     * @return ImportedModelInterface
     */
    public function setImportSource($importSource);

    /**
     * @return int
     */
    public function getImportId();

    /**
     * @param int $importId
     * @return ImportedModelInterface
     */
    public function setImportId($importId);

    /**
     * @return int
     */
    public function getImportedAt();

    /**
     * @param int $importedAt
     * @return ImportedModelInterface
     */
    public function setImportedAt($importedAt);
}