<?php
/**
*   Handler class for Login Logs
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Logins extends QUnit_Rpc_Service {

	function initMethod($params) {
		$method = $params->get('method');

		switch($method) {
			case 'getLoginsList':
				return $this->callService('Users', 'authenticateAgent', $params);
				break;
			default:
				return false;
				break;
		}
	}
	
	
	/*
	 * Return list of log entries defined by input parameters
	 */
	function getLoginsList($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		$session = QUnit::newObj('QUnit_Session');
		
		if ($session->getVar('userType') != 'a') {
			$params->set('user_id', $session->getVar('userId'));
		}
		
		$columns = "u.name, u.email, u.user_id, l.login, l.ip,
				CASE
					WHEN (l.logout IS NULL AND l.last_request > ('" . $db->getDateString() . "' - INTERVAL 900 SECOND)) THEN NULL
					WHEN l.logout IS NULL THEN last_request
					ELSE l.logout
				END as logout, 
				(UNIX_TIMESTAMP(CASE WHEN l.logout IS NULL THEN last_request ELSE l.logout END) - UNIX_TIMESTAMP(login)) as login_time";
		$from = "logins l INNER JOIN users u ON l.user_id = u.user_id";
		$where = "1";

		if ($id = $params->get('ip')) {
			$where .= " AND ip LIKE '" . $db->escapeString($id) ."'";
		}

		if ($id = $params->get('email')) {
			$where .= " AND email LIKE '" . $db->escapeString($id) ."%'";
		}
		if ($id = $params->get('name')) {
			$where .= " AND name LIKE '" . $db->escapeString($id) ."%'";
		}
		
		if ($id = $params->get('login_from')) {
			$where .= " AND login > '" . $db->escapeString($id) ."%'";
		}
		if ($id = $params->get('login_to')) {
			$where .= " AND login < '" . $db->escapeString($id) ."%'";
		}
		
		if($id = $params->get('user_id')) {
			if (is_array($id)) {
				$ids = '';
				foreach ($id as $filter_value) {
					if (strlen($filter_value)) {
						$ids .= (strlen($ids) ? ',' : '') . "'" . $db->escapeString($filter_value) . "'";
					}
				}
				if (strlen($ids)) {
					$where .= " and u.user_id IN (".$ids.")";
				}
			} else {
				$where .= " and u.user_id = '".$db->escapeString($id)."'";
			}
		}
		
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('where', $where);
		$params->set('table', 'logins');
		return $this->callService('SqlTable', 'select', $params);
	}
}
?>
