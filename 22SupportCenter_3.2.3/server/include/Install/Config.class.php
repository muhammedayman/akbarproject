<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package AddressCorrector_Core
*   @since Version 0.1
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

QUnit::includeClass('QUnit_Ui_Widget_WizardStep');

class Install_Config extends QUnit_Ui_Widget_WizardStep {

    function _init() {
        parent::_init();
        $this->set('template', 'Config');
        $this->set('title', 'Set Up Config');
    }

    function process() {
        if($this->processForm()) {
        	return true;
        }
        return false;
    }

    function processForm() {
        $request = $this->state->getByRef('request');
        if($request->get('submit')) {
        	if(!$this->saveConfig()) {
        		return false;
        	}
        	return true;
        }
        return false;
    }

    function saveConfig() {
    	$request =& $this->state->getByRef('request');

    	foreach(array('applicationURL', 'tmpPath') as $key) {
    		if(!strlen($request->get($key))) {
    			$this->set('errorMessage', $key.' cannot be empty');
    			return false;
    		}
    		$params[$key] = $request->get($key);
    	}

        if (!file_exists($request->get('tmpPath'))) {        
    		$this->set('errorMessage', 'Directory ' . $request->get('tmpPath') . ' doesn\'t exists');
    		return false;
        }
    	
    	if(!is_writable($request->get('tmpPath'))) {
    		$this->set('errorMessage', 'Directory ' . $request->get('tmpPath') . ' is not writable');
    		return false;
        }

        
    	if (strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false || !function_exists('checkdnsrr')) {
    		//for IIS switch off by default email quality check - security problems
    		$params['checkEmailQuality'] = 'n';
    	} else {
    		$params['checkEmailQuality'] = 'y';
    	}
    	$params['deleteLogsAfter'] = 7;
    	$params['logFile'] = '';
    	$params['minLogLevel'] = 'info';
    	$params['sendRegistrationEmail'] = 'y';
    	$params['sendSystemMails'] = 'y';
    	$params['agentCanSeeOwnReports'] = 'y';
    	$params['subjectPrefixes'] = 're:, fwd:, fw:';
    	$params['dbLevel'] = $this->computeLastVersion();
    	$params['ticketIdFormat'] = 'ZZZ-XXXXX-999';
    	$params['defaultLanguage'] = 'English';
		$params['useOutbox'] = 'y';   	
		$params['maxRetryCount'] = 10;   	
		$params['retryAfterDelay'] = 5;   	
		$params['defaultPriority'] = 50;
		$params['knowledgeRefresh'] = ''; //automatic refresh 	
		$params['knowledgeBaseModule'] = 'y'; //by default activate Knowledge Base module
        $params['DownloadManager'] = 'y'; //by default activate Download Manager module
		$params['workReporting'] = 'y'; //by default activate work reporting module
		
    	$db =& $this->state->getByRef('db');
    	    	 
    	foreach ($params as $name => $val) {
	    	$sql = "INSERT INTO settings (setting_id, setting_key, setting_value) 
					VALUES ('" . md5($name) . "', '" . $name . "', '" . $val . "')";
    		$ret = $db->execute($sql);
	        if(QUnit_Object::isError($ret)) {
	        	$this->set('errorMessage',  $ret->get('errorMessage'));
				return false;
			}
    	}
        return true;
    }
    
    function computeLastVersion() {
    	$dbLevel = '0';
    	if ($dh = opendir("db/updates")) {
    		while (($file = readdir($dh)) !== false) {
    			if ($file == "." || $file == "..") {
    				continue;
    			}
    			if(version_compare($file, $dbLevel) > 0) {
					$dbLevel = $file;
    			}
    		}
    	}
    	return $dbLevel;
    }
}
?>