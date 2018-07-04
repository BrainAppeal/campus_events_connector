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
 * @since     2018-06-21
 */

namespace BrainAppeal\BrainEventConnector\Domain\Model;


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