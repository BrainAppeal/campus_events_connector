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

use BrainAppeal\CampusEventsConnector\Http\HttpException;
use BrainAppeal\CampusEventsConnector\Importer\ExtendedApiConnector;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Additional BE fields for ip address anonymization task.
 */
class EventImportAdditionalFieldProvider extends AbstractAdditionalFieldProvider
{
    /**
     * Add additional fields
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param AbstractTask|null $task When editing, reference to the current task. NULL when adding.
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $additionalFields = [];
        $additionalFields['task_eventImport_baseUri'] = $this->getBaseUriAdditionalField($taskInfo, $task);
        $additionalFields['task_eventImport_apiKey'] = $this->getApiKeyAdditionalField($taskInfo, $task);
        $additionalFields['task_eventImport_apiVersion'] = $this->getApiVersionAdditionalField($taskInfo, $task);
        $additionalFields['task_eventImport_pid'] = $this->getPidAdditionalField($taskInfo, $task);
        $additionalFields['task_eventImport_storageId'] = $this->getStorageIdAdditionalField($task);
        $additionalFields['task_eventImport_storageFolder'] = $this->getStorageFolderAdditionalField($taskInfo, $task);
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
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.api_key',
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
    protected function getApiVersionAdditionalField(array &$taskInfo, ?EventImportTask $task): array
    {
        $fieldId = 'campusEventsConnector_eventImport_apiVersion';
        $selectedApiVersion = null !== $task ? $task->getApiVersion() : EventImportTask::API_VERSION_ABOVE_227;
        if (empty($taskInfo[$fieldId])) {
            $taskInfo[$fieldId] = $selectedApiVersion;
        }
        $fieldName = 'tx_scheduler[' . $fieldId . ']';

        $options = [];
        $optionValues = [
            EventImportTask::API_VERSION_LEGACY => "geringer als 2.27.0",
            EventImportTask::API_VERSION_ABOVE_227 => "2.27.0 oder höher"
        ];
        foreach ($optionValues as $optionValue => $optionName) {
            $selAttr = $selectedApiVersion === $optionValue ? ' selected="selected"' : '';
            $options[] = '<option value="' . $optionValue . '"'.$selAttr.'>' . $optionName . '</option>';
        }

        $fieldHtml = '<select class="form-control" name="' . $fieldName . '" id="' . $fieldId . '">' . implode("\n", $options) . '</select>';

        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.api_version',
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
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.base_uri',
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
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.pid',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
        return $fieldConfiguration;
    }

    /**
     * @param EventImportTask|null $task When editing, reference to the current task. NULL when adding.
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getStorageIdAdditionalField(?EventImportTask $task): array
    {
        $fieldId = 'campusEventsConnector_eventImport_storageId';
        $fieldName = 'tx_scheduler[' . $fieldId . ']';

        /** @var \TYPO3\CMS\Core\Resource\ResourceStorage[] $storages */
        $storages = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\StorageRepository::class)->findAll();
        $options = [];
        foreach ($storages as $storage) {
            $selAttr = null !== $task && (int) $task->storageId === $storage->getUid() ? ' selected="selected"' : '';
            $options[] = '<option value="' . $storage->getUid() . '"'.$selAttr.'>' . $storage->getName() . '</option>';
        }

        $fieldHtml = '<select class="form-control" name="' . $fieldName . '" id="' . $fieldId . '">' . implode("\n", $options) . '</select>';

        return [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.storage_id',
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
        $storageFolder = null !== $task ? $task->getStorageFolder() : null;
        if (empty($taskInfo[$fieldId])) {
            $taskUid = (null === $task) ? time()%10000 : $task->getTaskUid();
            $taskInfo[$fieldId] = empty($storageFolder) ? 'campus_events_import/task-'.$taskUid.'/' : $storageFolder;
        }
        $fieldName = 'tx_scheduler[' . $fieldId . ']';
        $fieldHtml = '<input class="form-control" type="text" ' . 'name="' . $fieldName . '" ' . 'id="' . $fieldId . '" ' . 'value="' . $taskInfo[$fieldId] . '" ' . '>';
        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.storage_folder',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
        return $fieldConfiguration;
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
            $this->addTranslatableMessage('LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.error.invalid_base_uri');
            $validData = false;
        }

        $apiVersion = !empty($submittedData['campusEventsConnector_eventImport_apiVersion'])
            && $submittedData['campusEventsConnector_eventImport_apiVersion'] === EventImportTask::API_VERSION_LEGACY
            ? EventImportTask::API_VERSION_LEGACY : EventImportTask::API_VERSION_ABOVE_227;

        $apiKey = $submittedData['campusEventsConnector_eventImport_apiKey'];
        if (empty($apiKey) || preg_match('/^[\w]{8}-[\w]{16}-[\w]{8}$/', $apiKey) !== 1) {
            $this->addTranslatableMessage('LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.error.invalid_api_key');
            $validData = false;
        }

        if ($validData) {
            if ($baseUri !== EventImportTask::BASE_URI_DEFAULT) {
                $apiConnector = $this->initializeApiConnector($baseUri, $apiKey, $apiVersion);
                try {
                    $validData = $apiConnector->checkApiVersion();
                } catch (HttpException $httpException) {
                    $this->addMessage(
                        $httpException->getMessage(),
                        FlashMessage::ERROR
                    );
                    $validData = false;
                }
            } else {
                $validData = false;
            }
            if (!$validData) {
                $this->addTranslatableMessage('LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.error.invalid_base_uri');
            }
        }

        return $validData;
    }

    /**
     * @param string $baseUri Tha base uri for the api
     * @param string $apiKey The api key
     * @param string $apiVersion The api version
     * @return \BrainAppeal\CampusEventsConnector\Importer\ApiConnector|ExtendedApiConnector
     */
    private function initializeApiConnector(string $baseUri, string $apiKey, string $apiVersion)
    {
        if ($apiVersion === EventImportTask::API_VERSION_LEGACY) {
            /** @var \BrainAppeal\CampusEventsConnector\Importer\ApiConnector $apiConnector */
            $apiConnector = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \BrainAppeal\CampusEventsConnector\Importer\ApiConnector::class
            );
            $apiConnector->setBaseUri($baseUri);
        } else {
            /** @var ExtendedApiConnector $apiConnector */
            $apiConnector = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtendedApiConnector::class);
            $apiConnector->setBaseUri($baseUri);
            $apiConnector->setApiKey($apiKey);
        }
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
            $dbal = \BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALFactory::getInstance();
            $validData = $dbal->checkIfPidIsValid($data);
        }
        if (!$validData) {
            // Issue error message
            $this->addTranslatableMessage('LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.error.invalid_pid');
        }
        return $validData;
    }

    /**
     * Add a translatable flash message
     *
     * @param string $messageKey the message key to be translated
     * @param int $severity the flash message severity
     */
    protected function addTranslatableMessage(string $messageKey, int $severity = AbstractMessage::ERROR): void
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
            $this->addTranslatableMessage('LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.error.invalid_storage_id');
        }
        return $validData;
    }

    /**
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateStorageFolderAdditionalField(array $submittedData, SchedulerModuleController $parentObject)
    {
        $validData = false;
        $data = $submittedData['campusEventsConnector_eventImport_storageFolder'];
        if (!empty($data)) {
            $validData = true;
        } else {
            // Issue error message
            $this->addTranslatableMessage('LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.error.invalid_storage_folder');
        }
        return $validData;
    }

    /**
     * Save additional field in task
     *
     * @param array $submittedData Contains data submitted by the user
     * @param AbstractTask|EventImportTask $task Reference to the current task object
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        $task->apiKey = $submittedData['campusEventsConnector_eventImport_apiKey'];
        $task->apiVersion = $submittedData['campusEventsConnector_eventImport_apiVersion'];
        $baseUri = $submittedData['campusEventsConnector_eventImport_baseUri'];
        if (!empty($baseUri) && strpos($baseUri, 'http') !== 0) {
            $baseUri = 'https://' . $baseUri;
        }
        $task->baseUri = $baseUri;
        $task->pid = $submittedData['campusEventsConnector_eventImport_pid'];
        $task->storageId = $submittedData['campusEventsConnector_eventImport_storageId'];
        $task->storageFolder = $submittedData['campusEventsConnector_eventImport_storageFolder'];
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
