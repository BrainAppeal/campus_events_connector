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

use BrainAppeal\CampusEventsConnector\CeImport\DataCollection\CeApiConnector;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Additional BE fields for ip address anonymization task.
 */
class EventImportAdditionalFieldProvider implements AdditionalFieldProviderInterface
{
    public const LL_PREFIX = 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask';

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
     * Add additional fields
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param AbstractTask|null $task When editing, reference to the current task. NULL when adding.
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return array<string, mixed> Array containing all the information pertaining to the additional fields
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
    {
        $additionalFields = [];
        $additionalFields['task_eventImport_baseUri'] = $this->getBaseUriAdditionalField($taskInfo, $task);
        $additionalFields['task_eventImport_apiKey'] = $this->getApiKeyAdditionalField($taskInfo, $task);
        $additionalFields['task_eventImport_pid'] = $this->getPidAdditionalField($taskInfo, $task);
        $additionalFields['task_eventImport_storageId'] = $this->getStorageIdAdditionalField($task);
        $additionalFields['task_eventImport_storageFolder'] = $this->getStorageFolderAdditionalField($taskInfo, $task);
        $additionalFields['task_eventImport_forceUpdate'] = $this->getForceUpdateAdditionalField($taskInfo, $task);
        return $additionalFields;
    }

