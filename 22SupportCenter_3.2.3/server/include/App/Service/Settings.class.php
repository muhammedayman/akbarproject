<?php
/**
 *   Handler class for Settings
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Settings extends QUnit_Rpc_Service {

	function initMethod($params) {
		$method = $params->get('method');

		switch($method) {
			case 'getSettingsList':
			case 'updateSetting':
				return $this->callService('Users', 'authenticate', $params);
				break;
			default:
				return false;
				break;
		}
	}
	
	function initSettings($params) {
		if($id = $params->get('setting_key')) {
			if (is_array($id)) {
				foreach ($id as $key) {
					$this->initSetting($key);
				}
			} else {
				$this->initSetting($id);
			}
		}
	}

	function initSetting($id) {
		switch($id) {
			case 'knowledgeBaseURL':
				if (!strlen($this->state->config->get('knowledgeBaseURL'))) {
					$this->insertSetting('knowledgeBaseURL', '', $this->state->config->get('applicationURL') . 'knowledgebase/');
				}
				break;
			case 'knowledgeBasePath':
				if (!strlen($this->state->config->get('knowledgeBasePath'))) {
					$this->insertSetting('knowledgeBasePath', '', str_replace('\\', '/', dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/knowledgebase/'));
				}
				break;
		}
		return true;
	}
	
	function getSettingsList($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		$session = QUnit::newObj('QUnit_Session');

		$this->initSettings($params);
		
		$params->set('columns', "setting_key, setting_value");
		$params->set('from', "settings");
		$where = "";

		//user can see just own settings
		if((strlen($session->getVar('userId')) && $session->getVar('userType') != 'a') || $params->get('user_id')) {
			$where .= "user_id = '".$db->escapeString($session->getVar('userId'))."'";
		} else {
			
			$where .= "(user_id IS NULL OR user_id = '')";
		}
		if($id = $params->get('setting_key')) {
			$keys = '';
			if (is_array($id)) {
				foreach ($id as $key) {
					$keys .= (strlen($keys) ? ',' : '') . "'" . $db->escapeString($key) . "'";
				}
				$where .= " and setting_key IN (". $keys . ")";
			} else {
				$where .= " and setting_key = '".$db->escapeString($id)."'";
			}
		}

		$params->set('where', $where);
		$params->set('table', 'settings');
		return $this->callService('SqlTable', 'select', $params);
	}

	/**
	 * Update Setting
	 *
	 * @param QUnit_Rpc_Params $params
	 * @return unknown
	 */
	function updateSetting($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		$session = QUnit::newObj('QUnit_Session');

    	if ($this->isDemoMode()) {
    		$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));
    		return false;
    	}
		
		//user can update just own settings
		if((strlen($session->getVar('userId')) && $session->getVar('userType') != 'a') || $params->get('user_id')) {
			$where = "user_id = '".$db->escapeString($session->getVar('userId'))."'";
			$user_id = $session->getVar('userId');
		} else {
			$where = "(user_id IS NULL OR user_id = '')";
			$user_id = '';
		}

		$params->set('table', 'settings');

		$fields = $params->get('fields');
		
		//convert object to array
		if (is_object($fields)) {
			$fields = get_object_vars($fields);
		}
		
		foreach ($fields as $key => $value) {
			if ($this->insertSetting($key, $user_id, $value)) {
				$params->setField('setting_value', $value);
				$params->set('where', $where . " AND setting_key='" . $db->escapeString($key) . "'");
				if (!($ret = $this->callService('SqlTable', 'update', $params))) {
					return $ret;
				}
			} else {
				return false;
			}
		}
		return true;
	}

	function insertSetting($key, $user_id, $value) {
		$db = $this->state->get('db');
		$response =& $this->getByRef('response');
		
		$sql = "INSERT IGNORE INTO settings (setting_id, setting_key, user_id, setting_value) 
				VALUES ('" . md5($key . $user_id) . "', 
						'" . $db->escapeString($key) . "', " . 
				(strlen($user_id) ? "'".$db->escapeString($user_id)."'" : 'NULL')  . 
				", '" . $db->escapeString($value) . "')";
		$sth = $db->execute($sql);
		$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
		if(!$this->_checkDbError($sth)) {
			return false;
		}
		return true;
	}
	
	
	function deleteSetting($params) {
		$response =& $this->getByRef('response');
		$db = $this->state->get('db');
		$session = QUnit::newObj('QUnit_Session');
		
		if (!$params->get('setting_key')) {
		    return false;
		}
		
		$where = "setting_key='" . $db->escapeString($params->get('setting_key')) . "'";
		if ($params->get('user_id')) {
		    $where .= "user_id='" . $db->escapeString($params->get('user_id')) . "'";
		}
		
		$params->set('table', 'settings');
		$params->set('where', $where);
		return $this->callService('SqlTable', 'delete', $params);
	}
	
	
}
?>