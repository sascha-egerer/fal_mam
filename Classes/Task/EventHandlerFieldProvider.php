<?php
namespace Crossmedia\FalMam\Task;

use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class EventHandlerFieldProvider implements AdditionalFieldProviderInterface {

    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $parentObject) {
        // if (empty($taskInfo['ip'])) {
        //     if($parentObject->CMD == 'edit') {
        //         $taskInfo['ip'] = $task->ip;
        //     } else {
        //         $taskInfo['ip'] = '';
        //     }
        // }

        // // Write the code for the field
        // $fieldID = 'task_ip';
        // $fieldCode = '<input type="text" name="tx_scheduler[ip]" id="' . $fieldID . '" value="' . $taskInfo['ip'] . '" size="30" />';
        // $additionalFields = array();
        // $additionalFields[$fieldID] = array(
        //     'code'     => $fieldCode,
        //     'label'    => 'IP-Adresse/Webseite'
        // );

        return $additionalFields;
    }

    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $parentObject) {
        // $submittedData['ip'] = trim($submittedData['ip']);
        return true;
    }

    public function saveAdditionalFields(array $submittedData, AbstractTask $task) {
        // $task->ip = $submittedData['ip'];
    }
}
?>