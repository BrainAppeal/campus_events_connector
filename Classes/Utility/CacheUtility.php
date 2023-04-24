<?php
/**
 * campus_events_connector comes with ABSOLUTELY NO WARRANTY
 * See the GNU GeneralPublic License for more details.
 * https://www.gnu.org/licenses/gpl-2.0
 *
 * Copyright (C) 2022 Brain Appeal GmbH
 *
 * @copyright 2022 Brain Appeal GmbH (www.brain-appeal.com)
 * @license   GPL-2 (www.gnu.org/licenses/gpl-2.0)
 * @link      https://www.campus-events.com/
 */

namespace BrainAppeal\CampusEventsConnector\Utility;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CacheUtility
{
    private const CACHE_TAG = 'tx_campus_events';

    /**
     * Clear the cache for the given page id and the cache tag "tx_campus_events"
     * @param int $pid
     */
    public function clearCacheForPage(int $pid): void
    {
        $pageIdsToClear[$pid] = $pid;

        $pageTS = \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($pid);
        if (isset($pageTS['TCEMAIN.']['clearCacheCmd'])) {
            $clearCacheCommands = GeneralUtility::trimExplode(',', strtolower($pageTS['TCEMAIN.']['clearCacheCmd']), true);
            $clearCacheCommands = array_unique($clearCacheCommands);
            foreach ($clearCacheCommands as $clearCacheCommand) {
                if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($clearCacheCommand)) {
                    $pageIdsToClear[$clearCacheCommand] = $clearCacheCommand;
                }
            }
        }
        $cacheService = $this->getCacheService();
        $cacheService->clear_cacheCmd('cacheTag:' . self::CACHE_TAG);
        foreach ($pageIdsToClear as $ccPid) {
            $cacheService->clear_cacheCmd($ccPid);
        }
    }

    /**
     * @return DataHandler
     */
    private function getCacheService(): DataHandler
    {
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], []);

        return $dataHandler;
    }
}
