<?php

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

namespace BrainAppeal\CampusEventsConnector\Task;

use BrainAppeal\CampusEventsConnector\CeImport\DataCollection\CeApiConnector;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Additional BE fields for ip address anonymization task.
 */
trait EventImportValidationTrait
{
    protected const LL_PREFIX = 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask';

    protected function getFieldValue(array $parameters, string $fieldNameWithoutPrefix, mixed $defaultValue = null, ?string $alternativOldFieldName = null): mixed
    {
        $prefix = 'campusEventsConnector_eventImport_';
        $tcaPrefix = 'ce_event_import_';
        // Handle migration: check old parameter names first, then new TCA field names
        $oldFieldName = $alternativOldFieldName ?? lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $fieldNameWithoutPrefix))));
        return $parameters[$prefix . $oldFieldName] ?? $parameters[$tcaPrefix . $fieldNameWithoutPrefix] ?? $defaultValue;
    }

    /**
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    protected function validateBaseUriAndApiKeyAdditionalField(array $submittedData): bool
    {
        $validData = true;
        $baseUri = $this->getFieldValue($submittedData, 'base_uri', '');
        if (empty($baseUri)) {
            $this->addTranslatableMessage(self::LL_PREFIX . '.error.invalid_base_uri');
            $validData = false;
        }
        $uriInfo = parse_url((string)$baseUri);
        if (!$uriInfo || empty($uriInfo['host']) || trim($uriInfo['path'] ?? '', '/') !== '' || !in_array($uriInfo['scheme'] ?? null, ['http', 'https'])) {
            $this->addTranslatableMessage(self::LL_PREFIX . '.error.invalid_base_uri');
            return false;
        }
        $apiKey = $this->getFieldValue($submittedData, 'api_key');
        if (empty($apiKey) || preg_match('/^[\w]{8}-[\w]{16}-[\w]{8}$/', (string)$apiKey) !== 1) {
            $this->addTranslatableMessage(self::LL_PREFIX . '.error.invalid_api_key');
            $validData = false;
        }

        if ($validData) {
            if ($baseUri !== EventImportTask::BASE_URI_DEFAULT) {
                $apiConnector = $this->initializeApiConnector($baseUri, $apiKey);
                $validData = $apiConnector->checkApiAccess();
            } else {
                $validData = false;
            }
            if (!$validData) {
                $this->addTranslatableMessage(self::LL_PREFIX . '.error.invalid_base_uri');
            }
        }

        return $validData;
    }

    /**
     * @param string $baseUri Tha base uri for the api
     * @param string $apiKey The api key
     * @return CeApiConnector
     */
    private function initializeApiConnector(string $baseUri, string $apiKey): CeApiConnector
    {
        /** @var CeApiConnector $apiConnector */
        $apiConnector = GeneralUtility::makeInstance(CeApiConnector::class);
        $apiConnector->setBaseUrl($baseUri);
        $apiConnector->setApiKey($apiKey);
        return $apiConnector;
    }

    /**
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    protected function validatePidAdditionalField(array $submittedData): bool
    {
        $validData = false;
        $data = $this->getFieldValue($submittedData, 'pid', null);
        if ($data && str_starts_with((string)$data, 'pages_')) {
            $data = (int)substr($data, 6);
        }
        if (empty($data) || is_numeric($data)) {
            $validData = $this->checkIfPidIsValid($data);
        }
        if (!$validData) {
            // Issue error message
            $this->addTranslatableMessage(self::LL_PREFIX . '.error.invalid_pid');
        }
        return $validData;
    }

    /**
     * @param int $pid
     * @return bool
     */
    private function checkIfPidIsValid(int $pid): bool
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->resetRestrictions();
        $pageRowOrNull = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', (int)$pid))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        return !empty($pageRowOrNull) && (int)$pageRowOrNull['uid'] === $pid;
    }

    /**
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    protected function validateStorageIdAdditionalField(array $submittedData): bool
    {
        $validData = false;
        $data = (int)($submittedData['campusEventsConnector_eventImport_storageId'] ?? $submittedData['file_storage'] ?? 0);
        if ($data >= 0) {
            $validData = true;
        } else {
            // Issue error message
            $this->addTranslatableMessage(self::LL_PREFIX . '.error.invalid_storage_id');
        }
        return $validData;
    }

    /**
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    protected function validateStorageFolderAdditionalField(array $submittedData): bool
    {
        $validData = false;
        $data = $this->getFieldValue($submittedData, 'storage_folder', null);
        if (!empty($data)) {
            $validData = true;
        } else {
            // Issue error message
            $this->addTranslatableMessage(self::LL_PREFIX . '.error.invalid_storage_folder');
        }
        return $validData;
    }

    /**
     * Add a translatable flash message
     *
     * @param string $messageKey the message key to be translated
     * @param ContextualFeedbackSeverity $severity the flash message severity
     */
    protected function addTranslatableMessage(string $messageKey, ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::ERROR): void
    {
        $message = $this->getLanguageService()->sL($messageKey);
        $this->addMessage($message, $severity);
    }

    /**
     * Add a flash message
     *
     * @param string $message the flash message content
     * @param ContextualFeedbackSeverity $severity the flash message severity
     */
    protected function addMessage(string $message, ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK): void
    {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, '', $severity);
        $service = GeneralUtility::makeInstance(FlashMessageService::class);
        $queue = $service->getMessageQueueByIdentifier();
        $queue->enqueue($flashMessage);
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
