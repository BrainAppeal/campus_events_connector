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

use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;

/**
 * Additional BE fields for ip address anonymization task.
 */
class EventImportAdditionalFieldProvider extends AbstractAdditionalFieldProvider
{
    /**
     * Add additional fields
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask|null $task When editing, reference to the current task. NULL when adding.
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     */
    public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
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
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask|EventImportTask|null $task When editing, reference to the current task. NULL when adding.
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getApiKeyAdditionalField(array &$taskInfo, $task)
    {
        $fieldId = 'campusEventsConnector_eventImport_apiKey';
        if (empty($taskInfo[$fieldId])) {
            $taskInfo[$fieldId] = isset($task->apiKey) ? $task->apiKey : '00000000-0000000000000000-00000000';
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
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask|EventImportTask|null $task When editing, reference to the current task. NULL when adding.
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getApiVersionAdditionalField(array &$taskInfo, $task)
    {
        $fieldId = 'campusEventsConnector_eventImport_apiVersion';
        $taskInfo[$fieldId] = isset($task->apiVersion) && $task->apiVersion == 1 ? 'checked="checked"' : '';
        $fieldName = 'tx_scheduler[' . $fieldId . ']';

        $options = [];
        $optionValues = ['below-2-27-0' => "geringer als 2.27.0", 'above-2-27-0' => "2.27.0 oder höher"];
        foreach ($optionValues as $optionValue => $optionName) {
            $selAttr = null !== $task && $task->apiVersion === $optionValue ? ' selected="selected"' : '';
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
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask|EventImportTask|null $task When editing, reference to the current task. NULL when adding.
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getBaseUriAdditionalField(array &$taskInfo, $task)
    {
        $fieldId = 'campusEventsConnector_eventImport_baseUri';
        if (empty($taskInfo[$fieldId])) {
            $taskInfo[$fieldId] = isset($task->baseUri) ? $task->baseUri : 'https://campusevents.example.com/';
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
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask|EventImportTask|null $task When editing, reference to the current task. NULL when adding.
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getPidAdditionalField(array &$taskInfo, $task)
    {
        $fieldId = 'campusEventsConnector_eventImport_pid';
        if (empty($taskInfo[$fieldId])) {
            $taskInfo[$fieldId] = empty($task->pid) ? 0 : intval($task->pid);
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
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask|EventImportTask|null $task When editing, reference to the current task. NULL when adding.
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getStorageIdAdditionalField($task)
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
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask|EventImportTask|null $task When editing, reference to the current task. NULL when adding.
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getStorageFolderAdditionalField(array &$taskInfo, $task)
    {
        $fieldId = 'campusEventsConnector_eventImport_storageFolder';
        if (empty($taskInfo[$fieldId])) {
            $taskUid = (null === $task) ? time()%10000 : $task->getTaskUid();
            $taskInfo[$fieldId] = empty($task->storageFolder) ? 'campus_events_import/task-'.$taskUid.'/' : $task->storageFolder;
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
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        $validData = true;
        $validData &= $this->validateBaseUriAndApiKeyAdditionalField($submittedData, $parentObject);
        $validData &= $this->validatePidAdditionalField($submittedData, $parentObject);
        $validData &= $this->validateStorageIdAdditionalField($submittedData, $parentObject);
        $validData &= $this->validateStorageFolderAdditionalField($submittedData, $parentObject);
        return $validData;
    }

    /**
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateBaseUriAndApiKeyAdditionalField(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        $validData = true;

        $baseUri = $submittedData['campusEventsConnector_eventImport_baseUri'];
        if (empty($baseUri)) {
            self::addMessage($this->getLanguageService()->sL('LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.error.invalid_base_uri'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
            $validData = false;
        }

        $apiKey = $submittedData['campusEventsConnector_eventImport_apiKey'];
        if (empty($apiKey) || preg_match('/^[\w]{8}-[\w]{16}-[\w]{8}$/', $apiKey) !== 1) {
            self::addMessage($this->getLanguageService()->sL('LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.error.invalid_api_key'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
            $validData = false;
        }

        if ($validData) {
            /** @var \BrainAppeal\CampusEventsConnector\Importer\ApiConnector $apiConnector */
            $apiConnector = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \BrainAppeal\CampusEventsConnector\Importer\ApiConnector::class
            );
            $apiConnector->setBaseUri($baseUri);

            try {
                $validData = $apiConnector->checkApiVersion();
            } catch (\BrainAppeal\CampusEventsConnector\Http\HttpException $httpException) {
                self::addMessage(
                    $httpException->getMessage(),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );
                $validData = false;
            }
            if (!$validData) {
                self::addMessage($this->getLanguageService()->sL('LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.error.invalid_base_uri'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
            }
        }

        return $validData;
    }

    /**
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validatePidAdditionalField(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        $validData = false;
        $data = $submittedData['campusEventsConnector_eventImport_pid'];
        if (empty($data) || is_numeric($data)) {
            $dbal = \BrainAppeal\CampusEventsConnector\Importer\DBAL\DBALFactory::getInstance();
            $validData = $dbal->checkIfPidIsValid($data);
        }
        if (!$validData) {
            // Issue error message
            self::addMessage($this->getLanguageService()->sL('LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.error.invalid_pid'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
        }
        return $validData;
    }

    /**
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateStorageIdAdditionalField(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        $validData = false;
        $data = $submittedData['campusEventsConnector_eventImport_storageId'];
        if (empty($data) || is_numeric($data)) {
            $validData = true;
        } else {
            // Issue error message
            self::addMessage($this->getLanguageService()->sL('LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.error.invalid_storage_id'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
        }
        return $validData;
    }

    /**
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateStorageFolderAdditionalField(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        $validData = false;
        $data = $submittedData['campusEventsConnector_eventImport_storageFolder'];
        if (!empty($data)) {
            $validData = true;
        } else {
            // Issue error message
            self::addMessage($this->getLanguageService()->sL('LLL:EXT:campus_events_connector/Resources/Private/Language/locallang.xlf:tx_campuseventsconnector_task_eventimporttask.error.invalid_storage_folder'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
        }
        return $validData;
    }

    /**
     * Save additional field in task
     *
     * @param array $submittedData Contains data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask|EventImportTask $task Reference to the current task object
     */
    public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task)
    {
        $task->apiKey = $submittedData['campusEventsConnector_eventImport_apiKey'];
        $task->apiVersion = $submittedData['campusEventsConnector_eventImport_apiVersion'];
        $task->baseUri = $submittedData['campusEventsConnector_eventImport_baseUri'];
        $task->pid = $submittedData['campusEventsConnector_eventImport_pid'];
        $task->storageId = $submittedData['campusEventsConnector_eventImport_storageId'];
        $task->storageFolder = $submittedData['campusEventsConnector_eventImport_storageFolder'];
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
