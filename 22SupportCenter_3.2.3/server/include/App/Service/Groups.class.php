<?php
/**
*   Handler class for Priorities management
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Groups extends QUnit_Rpc_Service {

    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
			case 'deleteGroup':
			case 'insertGroup':
			case 'updateGroup':
			case 'getGroup':
			case 'getCustomFieldValues':
			case 'getAllCustomFieldValues':
				return $this->callService('Users', 'authenticateAdmin', $params);
				break;
			case 'getGroupsList':
				return $this->state->config->get('showPriorityInSubmitForm') == 'y' || $this->callService('Users', 'authenticate', $params);
				break;
			default:
                return false;
                break;
        }
    }
    
    function getGroupsList($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $columns = "groupid, group_name";
        $from = "groups";
        $where = "1";
        if(strlen($params->get('groupid'))) {
        	$id = $params->get('groupid');
            $where .= " and groupid = ".$db->escapeString($id);
        }

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        if (!$params->get('order')) {
        	$params->set('order', 'group_name');
        }
        $params->set('table', 'groups');
        return $this->callService('SqlTable', 'select', $params);
    }
    
    function getGroup($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

    	if(!$params->check(array('groupid'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
        
        return $this->getGroupsList($params);
    }
    
    /**
     *  delete Group
     *
     *  @param string table
     *  @param string id id of task
     *  @return boolean
     */
    function deleteGroup($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');		
    	
    	$ids = explode('|',$params->get('groupid'));
    	
    	if ($this->isDemoMode('Groups', $ids)) {
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
    	 
    	if(!$params->check(array('groupid')) || !strlen($where_ids)) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
    	
    	//check if priority is not used by any ticket
    	$sql = "UPDATE users SET groupid=NULL WHERE groupid IN (" . $where_ids . ")";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(!$this->_checkDbError($sth)) {
    		$response->set('error', $sth->get('errorMessage'));
    		$this->state->log('error', $sth->get('errorMessage'), 'Group');
    		return false;
    	}
    	
    	$params->set('table', 'groups');
    	$params->set('where', "groupid IN (" . $where_ids . ")");
    	return $this->callService('SqlTable', 'delete', $params);
	}    
    	
	/**
	 * Insert group
	 *
	 * @param QUnit_Rpc_Params $params
	 */
    function insertGroup($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');
    	
        if(!$params->checkFields(array('group_name'))) {
        	$response->set('error', $this->state->lang->get('missingMandatoryFields'));
        	return false;
        }
        
        if (!$params->getField('groupid')) {
            $params->setField('groupid', 0);
        }
    	
    	$params->set('table', 'groups');
    	if ($ret = $this->callService('SqlTable', 'insert', $params)) {
    	    $this->updateCustomFields($params, $response->result);
    	}
    	return $ret;
    }
   
    
    /**
     * Update Group
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function updateGroup($params) {
    	$response =& $this->getByRef('response');
    	$db =& $this->state->getByRef('db');
    	
    	if(!$params->check(array('groupid'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}

    	if ($this->isDemoMode('Groups', $params->get('groupid'))) {
    		$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));
    		return false;
    	}
    	 
    	if(!$params->checkFields(array('group_name'))) {
    		$response->set('error', $this->state->lang->get('missingMandatoryFields'));
        	return false;
        }
    	
    	$params->set('table', 'groups');
    	$params->set('where', "groupid = ".$db->escapeString($params->get('groupid')*1));
    	if ($ret = $this->callService('SqlTable', 'update', $params)) {
    	    $this->updateCustomFields($params, $params->get('groupid'));
    	}
    	return $ret;
    }

    function updateCustomFields($params, $groupid) {
        $arrFields = $params->get('custom');
        //convert object to array
        if (is_object($arrFields)) {
            $arrFields = get_object_vars($arrFields);
        }

        if (is_array($arrFields)) {
            foreach ($arrFields as $field_id => $fld) {
                if (strlen($field_id)) {
                    $fldParams = $this->createParamsObject();
                    $fldParams->setField('field_id', $field_id);
                    $fldParams->setField('groupid', $groupid);
                    $fldParams->setField('field_value', $fld);
                    if (!$this->callService('Fields', 'insertFieldValue', $fldParams)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }
    
    function getAllCustomFieldValues($params) {
        $db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');

        $where = "related_to='g'";

        $params->set('columns', "*");
        $params->set('from', "custom_fields cf
                              LEFT JOIN custom_values cv ON (cf.field_id=cv.field_id)");
        $params->set('where', $where);
        $params->set('order', 'cf.order_value, cf.field_title');
        $params->set('table', 'custom_fields');
        return $this->callService('SqlTable', 'select', $params);
    }
    
    function getCustomFieldValues($params) {
        $db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');

        $where = "related_to='g'";

        $params->set('columns', "*");
        $params->set('from', "custom_fields cf
                              LEFT JOIN custom_values cv ON (cf.field_id=cv.field_id AND cv.groupid='" . $db->escapeString($params->get('groupid')) . "')");
        $params->set('where', $where);
        $params->set('order', 'cf.order_value, cf.field_title');
        $params->set('table', 'custom_fields');
        return $this->callService('SqlTable', 'select', $params);
    }
}
?>