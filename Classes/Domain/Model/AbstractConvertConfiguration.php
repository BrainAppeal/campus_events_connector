<?php
namespace BrainAppeal\BrainEventConnector\Domain\Model;

/***
 *
 * This file is part of the "BrainEventConvert2News" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018
 *
 ***/

/**
 * AbstractConvertConfiguration
 */
abstract class AbstractConvertConfiguration extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * targetPid
     *
     * @var int
     */
    protected $targetPid = 0;

    /**
     * Returns the targetPid
     *
     * @return int $targetPid
     */
    public function getTargetPid()
    {
        return $this->targetPid;
    }

    /**
     * Sets the targetPid
     *
     * @param int $targetPid
     * @return void
     */
    public function setTargetPid($targetPid)
    {
        $this->targetPid = $targetPid;
    }
}
