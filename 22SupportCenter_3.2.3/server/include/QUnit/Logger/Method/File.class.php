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

class QUnit_Logger_Method_File extends QUnit_Logger_Method {



	function _init() {
		parent::_init();
		$this->file = QUnit::newObj('QUnit_Io_File');
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
    		if(!$this->file->isOpened()) {
    			$this->file->setFileName($this->config->get('logFile'));
    			$this->file->setFileMode('a');
    			$this->file->open();
    		}

    		if($this->file->isOpened()) {
    			
    			if (is_array($user_ids)) {
    				$users = "(" . implode(',', $user_ids) ."):";
    			} elseif (strlen($user_ids)) {
    				$users = "(" . $user_ids ."): ";
    			} else {
    				$users = '';
    			}
    			if (is_array($emails)) {
    				$emails = "(" . implode(',', $emails) ."):";
    			} elseif (strlen($emails)) {
    				$emails = "(" . $emails ."):";
    			} else {
    				$emails = '';
    			}
    			
    			$this->file->writeLine(strftime('%Y/%m/%d %H:%M:%S', time())." ".
    			$type.": ".$this->getLogLevelCaption($level).": " .
    			$users . $emails . $this->get('messagePrefix')."$message\n\n");
    		}
    	}
    }
}

?>
