<?php
/**
*   Handler class for QueueSignatures
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_QueueSignatures extends QUnit_Rpc_Service {

	function initMethod($params) {
		$method = $params->get('method');

		switch($method) {
			case 'getSignaturesList':
			case 'insertSignature':
			case 'deleteSignature':
				return $this->callService('Users', 'authenticateAgent', $params);
				break;
			default:
				return false;
				break;
		}
	}
	
	function getSignaturesList($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');
		
		$columns = "q.queue_id, s.queue_id as signature_queueid, q.name, s.signature";
		$from = "queues q LEFT JOIN signatures s ON (q.queue_id = s.queue_id AND s.user_id = '" . $db->escapeString($session->getVar('userId')) . "')";
		$where = "1";
		
		if ($session->getVar('userType') != 'a') {
			$where .= " AND (q.public='y' OR q.queue_id IN (SELECT queue_id FROM queue_agents WHERE user_id='" . $db->escapeString($session->getVar('userId')) . "'))";
		}

		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('where', $where);
		$params->set('table', 'queues');
		return $this->callService('SqlTable', 'select', $params);
	}

    function deleteSignature($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');
    	
    	if(!$params->check(array('queue_id', 'user_id'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
    	$ids = explode('|',$params->get('queue_id'));
    	$where_ids = '';
    	foreach ($ids as $id) {
    		if (strlen(trim($id))) {
    			$where_ids .= (strlen($where_ids) ? ', ': '');
    			$where_ids .= "'" . $db->escapeString(trim($id)) . "'";
    		}
    	}
    	
    	$params->set('table', 'signatures');
    	$params->set('where', "queue_id IN (" . $where_ids . ") AND user_id='" . $db->escapeString($params->get('user_id')) . "'");
    	return $this->callService('SqlTable', 'delete', $params);
    }
	
	function insertSignature($params) {
    	$db = $this->state->get('db');
    	$response =& $this->getByRef('response');

    	if ($this->deleteSignature($params) && $params->checkFields(array('signature'))) {
	
	    	//check of mandatory fields
	    	if(!$params->checkFields(array('user_id', 'queue_id'))) {
    			$response->set('error', $this->state->lang->get('noIdProvided'));
	    		return false;
	    	}
	    	 
	    	$params->set('table', 'signatures');
	
	    	return $this->callService('SqlTable', 'insert', $params);
		}
		
		return true;
	}
}
?>