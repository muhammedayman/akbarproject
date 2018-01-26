<?php
/**
*   Handler class for Statuses
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Statuses extends QUnit_Rpc_Service {

	var $systemStatuses = array('n', 'r', 'b', 'c', 'a');
	
    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
			case 'deleteStatus':
			case 'insertStatus':
			case 'updateStatus':
			case 'getStatus':
			case 'getAvailableImages':
				return $this->callService('Users', 'authenticateAdmin', $params);
				break;
			case 'getStatusesList':
				return $this->callService('Users', 'authenticate', $params);
				break;
			default:
                return false;
                break;
        }
    }
    
    function getStatusesList($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $columns = "status, status_name, color, img, due, due_basetime";
        $from = "statuses";
        $where = "1";
        if(strlen($params->get('status'))) {
        	$id = $params->get('status');
            $where .= " and status = '".$db->escapeString($id)."'";
        }
        if(strlen($params->get('due'))) {
        	$id = $params->get('due');
            $where .= " and due = '".$db->escapeString($id)."'";
        }
        
        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        if (!$params->get('order')) {
        	$params->set('order', 'status_name');
        }
        $params->set('table', 'statuses');
        return $this->callService('SqlTable', 'select', $params);
    }

    function getStatusesArray() {
        $response =& $this->getByRef('response');
        $params = $this->createParamsObject();
    	if ($this->getStatusesList($params)) {
   			$res = & $response->getByRef('result');
			$prios = $res['rs']->getRows($res['md']);
			$arrRet = array();
			foreach ($prios as $arrRow) {
				$arrRet[$arrRow['status']] = $arrRow['status_name'];
			}
			return $arrRet;
    	}
    	return array();
    }
    
    function getDueStatusesArray() {
        $response =& $this->getByRef('response');
        $params = $this->createParamsObject();
        $params->set('due', 'y');
    	if ($this->getStatusesList($params)) {
   			$res = & $response->getByRef('result');
			$prios = $res['rs']->getRows($res['md']);
			$arrRet = array();
			foreach ($prios as $arrRow) {
				$arrRet[$arrRow['status']] = $arrRow['due_basetime'];
			}
			return $arrRet;
    	}
    	return array();
    }
    
    function getStatus($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

    	if(!$params->check(array('status'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
        
        return $this->getStatusesList($params);
    }
    
    /**
     *  delete Status
     *
     *  @param string table
     *  @param string id id of task
     *  @return boolean
     */
    function deleteStatus($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');		
    	
    	$ids = explode('|',$params->get('status'));
    	
    	if ($this->isDemoMode('Statuses', $ids)) {
        	$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));     	
			return false;
		}
    	
    	$where_ids = '';
    	foreach ($ids as $id) {
    		if (strlen(trim($id))) {
    			
    			if (in_array($id, $this->systemStatuses)) {
		    		$response->set('error', $this->state->lang->get('notAllowedToDeleteSystemStatus'));
		    		return false;
    			}
    			
    			$where_ids .= (strlen($where_ids) ? ', ': '');
    			$where_ids .= "'" . $db->escapeString(trim($id)) . "'";
    		}
    	}
    	 
    	if(!$params->check(array('status')) || !strlen($where_ids)) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
    	
    	//TODO: check if statuses are not in list of SYSTEM STATUSES
    	
    	
    	//check if status is not used by any ticket
    	$sql = "SELECT * FROM tickets WHERE status IN (" . $where_ids . ")";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(!$this->_checkDbError($sth)) {
    		$response->set('error', $sth->get('errorMessage'));
    		$this->state->log('error', $sth->get('errorMessage'), 'Statuses');
    		return false;
    	} else {
    		if ($sth->rowCount() > 0) {
    			$response->set('error', $this->state->lang->get('statusIsUsedInTickets'));
    			$this->state->log('error', $response->get('error'), 'Statuses');
    			return false;
    		}
    	}
    	
    	$params->set('table', 'statuses');
    	$params->set('where', "status IN (" . $where_ids . ")");
    	return $this->callService('SqlTable', 'delete', $params);
	}    
    	
	/**
	 * Insert Status 
	 * 
	 * @param QUnit_Rpc_Params $params
	 */
    function insertStatus($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');
    	
        if(!$params->checkFields(array('status', 'status_name'))) {
        	$response->set('error', $this->state->lang->get('missingMandatoryFields'));
        	return false;
        }
    	if ($params->getField('due') != 'y') {
			$params->setField('due', 'n');    		
    	}
    	if ($params->getField('due_basetime') != 'c') {
			$params->setField('due_basetime', 'm');
    	}
    	$params->set('table', 'statuses');
    	return $this->callService('SqlTable', 'insert', $params);
    }
   
    
    /**
     * Update Status
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function updateStatus($params) {
    	$response =& $this->getByRef('response');
    	$db =& $this->state->getByRef('db');
    	
    	if(!$params->check(array('status'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}

    	if ($this->isDemoMode('Statuses', $params->get('status'))) {
    		$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));
    		return false;
    	}
    	 
    	if(!$params->checkFields(array('status_name'))) {
    		$response->set('error', $this->state->lang->get('missingMandatoryFields'));
        	return false;
        }
    	if (strlen($params->getField('due_basetime')) && $params->getField('due_basetime') != 'c') {
			$params->setField('due_basetime', 'm');
    	}
        
    	$params->set('table', 'statuses');
    	$params->set('where', "status = '".$db->escapeString($params->get('status')) . "'");
    	return $this->callService('SqlTable', 'update', $params);
    }

    
    function loadImages() {
    	$directory = dir(SERVER_PATH . '../client/lib/icons/crystalsvg/16/');
		$icons = array();
		while (false !== ($entry = $directory->read())) {
			if (preg_match('/.*?\.png$/', $entry)) {
				$icons[] = array($entry);
			}
		}
		return $icons;
    }
    
    /**
     *  getAvailableImages
     *
     *  @return object returns resultset
     */
    function getAvailableImages($params) {
        $response =& $this->getByRef('response');
        $db =& $this->state->getByRef('db');
        
        $rs = QUnit::newObj('QUnit_Rpc_ResultSet');
        $images = $this->loadImages();
        $rs->setRows($images);
        $md = QUnit::newObj('QUnit_Rpc_MetaData', 'images');
        $md->setColumnNames(array('image'));
        $md->setColumnTypes(array('string'));
        $response->setResultVar('md', $md);
        $response->setResultVar('rs', $rs);
        $response->setResultVar('count', count($images));
        return true;
    }
    
}
?>