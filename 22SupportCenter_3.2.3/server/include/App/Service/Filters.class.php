<?php
/**
 *   Handler class for Filters
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Filters extends QUnit_Rpc_Service {

	function initMethod($params) {
		$method = $params->get('method');

		switch($method) {
			case 'getFiltersList':
			case 'deleteFilter':
			case 'insertFilter':
			case 'updateFilter':
				return $this->callService('Users', 'authenticateAgent', $params);
				break;
			default:
				return false;
				break;
		}
	}

	function getFiltersList($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');

		$params->set('columns', "*");
		$params->set('from', "filters");
		$where = "1";

		if($id = $params->get('user_id')) {
			$where .= " and (user_id = '".$db->escapeString($id)."' OR is_global='y')";
		}
		if($id = $params->get('filter_id')) {
			$where .= " and filter_id = '".$db->escapeString($id)."'";
		}
		if($id = $params->get('filter_name')) {
			$where .= " and filter_name = '".$db->escapeString($id)."'";
		}
		
		$params->set('where', $where);
		$params->set('table', 'parsing_rules');
		$params->set('order', 'is_global');
		return $this->callService('SqlTable', 'select', $params);
	}
	

	function loadFilter($params) {
    	$response =& $this->getByRef('response');
   	   	$db = $this->state->get('db'); 
        $session = QUnit::newObj('QUnit_Session');
        $filter = false;
    	//load ticket
    	if ($params->check(array('filter_id'))) {
    		if ($ret = $this->callService('Filters', 'getFiltersList', $params)) {
    			$res = & $response->getByRef('result');
    			if ($res['count'] > 0) {
					$filter = $res['rs']->getRows($res['md']);
    				$filter = $filter[0];
    			}
    		}
    	}
    	return $filter;
	    
	}
	
	/**
	 *  deleteFilter
	 *
	 *  @param string table
	 *  @param string id of filter
	 *  @return boolean
	 */
	function deleteFilter($params) {
		$response =& $this->getByRef('response');
		$db = $this->state->get('db');
		$session = QUnit::newObj('QUnit_Session');
		
		$ids = explode('|',$params->get('filter_id'));
   
		$where_ids = '';
		foreach ($ids as $id) {
			if (strlen(trim($id))) {
				$where_ids .= (strlen($where_ids) ? ', ': '');
				$where_ids .= "'" . $db->escapeString(trim($id)) . "'";
			}
		}
		if (!strlen($params->getField('user_id'))) {
			$params->setField('user_id', $session->getVar('userId'));
		}
		
		if (strlen($where_ids)) {
			$where ="filter_id IN (" . $where_ids . ")";
		} else if (strlen($params->get('filter_name'))) {
		    if ($session->getVar('userType') != 'a') {
    		    $where = "filter_name ='" . $db->escapeString($params->get('filter_name')) . "' AND user_id = '" . $db->escapeString($params->get('user_id')) . "'";
		    } else {
	    	    $where = "filter_name ='" . $db->escapeString($params->get('filter_name')) . "' AND (user_id = '" . $db->escapeString($params->get('user_id')) . "' OR is_global='y')";
		    }
		} else {
			$response->set('error', $this->state->lang->get('noIdProvided'));
			return false;			
		}

		if ($session->getVar('userType') != 'a') {
		    $where .= " AND is_global='n'";
		}
		
		$params->set('table', 'filters');
		$params->set('where', $where);
		return $this->callService('SqlTable', 'delete', $params);
	}

	/**
	 * Create filter
	 */
	function insertFilter($params) {
		$response =& $this->getByRef('response');
		$db = $this->state->get('db');
		$session = QUnit::newObj('QUnit_Session');

		if(!$params->checkFields(array('grid_id', 'filter_value', 'filter_name'))) {
			$response->set('error', $this->state->lang->get('noIdProvided'));
			return false;
		}

		if (!strlen($params->getField('user_id'))) {
			$params->setField('user_id', $session->getVar('userId'));
		}
		
		//delete filters with this name
		
		$paramsDelete = $this->createParamsObject();
		$paramsDelete->set('filter_name', $params->get('filter_name'));
		$paramsDelete->set('user_id', $params->get('user_id'));
		if (!$this->callService('Filters', 'deleteFilter', $paramsDelete)) {
			return false;
		}

		if ($session->getVar('userType') != 'a' || $params->getField('is_global') != 'y') {
		    $params->setField('is_global', 'n');
		}
		
		$params->set('table', 'filters');
		$params->setField('filter_id', 0);
		$params->setField('last_used', $db->getDateString());
		return $this->callService('SqlTable', 'insert', $params);
	}

	/**
	 * Update Filter
	 *
	 * @param QUnit_Rpc_Params $params
	 * @return unknown
	 */
	function updateFilter($params) {
		$response =& $this->getByRef('response');
		$db =& $this->state->getByRef('db');
		$session = QUnit::newObj('QUnit_Session');
		
		if(!$params->check(array('filter_id'))) {
			$response->set('error', $this->state->lang->get('noIdProvided'));
			return false;
		}
		
		if ($params->getField('is_global')) {
    		if ($session->getVar('userType') != 'a' || $params->getField('is_global') != 'y') {
    		    $params->setField('is_global', 'n');
    		}
		}

		
		$params->set('table', 'filters');
		$params->set('where', "filter_id = '".$db->escapeString($params->get('filter_id'))."' AND is_global='n'");
		return $this->callService('SqlTable', 'update', $params);
	}
}
?>