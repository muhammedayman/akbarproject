<?php
/**
*   Handler class for Reports
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Reports extends QUnit_Rpc_Service {

	function initMethod($params) {
		$method = $params->get('method');

		switch($method) {
			case 'ticketsReport':
			case 'getWorkTimeReport':
				return $this->callService('Users', 'authenticateAgent', $params);
				break;
			default:
				return false;
				break;
		}
	}

	function initDay($from, $to) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		
		if (strtotime($from) > strtotime($to)) {
			return false;
		}
		
		$sql = "INSERT IGNORE INTO statistic_days  VALUES ";
		
		for ($i=strtotime($from); $i<= strtotime($to); $i = $i + (strtotime('2007-1-2') - strtotime('2007-1-1'))) {
			$sql .= ($i == strtotime($from) ? '' : ',') . "('" . date("Y-m-d", $i) . "')"; 
		}
		
   		$sth = $db->execute($sql);
   		$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
   		if(!$this->_checkDbError($sth)) {
   			$response->set('error', $sth->get('errorMessage'));
   			$this->state->log('error', $sth->get('errorMessage'), 'Reports');
   			return false;
   		}
		
		return true;		
	}
	
	function ticketsReport($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		$result = array();
		$session = QUnit::newObj('QUnit_Session');
		
		if ($session->getVar('userType') != 'a') {
			$params->set('user_id', $session->getVar('userId'));
		}
		
		if (!strlen($params->get('date_from'))) {
			$params->set('date_from', $db->getDateString(mktime(0, 0, 0, date("m")-1, date("d"),  date("Y"))));
		}
		if (!strlen($params->get('date_to'))) {
			$params->set('date_to', $db->getDateString());
		}
		
		if (!$this->initDay($params->get('date_from'), $params->get('date_to'))) {
			return false;
		}

		switch($params->get('group_by')) {
			case 'weekday':
				$date_format = "DATE_FORMAT(day_date, '%W')";
				break;
			case 'week':
				$date_format = "DATE_FORMAT(day_date, '%v %Y')";
				break;
			case 'month':
				$date_format = "DATE_FORMAT(day_date, '%M %Y')";
				break;
			case 'year':
				$date_format = "DATE_FORMAT(day_date, '%Y')";
				break;
			default:
				$date_format = "DATE_FORMAT(day_date, '%e %b %Y')";
				break;
		}
		
		
		
		$columns = "$date_format as category_name";
		$from = "statistic_days";
		$where = "day_date >= DATE('".$db->escapeString($params->get('date_from'))."')";
		$where .= " AND day_date <= DATE('".$db->escapeString($params->get('date_to'))."')";
		
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('table', 'statistic_days');
		$params->set('order', 'day_date');
		$params->set('where', $where);
		$params->set('count', 'no');
		$params->set('group', $date_format);
		
		if ($ret = $this->callService('SqlTable', 'select', $params)) {
			$result['categories'] = $response->get('result');
		} else {
    		$response->set('error', $this->state->lang->get('failedTicketsReport'), 'Reports');
    		return false;
		}

		//STATUS NEW
		$columns = $date_format . ' as category_name, ts.new_status, count(ts.created) as changes_count';
		$from = "statistic_days sd
				INNER JOIN ticket_changes ts ON (DATE(ts.created) = sd.day_date)
				INNER JOIN tickets t ON (ts.ticket_id = t.ticket_id)";

		$where = "ts.new_status IS NOT NULL AND ts.new_status <> ''";

		if($id = $params->get('user_id')) {
			if (is_array($id)) {
				$ids = '';
				foreach ($id as $filter_value) {
					if (strlen($filter_value)) {
						$ids .= (strlen($ids) ? ',' : '') . "'" . $db->escapeString($filter_value) . "'";
					}
				}
				if (strlen($ids)) {
					$where .= " and ts.created_by_user_id IN (".$ids.")";
				}
			} else {
				$where .= " and ts.created_by_user_id = '".$db->escapeString($id)."'";
			}
		}
		
		if($id = $params->get('queue_id')) {
			if (is_array($id)) {
				$ids = '';
				foreach ($id as $filter_value) {
					if (strlen($filter_value)) {
						$ids .= (strlen($ids) ? ',' : '') . "'" . $db->escapeString($filter_value) . "'";
					}
				}
				if (strlen($ids)) {
					$where .= " and t.queue_id IN (".$ids.")";
				}
			} else {
				$where .= " and t.queue_id = '".$db->escapeString($id)."'";
			}
		}
		
		if($id = $params->get('date_from')) {
			$where .= " AND sd.day_date >= DATE('".$db->escapeString($params->get('date_from'))."')";
		}
		if($id = $params->get('date_to')) {
			$where .= " AND sd.day_date <= DATE('".$db->escapeString($params->get('date_to'))."')";
		}
		
		if (is_array($arrCustomFields = $params->get('custom_fields')) && !empty($arrCustomFields)) {
			$objFields = QUnit::newObj('App_Service_Fields');
			$objFields->state = $this->state;
			$objFields->response = $response;
			$arrFields = $objFields->getCustomFieldsRowsArray(null);
			foreach ($arrCustomFields as $customField) {
				if ($customField[1] != "" && isset($arrFields[$customField[0]])) {
					$where .= " AND t.ticket_id IN (SELECT ticket_id FROM custom_values cv WHERE t.ticket_id = cv.ticket_id AND cv.field_id='" . $db->escapeString($customField[0]) . "'";
					switch($arrFields[$customField[0]]['field_type']) {
						case 'a':
						case 't':
							$where .= " AND cv.field_value LIKE '%" . $db->escapeString($customField[1]) . "%'";
							break;
						case 'm':
							$where .= " AND (";
							$arrValues = explode(',', $customField[1]);
							$whereMultiple = '0';
							foreach ($arrValues as $val) {
								if (strlen($val)) {
									$whereMultiple .= " OR cv.field_value LIKE '%" . $db->escapeString($val) . "%'";
								}
							}
							$where .= $whereMultiple . ")";
							break;
						case 'c':
						case 'o':
							$where .= " AND cv.field_value = '" . $db->escapeString($customField[1]) . "'";
					}
				    $where .= ")";
				}
			}
		}
		
		
		
		$group = "category_name ,ts.new_status";
		
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('group', $group);
		$params->set('table', 'ticket_changes');
		$params->set('order', 'sd.day_date');
		$params->set('where', $where);
		$params->set('count', 'no');
		if ($ret = $this->callService('SqlTable', 'select', $params)) {
			$result['statuses'] = $response->get('result');
		} else {
    		$response->set('error', $this->state->lang->get('failedTicketsReport'), 'Reports');
    		return false;
		}
		
		//MAILS
		$columns = $date_format . ' as day_date, count(m.mail_id) as mails';
		$from = "statistic_days sd  
				INNER JOIN mails m ON (m.created_date = sd.day_date)
				INNER JOIN tickets t ON (m.ticket_id = t.ticket_id)
 				INNER JOIN mail_users mu ON (mu.mail_id = m.mail_id AND mu.mail_role='from_user')
				INNER JOIN users u ON (mu.user_id = u.user_id AND u.user_type IN ('a', 'g')) ";

		$where = "1";
		
		if($id = $params->get('user_id')) {
			if (is_array($id)) {
				$ids = '';
				foreach ($id as $filter_value) {
					if (strlen($filter_value)) {
						$ids .= (strlen($ids) ? ',' : '') . "'" . $db->escapeString($filter_value) . "'";
					}
				}
				if (strlen($ids)) {
					$where .= " and mu.user_id IN (".$ids.")";
				}
			} else {
				$where .= " and mu.user_id = '".$db->escapeString($id)."'";
			}
		}
		
		if($id = $params->get('queue_id')) {
			if (is_array($id)) {
				$ids = '';
				foreach ($id as $filter_value) {
					if (strlen($filter_value)) {
						$ids .= (strlen($ids) ? ',' : '') . "'" . $db->escapeString($filter_value) . "'";
					}
				}
				if (strlen($ids)) {
					$where .= " and t.queue_id IN (".$ids.")";
				}
			} else {
				$where .= " and t.queue_id = '".$db->escapeString($id)."'";
			}
		}
		
		if (is_array($arrCustomFields = $params->get('custom_fields')) && !empty($arrCustomFields)) {
			$objFields = QUnit::newObj('App_Service_Fields');
			$objFields->state = $this->state;
			$objFields->response = $response;
			$arrFields = $objFields->getCustomFieldsRowsArray(null);
			foreach ($arrCustomFields as $customField) {
				if ($customField[1] != "" && isset($arrFields[$customField[0]])) {
				    $where .= " AND t.ticket_id IN (SELECT ticket_id FROM custom_values cv WHERE t.ticket_id = cv.ticket_id AND cv.field_id='" . $db->escapeString($customField[0]) . "'";
					switch($arrFields[$customField[0]]['field_type']) {
						case 'a':
						case 't':
							$where .= " AND cv.field_value LIKE '%" . $db->escapeString($customField[1]) . "%'";
							break;
						case 'm':
							$where .= " AND (";
							$arrValues = explode(',', $customField[1]);
							$whereMultiple = '0';
							foreach ($arrValues as $val) {
								if (strlen($val)) {
									$whereMultiple .= " OR cv.field_value LIKE '%" . $db->escapeString($val) . "%'";
								}
							}
							$where .= $whereMultiple . ")";
							break;
						case 'c':
						case 'o':
							$where .= " AND cv.field_value = '" . $db->escapeString($customField[1]) . "'";
					}
				    $where .= ")";
				}
			}
		}
		
		
		if($id = $params->get('date_from')) {
			$where .= " AND sd.day_date >= DATE('".$db->escapeString($params->get('date_from'))."')";
		}
		if($id = $params->get('date_to')) {
			$where .= " AND sd.day_date <= DATE('".$db->escapeString($params->get('date_to'))."')";
		}
		
		$group = $date_format;
		
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('group', $group);
		$params->set('table', 'mails');
		$params->set('order', 'sd.day_date');
		$params->set('where', $where);
		if ($ret = $this->callService('SqlTable', 'select', $params)) {
			$result['mails'] = $response->get('result');
		} else {
    		$response->set('error', $this->state->lang->get('failedTicketsReport'), 'Reports');
    		return false;
		}
		
		$response->set('result', $result);
		return true;
	}
	
	function getWorkTimeReport($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');

		$session = QUnit::newObj('QUnit_Session');
		
		if ($session->getVar('userType') != 'a') {
			$params->set('user_id', $session->getVar('userId'));
		}
		
		if (!strlen($params->get('date_from'))) {
			$params->set('date_from', $db->getDateString(mktime(0, 0, 0, date("m")-1, date("d"),  date("Y"))));
		}
		if (!strlen($params->get('date_to'))) {
			$params->set('date_to', $db->getDateString());
		}
		
		if (!$this->initDay($params->get('date_from'), $params->get('date_to'))) {
			return false;
		}

		switch($params->get('group_by')) {
			case 'weekday':
				$date_format = "DATE_FORMAT(day_date, '%W')";
				break;
			case 'week':
				$date_format = "DATE_FORMAT(day_date, '%v %Y')";
				break;
			case 'month':
				$date_format = "DATE_FORMAT(day_date, '%M %Y')";
				break;
			case 'year':
				$date_format = "DATE_FORMAT(day_date, '%Y')";
				break;
			default:
				$date_format = "DATE_FORMAT(day_date, '%e %b %Y')";
				break;
		}

		$columns = $date_format . ' as category_name, SUM(UNIX_TIMESTAMP(CASE WHEN l.logout IS NULL THEN last_request ELSE l.logout END) - UNIX_TIMESTAMP(login)) as work_time';
		
		$where_left = '';
		if($id = $params->get('user_id')) {
			if (is_array($id)) {
				$ids = '';
				foreach ($id as $filter_value) {
					if (strlen($filter_value)) {
						$ids .= (strlen($ids) ? ',' : '') . "'" . $db->escapeString($filter_value) . "'";
					}
				}
				if (strlen($ids)) {
					$where_left = " and l.user_id IN (".$ids.")";
				}
			} else {
				$where_left = " and l.user_id = '".$db->escapeString($id)."'";
			}
		}
		
		
		$from = "statistic_days sd
				LEFT JOIN logins l ON (DATE(l.login) = sd.day_date$where_left)
				LEFT JOIN users u ON (l.user_id = u.user_id)";

		$where = "1";

		
		if($id = $params->get('date_from')) {
			$where .= " AND sd.day_date >= DATE('".$db->escapeString($params->get('date_from'))."')";
		}
		if($id = $params->get('date_to')) {
			$where .= " AND sd.day_date <= DATE('".$db->escapeString($params->get('date_to'))."')";
		}
		
		if ($params->get('order') == 'category_name') {
			$params->set('order', 'day_date');
		}
		
		
		$params->set('columns', $columns);
		$params->set('count', 'no');
		$params->set('from', $from);
		$params->set('group', $date_format);
		$params->set('table', 'statistic_days');
		$params->set('where', $where);
		if ($ret = $this->callService('SqlTable', 'select', $params)) {
			$result = $response->get('result');
        	$response->setResultVar('count', count($result['rs']->rows));
        	return $ret;
		} else {
			return false;
		}
	}
	
}
?>
