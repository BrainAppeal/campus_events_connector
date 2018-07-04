<?php

namespace BrainAppeal\BrainEventConnector\Task;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;

/**
 * Additional BE fields for ip address anonymization task.
 */
class EventImportAdditionalFieldProvider implements AdditionalFieldProviderInterface
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
        $additionalFields['task_eventImport_pid'] = $this->getPidAdditionalField($taskInfo, $task);
        return $additionalFields;
    }

    /**
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask|EventImportTask|null $task When editing, reference to the current task. NULL when adding.
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getApiKeyAdditionalField(array &$taskInfo, $task)
    {
        $fieldId = 'brainEventConnector_eventImport_apiKey';
        if (empty($taskInfo[$fieldId])) {
            $taskInfo[$fieldId] = isset($task->apiKey) ? $task->apiKey : '00000000-0000000000000000-00000000';
        }
        $fieldName = 'tx_scheduler[' . $fieldId . ']';
        $fieldHtml = '<input class="form-control" type="text" ' . 'name="' . $fieldName . '" ' . 'id="' . $fieldId . '" ' . 'value="' . $taskInfo[$fieldId] . '" ' . 'size="4">';
        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:brain_event_connector/Resources/Private/Language/locallang.xlf:tx_braineventconnector_task_eventimporttask.api_key',
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
        $fieldId = 'brainEventConnector_eventImport_baseUri';
        if (empty($taskInfo[$fieldId])) {
            $taskInfo[$fieldId] = isset($task->baseUri) ? $task->baseUri : 'https://campusevents.example.com/';
        }
        $fieldName = 'tx_scheduler[' . $fieldId . ']';
        $fieldHtml = '<input class="form-control" type="text" ' . 'name="' . $fieldName . '" ' . 'id="' . $fieldId . '" ' . 'value="' . $taskInfo[$fieldId] . '" ' . 'size="4">';
        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:brain_event_connector/Resources/Private/Language/locallang.xlf:tx_braineventconnector_task_eventimporttask.base_uri',
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
        $fieldId = 'brainEventConnector_eventImport_pid';
        if (empty($taskInfo[$fieldId])) {
            $taskInfo[$fieldId] = empty($task->pid) ? null : intval($task->pid);
        }
        $fieldName = 'tx_scheduler[' . $fieldId . ']';
        $fieldHtml = '<input class="form-control" type="text" ' . 'name="' . $fieldName . '" ' . 'id="' . $fieldId . '" ' . 'value="' . $taskInfo[$fieldId] . '" ' . '>';
        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:brain_event_connector/Resources/Private/Language/locallang.xlf:tx_braineventconnector_task_eventimporttask.pid',
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
        $validData &= $this->validateBaseUriAdditionalField($submittedData, $parentObject);
        $validData &= $this->validateApiKeyAdditionalField($submittedData, $parentObject);
        $validData &= $this->validatePidAdditionalField($submittedData, $parentObject);
        return $validData;
    }

    /**
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateApiKeyAdditionalField(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        $validData = false;
        if (!empty($submittedData['brainEventConnector_eventImport_apiKey'])) {
            $validData = true;
        } else {
            // Issue error message
            $parentObject->addMessage($this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.invalidApiKey'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
        }
        return $validData;
    }

    /**
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateBaseUriAdditionalField(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        $validData = false;
        if (!empty($submittedData['brainEventConnector_eventImport_baseUri'])) {
            $validData = true;
        } else {
            // Issue error message
            $parentObject->addMessage($this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.invalidBaseUri'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
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
        $data = $submittedData['brainEventConnector_eventImport_pid'];
        if (empty($data) || is_numeric($data)) {
            $validData = true;
        } else {
            // Issue error message
            $parentObject->addMessage($this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.invalidPid'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
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
        $task->apiKey = $submittedData['brainEventConnector_eventImport_apiKey'];
        $task->baseUri = $submittedData['brainEventConnector_eventImport_baseUri'];
        $task->pid = $submittedData['brainEventConnector_eventImport_pid'];
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
