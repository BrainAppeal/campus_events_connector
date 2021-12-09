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

namespace BrainAppeal\CampusEventsConnector\Task;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;

/**
 * Base class for providers of additional fields
 */
abstract class AbstractAdditionalFieldProvider implements AdditionalFieldProviderInterface
{
    /**
     * Add a flash message
     *
     * @param string $message the flash message content
     * @param int $severity the flash message severity
     */
    protected function addMessage(string $message, int $severity = FlashMessage::OK): void
    {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, '', $severity);
        $service = GeneralUtility::makeInstance(FlashMessageService::class);
        $queue = $service->getMessageQueueByIdentifier();
        $queue->enqueue($flashMessage);
    }
}
