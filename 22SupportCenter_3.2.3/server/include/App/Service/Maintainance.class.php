<?php
/**
*   Handler class for system Maintainance
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Maintainance extends QUnit_Rpc_Service {

	function initMethod($params) {
		$method = $params->get('method');

		switch($method) {
			default:
				return false;
				break;
		}
	}
	
	function canExecute($key_name, $timeout) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		//check if there is lastOptimization Parameter
		$params = $this->createParamsObject();
		$params->set('setting_key', $key_name);
		if ($this->callService('Settings', 'getSettingsList', $params)) {
			$result = & $response->getByRef('result');
			if ($result['count'] > 0) {
				$setting = $result['rs']->getRows($result['md']);
    			$setting = $setting[0];
				if (strtotime($setting['setting_value']) < (time() - $timeout)) {
					return true;
				} else {
					return false;
				}
			} else {
				return true;
			}
		}
	}
	
	function getRandomTime($min = 0, $max = 86400) {
		return rand($min, $max);
	}
	
	/*
	 * Optimize tables, which have more than 1% of wasted space
	 */
	function optimizeTables() {
        $db =& $this->state->getByRef('db');
	    if (!$this->canExecute('lastOptimization', $this->getRandomTime(300000, 600000))) {
			return true;
		} else {
			$paramsSettings = $this->createParamsObject();
			$paramsSettings->setField('lastOptimization', $db->getDateString());
			$this->callService('Settings', 'updateSetting', $paramsSettings);
		}
        return $this->runOptimizeTables();		
	}

    function runOptimizeTables() {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        
        $sql = "SHOW TABLE STATUS";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'select');
        if(QUnit_Object::isError($sth)) {
            $this->state->log('error', $this->state->lang->get('dbError').$sth->get('errorMessage'), 'Maintainance');
            return false;
        }
        
        $arr_table_names = array();
        while ($row = $sth->fetchArray()) {
            //optimize table only if there is more than 1% of data wasted
            if ($row['Data_length'] > 0 && ($row['Data_free']/$row['Data_length'] > 0.01)) {
                $arr_table_names[] = $row['Name'];
            }
        }
        
        foreach ($arr_table_names as $table) {
            $sql = "OPTIMIZE TABLE $table";
            $sth = $db->execute($sql);
            $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'select');
            if(QUnit_Object::isError($sth)) {
                $this->state->log('error', $this->state->lang->get('dbError').$sth->get('errorMessage'), 'Maintainance');
                return false;
            }
        }
        return true;
    }
	
	
	/*
	 * Analyze all tables once a day
	 */
	function analyzeTables() {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		
		if (!$this->canExecute('lastAnalyzeTable', $this->getRandomTime(300000, 600000))) {
			return true;
		} else {
			$paramsSettings = $this->createParamsObject();
			$paramsSettings->setField('lastAnalyzeTable', $db->getDateString());
			$this->callService('Settings', 'updateSetting', $paramsSettings);
		}
		
		
		$sql = "SHOW TABLE STATUS";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'select');
        if(QUnit_Object::isError($sth)) {
        	$this->state->log('error', $this->state->lang->get('dbError').$sth->get('errorMessage'), 'Maintainance');
            return false;
        }
        
        $arr_table_names = array();
        while ($row = $sth->fetchArray()) {
       		$arr_table_names[] = $row['Name'];
        }
		
        foreach ($arr_table_names as $table) {
        	$sql = "ANALYZE TABLE $table";
	        $sth = $db->execute($sql);
	        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'select');
	        if(QUnit_Object::isError($sth)) {
	        	$this->state->log('error', $this->state->lang->get('dbError').$sth->get('errorMessage'), 'Maintainance');
	            return false;
	        }
        }
        return true;
	}
	
	
	/*
	 * Cleanup not used files
	 */
	function cleanupFiles() {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		
		if (!$this->canExecute('lastCleanupFiles', $this->getRandomTime(300000, 600000))) {
			return true;
		} else {
			$paramsSettings = $this->createParamsObject();
			$paramsSettings->setField('lastCleanupFiles', $db->getDateString());
			$this->callService('Settings', 'updateSetting', $paramsSettings);
		}
		
		$files = QUnit::newObj('App_Service_Files');
		$files->state = $this->state;
		$files->response = $response;
		$files->cleanupFiles();
		
        return true;
	}
	
	/*
	 * Cleanup log
	 */
	function cleanupLog() {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		
		if (!$this->canExecute('lastCleanupLog', $this->getRandomTime(86400, 172800))) {
			return true;
		} else {
			$paramsSettings = $this->createParamsObject();
			$paramsSettings->setField('lastCleanupLog', $db->getDateString());
			$this->callService('Settings', 'updateSetting', $paramsSettings);
		}
		
		$params = $this->createParamsObject();
		if (!$this->callService('Logs', 'cleanupLog', $params)) {
			$this->state->log('error', 'Failed to cleanup Logs', 'Log');
		}
				
        return true;
	}
	
	
	function generateKnowledgeBase() {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		
		//do nothing if Knowledge Base module is not activated
    	if ($this->state->config->get('knowledgeBaseModule') != 'y') {
    		return true;
    	}
		
    	
		//update KB every year also in case there was no change
		if (!$this->canExecute('lastKBUpdate', $this->getRandomTime(31530000, 31536000))) {
			return true;
		} else {
			$paramsSettings = $this->createParamsObject();
			$paramsSettings->setField('lastKBUpdate', $db->getDateString());
			$this->callService('Settings', 'updateSetting', $paramsSettings);
		}
		
		$kbOfflineContent = QUnit::newObj('App_Service_KBOffline');
		$kbOfflineContent->state = $this->state;
		$kbOfflineContent->response = $response;
		return $kbOfflineContent->generateKnowledgeBaseFiles();
	}
	
	function recomputeMissingIndex() {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		$keyWords = QUnit::newObj('App_Service_KeyWords');
		$keyWords->state = $this->state;
		$keyWords->response = $response;
		
		//do nothing if Knowledge Base module is not activated
    	if ($this->state->config->get('knowledgeBaseModule') == 'y') {

			//update KB every year also in case there was no change
			if (!$this->canExecute('lastWordsUpdate', $this->getRandomTime(86400, 89000))) {
			} else {
				$paramsSettings = $this->createParamsObject();
				$paramsSettings->setField('lastWordsUpdate', $db->getDateString());
				$this->callService('Settings', 'updateSetting', $paramsSettings);
		    	$sql = "UPDATE kb_items SET is_indexed='n'";
		    	$sth = $db->execute($sql);
		    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
		    	if(QUnit_Object::isError($sth)) {
		            $response->set('result', null);
		            $response->set('error', $this->state->lang->get('dbError').$sth->get('errorMessage'));
		            return false;
		        }
			}
    		
    		//recompute KB index
			$keyWords->recomputeMissingKBWordIndex();
    	}
	}
	
	/*
	 * Execute all escalation rules
	 */
	function executeEscalationRules() {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		
		
		$files = QUnit::newObj('App_Service_EscalationRules');
		$files->state = $this->state;
		$files->response = $response;
		
		global $serverDeleteTickets;
		$serverDeleteTickets = true;
		$files->runAllRules();
		$serverDeleteTickets = false;
		
        return true;
	}
	
	
}
?>
