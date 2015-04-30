<?php
namespace Crossmedia\FalMam\Task;

use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class EventQueueHandlerFieldProvider implements AdditionalFieldProviderInterface {

    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $parentObject) {
        if (empty($taskInfo['items'])) {
            if($parentObject->CMD == 'edit') {
                $taskInfo['items'] = $task->items;
            } else {
                $taskInfo['items'] = '';
            }
        }

        // Write the code for the field
        $fieldID = 'items';
        $fieldCode = '<input type="text" name="tx_scheduler[items]" id="' . $fieldID . '" value="' . $taskInfo['items'] . '" size="30" />';
        $additionalFields = array();
        $additionalFields[$fieldID] = array(
            'code'     => $fieldCode,
            'label'    => 'Amount of queue items to process in each run'
        );

        return $additionalFields;
    }

    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $parentObject) {
        $submittedData['items'] = trim($submittedData['items']);
        return true;
    }

    public function saveAdditionalFields(array $submittedData, AbstractTask $task) {
        $task->items = $submittedData['items'];
    }
}
?>