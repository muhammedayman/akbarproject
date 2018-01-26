<?php
/**
*   Handler class for Logs
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Logs extends QUnit_Rpc_Service {

	function initMethod($params) {
		$method = $params->get('method');

		switch($method) {
			case 'deleteLog':
			case 'getLogsList':
				return $this->callService('Users', 'authenticateAdmin', $params);
				break;
			case 'getLogUsersList':
				return $this->callService('Users', 'authenticateAgent', $params);
				break;
			default:
				return false;
				break;
		}
	}
	
	
	function getLogUsersList($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');

		
        if(!$params->check(array('log_id'))) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }
		
		$columns = "u.user_id as user_id, u.name as name, u.email as email";
		$from = "users u INNER JOIN log_users lu ON (lu.user_id = u.user_id)";
		if($id = $params->get('log_id')) {
			$where = "log_id = '".$db->escapeString($id)."'";
		}
		
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('where', $where);
		$params->set('table', 'users');
		return $this->callService('SqlTable', 'select', $params);
	}
	
	/*
	 * Return list of log entries defined by input parameters
	 */
	function getLogsList($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');

		$columns = "*";
		$from = "syslogs";
		$where = "1";
		
		
		if($id = $params->get('user_id')) {
			$from .= ', log_users u';
			$where .= " AND syslogs.log_id = u.log_id and u.user_id = '".$db->escapeString($id)."'";
		}
		
		
		if($id = $params->get('log_id')) {
			$where .= " and syslogs.log_id = '".$db->escapeString($id)."'";
		}

		if($id = $params->get('log_level')) {
			if (is_array($id)) {
				$ids = '';
				foreach ($id as $filter_value) {
					if (strlen($filter_value)) {
						$ids .= (strlen($ids) ? ',' : '') . "'" . $db->escapeString($filter_value) . "'";
					}
				}
				if (strlen($ids)) {
					$where .= " and level IN (".$ids.")";
				}
			} else {
				$where .= " and level = '".$db->escapeString($id)."'";
			}
		}
		
		
		if($id = $params->get('log_type')) {
			$where .= " and log_type LIKE '%".$db->escapeString($id)."%'";
		}

		if($id = $params->get('ip')) {
			$where .= " and ip LIKE '%".$db->escapeString($id)."%'";
		}
		
		if($id = $params->get('log_text')) {
			$where .= " and log_text LIKE '%".$db->escapeString($id)."%'";
		}

		
		if($id = $params->get('created_from')) {
			$where .= " AND created > '" . $db->escapeString($id) . "'";
		}
		if($id = $params->get('created_to')) {
			$where .= " AND created < '" . $db->escapeString($id) . "'";
		}
		
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('where', $where);
		$params->set('table', 'mail_accounts');
		return $this->callService('SqlTable', 'select', $params);
	}
	
	/**
	 * Create log entry
	 */
	function insertLog($params) {
		global $_SERVER;

		$response =& $this->getByRef('response');
		$db = $this->state->get('db');
		$params->set('table', 'syslogs');
		
		$params->setField('created', $db->getDateString());
		$params->setField('log_id', 0);

		//set IP address from which is comming request
		if (isset($_SERVER['REMOTE_ADDR']) && strlen($_SERVER['REMOTE_ADDR'])) {
			$params->setField('ip', $_SERVER['REMOTE_ADDR']);
		} else {
			$params->setField('ip', '127.0.0.1');
		}
		
		$user_ids = $params->get('user_ids');
		$emails = $params->get('emails');

		if ($ret = $this->callService('SqlTable', 'insert', $params)) {
			return $this->assignLogToUsers($response->result, $user_ids) && 
			$this->assignLogToUsersByEmail($response->result, $emails);
		} else {
			return $ret;
		}
	}

	/**
	 * Assign log entry to defined users by their email addresses
	 *
	 * @param unknown_type $log_id
	 * @param unknown_type $emails array or simple email identifies users, which should have assigned log entries
	 * @return unknown
	 */
	function assignLogToUsersByEmail($log_id, $emails) {
		$db = $this->state->get('db');
		
		$sql = "INSERT IGNORE INTO log_users (log_id, user_id)
				SELECT $log_id, user_id from users where email IN ( ";
		$values = '';
		if (is_array($emails)) {
			foreach ($emails as $email) {
				$email = trim($email);
				if (strlen($email)) {
					$email = addslashes($email);
					$values .= (strlen($values) ? ',' : '') . "'" . $email . "'";
				}
			}
		} elseif (strlen(trim($emails))) {
			$email = addslashes(trim($emails));
			$values = "'" . $email . "'";
		} else {
			return true;
		}
		if (strlen(trim($values))) {
			$sql .= $values . ")";

			$sth = $db->execute($sql);
			$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
			if(!$this->_checkDbError($sth)) {
				return false;
			}
		}
		return true;
	}
	
	
	/**
	 * Assign log entry to defined users
	 *
	 * @param unknown_type $log_id
	 * @param unknown_type $user_ids
	 * @return unknown
	 */
	function assignLogToUsers($log_id, $user_ids) {
		$db = $this->state->get('db');

		$sql = "INSERT IGNORE INTO log_users (log_id, user_id)
				VALUES ";
		$values = '';
		if (is_array($user_ids)) {
			foreach ($user_ids as $user_id) {
				if (strlen($user_id)) {
					$user_id = addslashes(trim($user_id));
					$values .= (strlen($values) ? ',' : '') . "($log_id, '" . $user_id . "')";
				}
			}
		} elseif (strlen($user_ids)) {
			$user_id = addslashes(trim($user_ids));
			$values = "($log_id, '" . $user_id . "')";
		} else {
			return true;
		}
		if (strlen(trim($values))) {
			$sql .= $values;

			$sth = $db->execute($sql);
			$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
			if(!$this->_checkDbError($sth)) {
				return false;
			}
		}
		return true;
	}
	
  /**
     *  deleteLog
     *
     *  @param string table
     *  @param string id id of task
     *  @return boolean
     */
    function deleteLog($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');
		if ($this->isDemoMode()) {
        	$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));     	
			return false;
		}
    	
    	$ids = explode('|',$params->get('log_id'));
    	
    	$where_ids = '';
    	foreach ($ids as $id) {
    		if (strlen(trim($id))) {
    			$where_ids .= (strlen($where_ids) ? ', ': '');
    			$where_ids .= "'" . $db->escapeString(trim($id)) . "'";
    		}
    	}
    	
    	if(!$params->check(array('log_id')) || !strlen($where_ids)) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}


    	//delete assignments of logs to users
    	$sql = "delete from log_users where log_id IN (" . $where_ids . ")";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(!$this->_checkDbError($sth)) {
    		$response->set('error', $this->state->lang->get('failedDeleteLogUsers') . $params->get('log_id'));
    		$this->state->log('error', $response->get('error') . " -> " . $sth->get('errorMessage') , 'Log');
    		return false;
    	}


    	$params->set('table', 'syslogs');
    	$params->set('where', "log_id IN (" . $where_ids . ")");
    	return $this->callService('SqlTable', 'delete', $params);
    }
     
    /**
     *  Clean old log entries
     *
     *  @param string table
     *  @param string id id of task
     *  @return boolean
     */
    function cleanupLog($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');
    	 
    	$daysToKeep = $this->state->config->get('deleteLogsAfter');
    	if ($daysToKeep && $daysToKeep > 0) {
	    	$sqlLogs = "SELECT log_id FROM syslogs WHERE created < '" . $db->getDateString() . "' - INTERVAL $daysToKeep DAY"; 
	    	
	    	//delete assignments of logs to users
	    	$sql = "delete from log_users where log_id IN (" . $sqlLogs . ")";
	    	$sth = $db->execute($sql);
	    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
	    	if(!$this->_checkDbError($sth)) {
	    		$response->set('error', $this->state->lang->get('failedDeleteLogUsers') . $params->get('log_id'));
	    		$this->state->log('error', $response->get('error') . " -> " . $sth->get('errorMessage') , 'Log');
	    		return false;
	    	}
	    	
	    	
	    	$params->set('table', 'syslogs');
	    	$params->set('where', "created < '" . $db->getDateString() . "' - INTERVAL $daysToKeep DAY");
	    	return $this->callService('SqlTable', 'delete', $params);
    	}
    	return true;
    }    
    
}
?>
