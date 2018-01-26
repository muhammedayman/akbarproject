<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package QUnit
*   @since Version 0.1
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

QUnit::includeClass('QUnit_Object');

class QUnit_Logger_Method extends QUnit_Object {

	var $minLogLevel = 'none';
	
    function _init() {
        parent::_init();
        $this->attrAccessor('minLogLevel');
        $this->attrAccessor('logLevels');
        $this->attrAccessor('messagePrefix');
        $this->attrAccessor('config');

        $this->logLevels = array();
        $this->messagePrefix = '';

        $this->addLogLevel('none', 0);
        $this->addLogLevel('error', 1);
        $this->addLogLevel('warning', 2);
        $this->addLogLevel('info', 3);
        $this->addLogLevel('debug', 4);
    }

    function _getMinLogLevel() {
    	if (strlen($this->minLogLevel)) {
    		return $this->minLogLevel;
    	}
        if($level = $this->config->get('minLogLevel')) {
            return $level;
        }
        return 'none';
    }

    function addLogLevel($name, $id) {
        $this->logLevels[$name] = array('id' => $id, 'name' => $name);
    }

    function isMinLogLevel($level) {
        if(isset($this->logLevels[$level])) {
            return $this->logLevels[$level]['id'] <= $this->logLevels[$this->get('minLogLevel')]['id'];
        }
        return false;
    }

    function getLogLevelCaption($level) {
        if(isset($this->logLevels[$level])) {
            return $level;
        }
        return "undefined";
    }

    function getLogLevelId($level) {
        if(isset($this->logLevels[$level])) {
            return $this->logLevels[$level]['id'];
        }
        return false;
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
    }
}

?>