    /**
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param EventImportTask|null $task When editing, reference to the current task. NULL when adding.
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getApiKeyAdditionalField(array &$taskInfo, ?EventImportTask $task): array
    {
        $fieldId = 'campusEventsConnector_eventImport_apiKey';
        $apiKey = null !== $task ? $task->getApiKey() : null;
        if (empty($taskInfo[$fieldId])) {
            $taskInfo[$fieldId] = !empty($apiKey) ? $apiKey : '';
        }
        $fieldName = 'tx_scheduler[' . $fieldId . ']';
        $fieldHtml = '<input class="form-control" type="text" ' . 'name="' . $fieldName . '" ' . 'id="' . $fieldId . '" ' . 'value="' . $taskInfo[$fieldId] . '" ' . 'size="4">';
        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => self::LL_PREFIX . '.api_key',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
        return $fieldConfiguration;
    }

    /**
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param EventImportTask|null $task When editing, reference to the current task. NULL when adding.
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getBaseUriAdditionalField(array &$taskInfo, ?EventImportTask $task): array
    {
        $fieldId = 'campusEventsConnector_eventImport_baseUri';
        $baseUri = null !== $task ? $task->getBaseUri() : EventImportTask::BASE_URI_DEFAULT;
        if (empty($taskInfo[$fieldId])) {
            $taskInfo[$fieldId] = $baseUri;
        }
        $fieldName = 'tx_scheduler[' . $fieldId . ']';
        $fieldHtml = '<input class="form-control" type="text" ' . 'name="' . $fieldName . '" ' . 'id="' . $fieldId . '" ' . 'value="' . $taskInfo[$fieldId] . '" ' . 'size="4">';
        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => self::LL_PREFIX . '.base_uri',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
        return $fieldConfiguration;
    }

    /**
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param EventImportTask|null $task When editing, reference to the current task. NULL when adding.
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getPidAdditionalField(array &$taskInfo, ?EventImportTask $task): array
    {
        $fieldId = 'campusEventsConnector_eventImport_pid';
        $pid = null !== $task ? $task->getPid() : null;
        if (empty($taskInfo[$fieldId])) {
            $taskInfo[$fieldId] = empty($pid) ? 0 : (int)$pid;
        }
        $fieldName = 'tx_scheduler[' . $fieldId . ']';
        $fieldHtml = '<input class="form-control" type="text" ' . 'name="' . $fieldName . '" ' . 'id="' . $fieldId . '" ' . 'value="' . $taskInfo[$fieldId] . '" ' . '>';
        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => self::LL_PREFIX . '.pid',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
        return $fieldConfiguration;
    }

    /**
     * @param EventImportTask|null $task When editing, reference to the current task. NULL when adding.
     * @return array{code: string, label: string, cshKey: string, cshLabel: string} Array containing all the information pertaining to the additional fields
     */
    protected function getStorageIdAdditionalField(?EventImportTask $task): array
    {
        $fieldId = 'campusEventsConnector_eventImport_storageId';
        $fieldName = 'tx_scheduler[' . $fieldId . ']';

        /** @var \TYPO3\CMS\Core\Resource\ResourceStorage[] $storages */
        $storages = GeneralUtility::makeInstance(StorageRepository::class)->findAll();
        $options = [];
        foreach ($storages as $storage) {
            $selAttr = null !== $task && (int) $task->storageId === $storage->getUid() ? ' selected="selected"' : '';
            $options[] = '<option value="' . $storage->getUid() . '"'.$selAttr.'>' . $storage->getName() . '</option>';
        }

        $fieldHtml = '<select class="form-control" name="' . $fieldName . '" id="' . $fieldId . '">' . implode("\n", $options) . '</select>';

        return [
            'code' => $fieldHtml,
            'label' => self::LL_PREFIX . '.storage_id',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
    }

    /**
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param EventImportTask|null $task When editing, reference to the current task. NULL when adding.
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getStorageFolderAdditionalField(array &$taskInfo, ?EventImportTask $task): array
    {
        $fieldId = 'campusEventsConnector_eventImport_storageFolder';
        if (empty($taskInfo[$fieldId])) {
            $storageFolder = null !== $task ? $task->getStorageFolder() : null;
            $taskUid = (null === $task) ? time()%10000 : $task->getTaskUid();
            $taskInfo[$fieldId] = empty($storageFolder) ? 'campus_events_import/task-'.$taskUid.'/' : $storageFolder;
        }
        $fieldName = 'tx_scheduler[' . $fieldId . ']';
        $fieldHtml = '<input class="form-control" type="text" ' . 'name="' . $fieldName . '" ' . 'id="' . $fieldId . '" ' . 'value="' . $taskInfo[$fieldId] . '" ' . '>';
        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => self::LL_PREFIX . '.storage_folder',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
        return $fieldConfiguration;
    }

    protected function getForceUpdateAdditionalField(array &$taskInfo, ?EventImportTask $task): array
    {
        $propertyName = 'forceUpdate';
        $value = $taskInfo['emailOnBrokenLinkOnly']??null;
        if ($value === null && $task !== null) {
            $value = $task->forceUpdate;
        }
        $fieldId = 'campusEventsConnector_eventImport_' . $propertyName;
        $fieldName = 'tx_scheduler[' . $fieldId . ']';
        $fieldCode = '<input type="checkbox" class="form-check-input" name="'.$fieldName.'" '
            . 'id="' . $fieldId . '" ' . ($value ? 'checked="checked"' : '') . '>';
        return [
            'code' => $fieldCode,
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId,
            'label' => self::LL_PREFIX . '.force_update',
            'type' => 'checkToggle',
        ];
    }

    /**
     * Validate additional fields
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $validData = true;
        $validData &= $this->validateBaseUriAndApiKeyAdditionalField($submittedData, $schedulerModule);
        $validData &= $this->validatePidAdditionalField($submittedData, $schedulerModule);
        $validData &= $this->validateStorageIdAdditionalField($submittedData, $schedulerModule);
        $validData &= $this->validateStorageFolderAdditionalField($submittedData, $schedulerModule);
        return (bool) $validData;
    }

    /**
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateBaseUriAndApiKeyAdditionalField(array $submittedData, SchedulerModuleController $parentObject)
    {
        $validData = true;
        $baseUri = $submittedData['campusEventsConnector_eventImport_baseUri'];
        if (empty($baseUri)) {
            $this->addTranslatableMessage(self::LL_PREFIX . '.error.invalid_base_uri');
            $validData = false;
        }

        $apiKey = $submittedData['campusEventsConnector_eventImport_apiKey'];
        if (empty($apiKey) || preg_match('/^[\w]{8}-[\w]{16}-[\w]{8}$/', (string) $apiKey) !== 1) {
            $this->addTranslatableMessage(self::LL_PREFIX . '.error.invalid_api_key');
            $validData = false;
        }

        if ($validData) {
            $hasApiCheckFailureMessage = false;
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
     * @param SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validatePidAdditionalField(array $submittedData, SchedulerModuleController $parentObject): bool
    {
        $validData = false;
        $data = $submittedData['campusEventsConnector_eventImport_pid'];
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
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateStorageIdAdditionalField(array $submittedData, SchedulerModuleController $parentObject): bool
    {
        $validData = false;
        $data = $submittedData['campusEventsConnector_eventImport_storageId'];
        if (empty($data) || is_numeric($data)) {
            $validData = true;
        } else {
            // Issue error message
            $this->addTranslatableMessage(self::LL_PREFIX . '.error.invalid_storage_id');
        }
        return $validData;
    }

    /**
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateStorageFolderAdditionalField(array $submittedData, SchedulerModuleController $parentObject): bool
    {
        $validData = false;
        $data = $submittedData['campusEventsConnector_eventImport_storageFolder'];
        if (!empty($data)) {
            $validData = true;
        } else {
            // Issue error message
            $this->addTranslatableMessage(self::LL_PREFIX . '.error.invalid_storage_folder');
        }
        return $validData;
    }

    /**
     * Assign addition fields to the task object
     *
     * @param array $submittedData Contains data submitted by the user
     * @param AbstractTask|EventImportTask $task Reference to the current task object
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task): void
    {
        /** @var EventImportTask $task */
        $task->apiKey = $submittedData['campusEventsConnector_eventImport_apiKey'];
        $baseUri = $submittedData['campusEventsConnector_eventImport_baseUri'];
        if (!empty($baseUri) && !str_starts_with((string) $baseUri, 'http')) {
            $baseUri = 'https://' . $baseUri;
        }
        $task->baseUri = $baseUri;
        $task->pid = $submittedData['campusEventsConnector_eventImport_pid'];
        $task->storageId = $submittedData['campusEventsConnector_eventImport_storageId'];
        $task->storageFolder = $submittedData['campusEventsConnector_eventImport_storageFolder'];
        $task->forceUpdate = $submittedData['campusEventsConnector_eventImport_forceUpdate']??false;
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
