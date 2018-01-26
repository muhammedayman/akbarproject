<?php
/**
*   Handler class for Gateways
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Gateways extends QUnit_Rpc_Service {

	function initMethod($params) {
		$method = $params->get('method');

		switch($method) {
			case 'updateGateway':
			case 'getGateway':
			case 'getGatewaysList':
			case 'deleteGateway':
			case 'insertGateway':
				return $this->callService('Users', 'authenticateAgent', $params);
				break;
			default:
				return false;
				break;
		}
	}

    /**
     * Update Gateway
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function updateGateway($params) {
    	$response =& $this->getByRef('response');
    	$db =& $this->state->getByRef('db');
    	
    	if(!$params->check(array('gateway_id'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
    	
    	$params->set('table', 'mail_gateways');
    	$params->set('where', "gateway_id = '".$db->escapeString($params->get('gateway_id'))."'");
    	return $this->callService('SqlTable', 'update', $params);
    }

	function getGateway($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		$session = QUnit::newObj('QUnit_Session');

		if(!$params->check(array('gateway_id'))) {
			$response->set('error', $this->state->lang->get('noIdProvided'));
			return false;
		}
		
    	return $this->getGatewaysList($params);
	}

	function insertGateway($params) {
		$response =& $this->getByRef('response');
		$db =& $this->state->getByRef('db');
		$session = QUnit::newObj('QUnit_Session');
   
		$params->setField('gateway_id', 0);
		$params->setField('user_id', $session->getVar('userId'));

		$params->set('table', 'mail_gateways');
		return $this->callService('SqlTable', 'insert', $params);
	}
	
	/*
	 * Return list of Gateways defined by user
	 */
	function getGatewaysList($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		$session = QUnit::newObj('QUnit_Session');
		
		$columns = "gateway_id, mg.name as name, mg.user_id, ticket_owners, statuses, queues, priorities, u.email as uemail, u.name as uname";
		$from = "mail_gateways mg INNER JOIN users u ON (u.user_id = mg.user_id)";
		if ($params->get('gateway_id')) {
			$where .= "mg.gateway_id='" . $db->escapeString($params->get('gateway_id')) . "'";
		} else {
			if ($params->get('admin') && $session->getVar('userType') == 'a') {
				$where = "";
			} else {
				$where = "mg.user_id='" . $db->escapeString($session->getVar('userId')) . "'";
			}
		}
		
		
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('where', $where);
		$params->set('table', 'mail_gateways');
		return $this->callService('SqlTable', 'select', $params);
	}
	
	
 /**
     *  deleteGateway
     *
     *  @param string table
     *  @param string id of Gateway
     *  @return boolean
     */
    function deleteGateway($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');
		$session = QUnit::newObj('QUnit_Session');
    	
    	$ids = explode('|',$params->get('gateway_id'));
    	
    	$where_ids = '';
    	foreach ($ids as $id) {
    		if (strlen(trim($id))) {
    			$where_ids .= (strlen($where_ids) ? ', ': '');
    			$where_ids .= "'" . $db->escapeString(trim($id)) . "'";
    		}
    	}
    	
    	if(!$params->check(array('gateway_id')) || !strlen($where_ids)) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
		if ($session->getVar('userType') != 'a') {
    		$where = "user_id='" . $db->escapeString($session->getVar('userId')) . "'";
		} else {
			$where = '1=1';
		}
    	$where .= " AND gateway_id IN (" . $where_ids . ")";

    	$params->set('table', 'mail_gateways');
    	$params->set('where', $where);
    	return $this->callService('SqlTable', 'delete', $params);
    }
}
?>
