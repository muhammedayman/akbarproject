<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package QUnit
*   @since Version 0.1
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

QUnit::includeClass('QUnit_Logger_Method');

class QUnit_Logger_Method_Db extends QUnit_Logger_Method {


    function _init() {
        parent::_init();
    }

    /**
    *
    * Log message
    *
    * @access public
    * @param $level integer
    * @param $message string
    *
    */
    function log($level, $message, $type, $user_ids = '', $emails = '') {
        if($this->isMinLogLevel($level)) {
        	
        	$session = QUnit::newObj('QUnit_Session');
        	if (is_array($user_ids)) {
        		$user_ids[] = $session->getVar('userId');
        	} else if (strlen($user_ids)) {
        		$user_ids = array($user_ids, $session->getVar('userId'));
        	} else {
        		$user_ids = array($session->getVar('userId'));
        	}
        	
        	$logMessages = QUnit::newObj('QUnit_Rpc_Service');
            $params = QUnit::newObj('QUnit_Rpc_Params');
            $fields = new stdClass();
            
            $fields->level = $level;
            $fields->log_text = $message;
            $fields->log_type = $type;
            $params->set('user_ids', $user_ids);
            $params->set('emails', $emails);
            
            $params->set('fields', $fields);
            $logMessages->callService('Logs', 'insertLog', $params);
        }
    }
}

?>