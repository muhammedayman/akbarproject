<?php
/**
 *   Handler class for system Maintainance
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_ExecutionController extends QUnit_Rpc_Service {

    var $arr_start;

    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
            default:
                return false;
                break;
        }
    }

    function getExecutionTime() {
        $arr_end = @gettimeofday();

        $delay_sec = $arr_end['sec'] - $this->arr_start['sec'];
        $delay_usec = $arr_end['usec'] - $this->arr_start['usec'];
        if ($delay_usec < 0) {
            $delay_sec--;
            $delay_usec = 1000000 + $delay_usec;
        }
        $delay_usec = str_repeat('0', 6 - strlen($delay_usec)) . $delay_usec;
        return  $delay_sec + $delay_usec/1000000;
    }


    function setRunning($isRunning = true) {
        $paramsSettings = $this->createParamsObject();
        if ($isRunning) {
            if ($this->arr_start == null) {
                $this->arr_start = @gettimeofday();
            }
            $paramsSettings->setField('lastExecution', time());
            $this->callService('Settings', 'updateSetting', $paramsSettings);
        } else {
            $paramsSettings->set('setting_key', 'lastExecution');
            $this->callService('Settings', 'deleteSetting', $paramsSettings);
            $this->state->log('info', 'jobs.php execution time: ' . $this->getExecutionTime(), 'jobs.php');
        }
    }

    function getServerLoad() {
        $serverLoad = 0;
        $fileName = '/proc/loadavg';
        if (@file_exists($fileName)) {
            if ($fd = @fopen($fileName, 'r') ) {
                $load = split(' ', fgets($fd, 4096));
                fclose($fd);
                return $load[0];
            }
        }
        return $serverLoad;
    }

    function canStartProcess() {
        if ($this->getServerLoad() > 6) {
            $this->state->log('warning', 'Postponed execution of jobs.php, Too high load on server', 'jobs.php');
            return false;
        }
        
        if (!$this->state->config->get('lastExecution') || time() - $this->state->config->get('lastExecution') > 900 || $this->state->config->get('lastExecution') > time()) {
            return true;
        }
        
        $this->state->log('warning', "Next jobs.php process blocked - one more process is still running", 'job.php');
        return false;
    }

}
?>
