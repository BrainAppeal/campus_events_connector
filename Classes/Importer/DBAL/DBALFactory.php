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
 * @since     2018-07-23
 */

namespace BrainAppeal\BrainEventConnector\Importer\DBAL;

class DBALFactory
{

    /**
     * @var \BrainAppeal\BrainEventConnector\Importer\DBAL\DBALInterface
     */
    protected static $instance;

    /**
     * @return \BrainAppeal\BrainEventConnector\Importer\DBAL\DBALInterface
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(\TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()) < 8000000) {
                self::$instance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('BrainAppeal\BrainEventConnector\Importer\DBAL\Compatibility7DBAL');
            } else {
                self::$instance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('BrainAppeal\BrainEventConnector\Importer\DBAL\DBAL');
            }
        }

        return self::$instance;
    }
}