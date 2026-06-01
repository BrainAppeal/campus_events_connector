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

use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Additional BE fields for ip address anonymization task.
 */
class EventImportAdditionalFieldProvider implements AdditionalFieldProviderInterface
{
    use EventImportValidationTrait;

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
        return [
            'task_eventImport_baseUri' => $this->getBaseUriAdditionalField($taskInfo, $task),
            'task_eventImport_apiKey' => $this->getApiKeyAdditionalField($taskInfo, $task),
            'task_eventImport_pid' => $this->getPidAdditionalField($taskInfo, $task),
            'task_eventImport_storageId' => $this->getStorageIdAdditionalField($task),
            'task_eventImport_storageFolder' => $this->getStorageFolderAdditionalField($taskInfo, $task),
            'task_eventImport_forceUpdate' => $this->getForceUpdateAdditionalField($taskInfo, $task),
        ];
    }

    /**
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param EventImportTask|null $task When editing, reference to the current task. NULL when adding.
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getApiKeyAdditionalField(array &$taskInfo, ?EventImportTask $task): array
    {
        $fieldId = 'campusEventsConnector_eventImport_apiKey';
        $apiKey = $task instanceof EventImportTask ? $task->getApiKey() : null;
        if (empty($taskInfo[$fieldId])) {
            $taskInfo[$fieldId] = in_array($apiKey, [null, ''], true) ? '' : $apiKey;
        }
        $fieldName = 'tx_scheduler[' . $fieldId . ']';
        $fieldHtml = '<input class="form-control" type="text" name="' . $fieldName . '" ' . 'id="' . $fieldId . '" ' . 'value="' . $taskInfo[$fieldId] . '" ' . 'size="4">';
        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => self::LL_PREFIX . '.api_key',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId,
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
        $baseUri = $task instanceof EventImportTask ? $task->getBaseUri() : EventImportTask::BASE_URI_DEFAULT;
        if (empty($taskInfo[$fieldId])) {
            $taskInfo[$fieldId] = $baseUri;
        }
        $fieldName = 'tx_scheduler[' . $fieldId . ']';
        $fieldHtml = '<input class="form-control" type="text" name="' . $fieldName . '" ' . 'id="' . $fieldId . '" ' . 'value="' . $taskInfo[$fieldId] . '" ' . 'size="4">';
        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => self::LL_PREFIX . '.base_uri',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId,
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
        $pid = $task instanceof EventImportTask ? $task->getPid() : null;
        if (empty($taskInfo[$fieldId])) {
            $taskInfo[$fieldId] = $pid === null || $pid === 0 ? 0 : (int)$pid;
        }
        $fieldName = 'tx_scheduler[' . $fieldId . ']';
        $fieldHtml = '<input class="form-control" type="text" name="' . $fieldName . '" ' . 'id="' . $fieldId . '" ' . 'value="' . $taskInfo[$fieldId] . '" ' . '>';
        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => self::LL_PREFIX . '.pid',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId,
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

        /** @var ResourceStorage[] $storages */
        $storages = GeneralUtility::makeInstance(StorageRepository::class)->findAll();
        $options = [];
        foreach ($storages as $storage) {
            $selAttr = $task instanceof EventImportTask && (int)$task->storageId === $storage->getUid() ? ' selected="selected"' : '';
            $options[] = '<option value="' . $storage->getUid() . '"' . $selAttr . '>' . $storage->getName() . '</option>';
        }

        $fieldHtml = '<select class="form-control" name="' . $fieldName . '" id="' . $fieldId . '">' . implode("\n", $options) . '</select>';

        return [
            'code' => $fieldHtml,
            'label' => self::LL_PREFIX . '.storage_id',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId,
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
            $storageFolder = $task instanceof EventImportTask ? $task->getStorageFolder() : null;
            $taskUid = ($task instanceof EventImportTask) ? $task->getTaskUid() : time() % 10000;
            $taskInfo[$fieldId] = in_array($storageFolder, [null, ''], true) ? 'campus_events_import/task-' . $taskUid . '/' : $storageFolder;
        }
        $fieldName = 'tx_scheduler[' . $fieldId . ']';
        $fieldHtml = '<input class="form-control" type="text" name="' . $fieldName . '" ' . 'id="' . $fieldId . '" ' . 'value="' . $taskInfo[$fieldId] . '" ' . '>';
        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => self::LL_PREFIX . '.storage_folder',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId,
        ];
        return $fieldConfiguration;
    }

    protected function getForceUpdateAdditionalField(array &$taskInfo, ?EventImportTask $task): array
    {
        $propertyName = 'forceUpdate';
        $value = $taskInfo['emailOnBrokenLinkOnly'] ?? null;
        if ($value === null && $task instanceof EventImportTask) {
            $value = $task->forceUpdate;
        }
        $fieldId = 'campusEventsConnector_eventImport_' . $propertyName;
        $fieldName = 'tx_scheduler[' . $fieldId . ']';
        $fieldCode = '<input type="checkbox" class="form-check-input" name="' . $fieldName . '" '
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
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
    {
        $validData = true;
        $validData &= $this->validateBaseUriAndApiKeyAdditionalField($submittedData);
        $validData &= $this->validatePidAdditionalField($submittedData);
        $validData &= $this->validateStorageIdAdditionalField($submittedData);
        $validData &= $this->validateStorageFolderAdditionalField($submittedData);
        return (bool)$validData;
    }

    /**
     * Assign addition fields to the task object
     *
     * @param array $submittedData Contains data submitted by the user
     * @param AbstractTask $task Reference to the current task object
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task): void
    {
        /** @var EventImportTask $task */
        $task->apiKey = $submittedData['campusEventsConnector_eventImport_apiKey'];
        $baseUri = $submittedData['campusEventsConnector_eventImport_baseUri'];
        if (!empty($baseUri) && !str_starts_with((string)$baseUri, 'http')) {
            $baseUri = 'https://' . $baseUri;
        }
        $task->baseUri = $baseUri;
        $task->pid = $submittedData['campusEventsConnector_eventImport_pid'];
        $task->storageId = (int)$submittedData['campusEventsConnector_eventImport_storageId'];
        $task->storageFolder = $submittedData['campusEventsConnector_eventImport_storageFolder'];
        $task->forceUpdate = $submittedData['campusEventsConnector_eventImport_forceUpdate'] ?? false;
    }
}
