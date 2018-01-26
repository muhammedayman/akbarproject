<?php
/**
*   Handler class for Priorities management
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Priorities extends QUnit_Rpc_Service {

    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
			case 'deletePriority':
			case 'insertPriority':
			case 'updatePriority':
			case 'getPriority':
				return $this->callService('Users', 'authenticateAdmin', $params);
				break;
			case 'getPrioritiesList':
				return $this->state->config->get('showPriorityInSubmitForm') == 'y' || $this->callService('Users', 'authenticate', $params);
				break;
			default:
                return false;
                break;
        }
    }
    
    function getPrioritiesList($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $columns = "priority, priority_name";
        $from = "priorities";
        $where = "1";
        if(strlen($params->get('priority'))) {
        	$id = $params->get('priority');
            $where .= " and priority = ".$db->escapeString($id);
        }

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        if (!$params->get('order')) {
        	$params->set('order', 'priority');
        }
        $params->set('table', 'priorities');
        return $this->callService('SqlTable', 'select', $params);
    }
    
    function getPrioritiesArray() {
        $response =& $this->getByRef('response');
        $params = $this->createParamsObject();
    	if ($this->getPrioritiesList($params)) {
   			$res = & $response->getByRef('result');
			$prios = $res['rs']->getRows($res['md']);
			$arrRet = array();
			foreach ($prios as $arrRow) {
				$arrRet[$arrRow['priority']*1] = $arrRow['priority_name'];
			}
			return $arrRet;
    	}
    	return array();
    }

    function getPriority($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

    	if(!$params->check(array('priority'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
        
        return $this->getPrioritiesList($params);
    }
    
    /**
     *  delete Priority
     *
     *  @param string table
     *  @param string id id of task
     *  @return boolean
     */
    function deletePriority($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');		
    	
    	$ids = explode('|',$params->get('priority'));
    	
    	if ($this->isDemoMode('Priorities', $ids)) {
        	$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));     	
			return false;
		}
    	
    	$where_ids = '';
    	foreach ($ids as $id) {
    		if (strlen(trim($id))) {
    			$where_ids .= (strlen($where_ids) ? ', ': '');
    			$where_ids .= $db->escapeString(trim($id)*1);
    		}
    	}
    	 
    	if(!$params->check(array('priority')) || !strlen($where_ids)) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
    	
    	//check if priority is not used by any ticket
    	$sql = "SELECT * FROM tickets WHERE priority IN (" . $where_ids . ")";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(!$this->_checkDbError($sth)) {
    		$response->set('error', $sth->get('errorMessage'));
    		$this->state->log('error', $sth->get('errorMessage'), 'Priority');
    		return false;
    	} else {
    		if ($sth->rowCount() > 0) {
    			$response->set('error', $this->state->lang->get('priorityIsUsedInTickets'));
    			$this->state->log('error', $response->get('error'), 'Priorities');
    			return false;
    		}
    	}
    	
    	$params->set('table', 'priorities');
    	$params->set('where', "priority IN (" . $where_ids . ")");
    	return $this->callService('SqlTable', 'delete', $params);
	}    
    	
	/**
	 * Insert mail priority
	 *
	 * @param QUnit_Rpc_Params $params
	 */
    function insertPriority($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');
    	
        if(!$params->checkFields(array('priority', 'priority_name'))) {
        	$response->set('error', $this->state->lang->get('missingMandatoryFields'));
        	return false;
        }
    	
    	$params->set('table', 'priorities');
    	return $this->callService('SqlTable', 'insert', $params);
    }
   
    
    /**
     * Update Priority
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function updatePriority($params) {
    	$response =& $this->getByRef('response');
    	$db =& $this->state->getByRef('db');
    	
    	if(!$params->check(array('priority'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}

    	if ($this->isDemoMode('Priorities', $params->get('priority'))) {
    		$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));
    		return false;
    	}
    	 
    	if(!$params->checkFields(array('priority_name'))) {
    		$response->set('error', $this->state->lang->get('missingMandatoryFields'));
        	return false;
        }
    	
    	$params->set('table', 'priorities');
    	$params->set('where', "priority = ".$db->escapeString($params->get('priority')*1));
    	return $this->callService('SqlTable', 'update', $params);
    }
}
?>