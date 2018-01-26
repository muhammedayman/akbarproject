<?php
/**
*   Handler class for WorkReporting
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_WorkReports extends QUnit_Rpc_Service {

	function initMethod($params) {
		$method = $params->get('method');

		switch($method) {
			case 'insertReport':
			case 'getBillingTimeList':
			case 'getBillingTimeSum':
				return $this->callService('Users', 'authenticateAgent', $params);
				break;
			case 'deleteReport':
			case 'updateReport':
				return $this->callService('Users', 'authenticateAdmin', $params);
				break;
			default:
				return false;
				break;
		}
	}

	/**
	 * Create report entry
	 */
	function insertReport($params) {
		$response =& $this->getByRef('response');
		$db = $this->state->get('db');
		$session = QUnit::newObj('QUnit_Session');
		
   		if (!strlen($session->getId()) || !strlen($session->getVar('loginId')) || 
   			$session->getVar('userType') == 'u' || !strlen($params->getField('ticket_id'))) {
    			return false;
   		}
   		
		$params->set('table', 'work_reports');
		
		$params->setField('work_id', 0);
		$params->setField('approved', 'n');
		$params->setField('created', $db->getDateString());
		$params->setField('login_id', $session->getVar('loginId'));
		$params->setField('user_id', $session->getVar('userId'));
		
		if (!strlen($params->getField('billing_time'))) {
			$params->setField('billing_time', 0);
		}

		if (!strlen($params->getField('work_time'))) {
			$params->setField('work_time', 0);
		}
		
		if (!strlen($params->getField('ticket_time'))) {
			$params->setField('ticket_time', 0);
		}
		
		return $this->callService('SqlTable', 'insert', $params);
	}
	
 	/**
     *  delete report
     *
     *  @return boolean
     */
    function deleteReport($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');
    	
    	$ids = explode('|',$params->get('work_id'));
    	
    	$where_ids = '';
    	foreach ($ids as $id) {
    		if (strlen(trim($id))) {
    			$where_ids .= (strlen($where_ids) ? ', ': '');
    			$where_ids .= "'" . $db->escapeString(trim($id)) . "'";
    		}
    	}
    	
    	if(!$params->check(array('work_id')) || !strlen($where_ids)) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}

    	$params->set('table', 'work_reports');
    	$params->set('where', "work_id IN (" . $where_ids . ")");
    	return $this->callService('SqlTable', 'delete', $params);
    }

    /**
     * Update Work report
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function updateReport($params) {
    	$response =& $this->getByRef('response');
    	$db =& $this->state->getByRef('db');
    	
    	$ids = explode('|',$params->get('work_id'));
    	
    	$where_ids = '';
    	foreach ($ids as $id) {
    		if (strlen(trim($id))) {
    			$where_ids .= (strlen($where_ids) ? ', ': '');
    			$where_ids .= "'" . $db->escapeString(trim($id)) . "'";
    		}
    	}
    	
    	if(!$params->check(array('work_id')) || !strlen($where_ids)) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}

    	$params->set('table', 'work_reports');
    	$params->set('where', "work_id IN (" . $where_ids . ")");
    	return $this->callService('SqlTable', 'update', $params);
    }
    
    function getBillingTimeWhere($params) {
		$session = QUnit::newObj('QUnit_Session');
    	$db =& $this->state->getByRef('db');
    	$where = "1";

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

		if($id = $params->get('groupid')) {
			if (is_array($id)) {
				$ids = '';
				foreach ($id as $filter_value) {
					if (strlen($filter_value)) {
						$ids .= (strlen($ids) ? ',' : '') . "'" . $db->escapeString($filter_value) . "'";
					}
				}
				if (strlen($ids)) {
					$where .= " and g.groupid IN (".$ids.")";
				}
			} else {
				$where .= " and g.groupid = '".$db->escapeString($id)."'";
			}
		}
		
		
		if ($session->getVar('userType') == 'g') {
			$params->set('user_id', $session->getVar('userId'));
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
					$where .= " and wr.user_id IN (".$ids.")";
				}
			} else {
				$where .= " and wr.user_id = '".$db->escapeString($id)."'";
			}
		}

		if($id = $params->get('customer_email')) {
			$where .= " AND uc.email LIKE '".$db->escapeString($id)."'";
		}
		
		if($id = $params->get('date_from')) {
			$where .= " AND wr.created >= '".$db->escapeString($id)."'";
		}
		if($id = $params->get('date_to')) {
			$where .= " AND wr.created <= '".$db->escapeString($id)."'";
		}
    	return $where;
    }
    
    function getBillingTimeList($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');

		$columns = "work_id, approved, wr.created as reported, billing_time, work_time, ticket_time, note,
					t.created as ticket_created, t.first_subject, t.subject_ticket_id,
					q.name as queue_name,
					ua.email as reporter,
					uc.email as customer,
					g.groupid as groupid,
					g.group_name as group_name";
		$from = "work_reports wr 
				INNER JOIN tickets t ON (wr.ticket_id = t.ticket_id)
				INNER JOIN queues q ON (t.queue_id = q.queue_id)
				INNER JOIN users ua ON (wr.user_id = ua.user_id)
				INNER JOIN users uc ON (t.customer_id = uc.user_id)
				LEFT JOIN groups g ON (g.groupid = uc.groupid)";

		$where = $this->getBillingTimeWhere($params);
		
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('where', $where);
		$params->set('table', 'work_reports');
		return $this->callService('SqlTable', 'select', $params);
    	
    }

    function getBillingTimeSum($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');

		$columns = "SUM(billing_time) as sum_billing_time, SUM(work_time) as sum_work_time, SUM(ticket_time) as sum_ticket_time,
					AVG(billing_time) as avg_billing_time, AVG(work_time) as avg_work_time, AVG(ticket_time) as avg_ticket_time";
		$from = "work_reports wr 
				INNER JOIN tickets t ON (wr.ticket_id = t.ticket_id)
				INNER JOIN users uc ON (t.customer_id = uc.user_id)";

		$where = $this->getBillingTimeWhere($params);
		
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('where', $where);
		$params->set('table', 'work_reports');
		return $this->callService('SqlTable', 'select', $params);
    }
    
}
?>
