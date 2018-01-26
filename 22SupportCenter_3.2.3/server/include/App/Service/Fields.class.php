<?php
/**
*   Handler class for Custom Fields
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Fields extends QUnit_Rpc_Service {

    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
			case 'deleteField':
			case 'insertField':
			case 'updateField':
			case 'getField':
			case 'getFieldsList':
				return $this->callService('Users', 'authenticateAdmin', $params);
				break;
			case 'updateCustomFields':
			case 'getFieldsListAllColumns':
				return $this->callService('Users', 'authenticate', $params);
				break;
			case 'getTicketCustomFieldsList':
				return true;
				break;
			default:
                return false;
                break;
        }
    }
    
    function getFieldsList($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $columns = "field_id, field_title, field_type, related_to, order_value, user_access";
        $from = "custom_fields";
        $where = '';
        
        if(strlen($id = $params->get('field_id'))) {
            $where .= (strlen($where) ? " AND " : '') . "field_id = '".$db->escapeString($id) . "'";
        }

        if(strlen($id = $params->get('user_access'))) {
            $where .= (strlen($where) ? " AND " : '') . "user_access = '".$db->escapeString($id) . "'";
        }
        
        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        if (!$params->get('order')) {
        	$params->set('order', 'related_to');
        }
        $params->set('table', 'custom_fields');
        return $this->callService('SqlTable', 'select', $params);
    }

    function getCustomFieldsArray($user_access = 'u') {
        $response =& $this->getByRef('response');
        $params = $this->createParamsObject();
        $params->set('user_access', $user_access);
    	if ($this->getFieldsList($params)) {
   			$res = & $response->getByRef('result');
			$flds = $res['rs']->getRows($res['md']);
			$arrRet = array();
			foreach ($flds as $arrRow) {
				$arrRet[$arrRow['field_id']] = $arrRow['field_title'];
			}
			return $arrRet;
    	}
    	return array();
    }
    
    function getCustomFieldsRowsArray($user_access = 'u') {
        $response =& $this->getByRef('response');
        $params = $this->createParamsObject();
        if ($user_access != null) {
            $params->set('user_access', $user_access);
        }
    	if ($this->getFieldsList($params)) {
   			$res = & $response->getByRef('result');
			$flds = $res['rs']->getRows($res['md']);
			$arrRet = array();
			foreach ($flds as $arrRow) {
				$arrRet[$arrRow['field_id']] = $arrRow;
			}
			return $arrRet;
    	}
    	return array();
    }
    
    function getFieldsListAllColumns($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $columns = "*";
        $from = "custom_fields";
        $where = '';
        if(strlen($id = $params->get('field_id'))) {
            $where .= (strlen($where) ? " AND " : '') . "field_id = '".$db->escapeString($id) . "'";
        }

        if(strlen($id = $params->get('user_access'))) {
            $where .= (strlen($where) ? " AND " : '') . "user_access = '".$db->escapeString($id) . "'";
        }
        
        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        if (!$params->get('order')) {
        	$params->set('order', 'order_value, related_to, field_title');
        }
        $params->set('table', 'custom_fields');
        return $this->callService('SqlTable', 'select', $params);
    }
    
    function getField($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

    	if(!$params->check(array('field_id'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
        
        return $this->getFieldsListAllColumns($params);
    }
    
    /**
     *  delete metadata Field
     *
     *  @param string table
     *  @param string id id of task
     *  @return boolean
     */
    function deleteField($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');		
    	
    	$ids = explode('|',$params->get('field_id'));
    	
    	$where_ids = '';
    	foreach ($ids as $id) {
    		if (strlen(trim($id))) {
    			$where_ids .= (strlen($where_ids) ? ', ': '');
    			$where_ids .= "'" . $db->escapeString(trim($id)) . "'";
    		}
    	}
    	 
    	if(!$params->check(array('field_id')) || !strlen($where_ids)) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
    	
    	//check if field is not used
    	$sql = "DELETE FROM custom_values WHERE field_id IN (" . $where_ids . ")";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(!$this->_checkDbError($sth)) {
    		$response->set('error', $sth->get('errorMessage'));
    		$this->state->log('error', $sth->get('errorMessage'), 'Fields');
    		return false;
    	}
    	
    	$params->set('table', 'custom_fields');
    	$params->set('where', "field_id IN (" . $where_ids . ")");
    	return $this->callService('SqlTable', 'delete', $params);
	}    
    	
	/**
	 * Insert mail priority
	 *
	 * @param QUnit_Rpc_Params $params
	 */
    function insertField($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');
    	
        if(!$params->checkFields(array('field_id', 'field_title'))) {
        	$response->set('error', $this->state->lang->get('missingMandatoryFields'));
        	return false;
        }
    	
    	$params->set('table', 'custom_fields');
    	return $this->callService('SqlTable', 'insert', $params);
    }
   
    
    /**
     * Update Priority
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function updateField($params) {
    	$response =& $this->getByRef('response');
    	$db =& $this->state->getByRef('db');
    	
    	if(!$params->check(array('field_id'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}

    	if(!$params->checkFields(array('field_title'))) {
    		$response->set('error', $this->state->lang->get('missingMandatoryFields'));
        	return false;
        }
    	
    	$params->set('table', 'custom_fields');
    	$params->set('where', "field_id = '".$db->escapeString($params->get('field_id')) . "'");
    	return $this->callService('SqlTable', 'update', $params);
    }
    
    
    function getTicketCustomFieldsList($params) {
		$session = QUnit::newObj('QUnit_Session');
    	if ($session->getVar('userType') != 'a' && $session->getVar('userType') != 'g') { 
    		$params->set('user_access', 'u');
    	}
    	return $this->getFieldsListAllColumns($params);
    }
    
    function deleteFieldValue($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');		
    	
    	$where = "";
    	
    	if (strlen($id = $params->get('value_id'))) {
    		$where .= (strlen($where) ? " AND " : '') . "value_id = '" . $db->escapeString(trim($id)) . "'";
    	} else {
    	
	    	if (strlen($field_id = $params->get('field_id'))) {
	    		$where .= (strlen($where) ? " AND " : '') . "field_id = '" . $db->escapeString(trim($field_id)) . "'";
				
	    		$where2 = '';
	    		if (strlen($id = $params->get('user_id'))) {
		    		$where2 .= "(user_id = '" . $db->escapeString(trim($id)) . "' AND 0 < (SELECT count(*) FROM custom_fields WHERE field_id='" . $db->escapeString(trim($field_id)) . "' AND related_to='u'))";
		    	}

                if (strlen($id = $params->get('groupid'))) {
                    $where2 .= (strlen($where2) ? ' OR ' : '') . "(groupid = '" . $db->escapeString(trim($id)) . "' AND 0 < (SELECT count(*) FROM custom_fields WHERE field_id='" . $db->escapeString(trim($field_id)) . "' AND related_to='g'))";
                }
		    	
		    	if (strlen($id = $params->get('ticket_id'))) {
		    		$where2 .= (strlen($where2) ? ' OR ' : '') . "(ticket_id = '" . $db->escapeString(trim($id)) . "' AND 0 < (SELECT count(*) FROM custom_fields WHERE field_id='" . $db->escapeString(trim($field_id)) . "' AND related_to='t'))";
		    	}
		    	
		    	if (strlen($where2)) {
		    		$where .= (strlen($where) ? " AND " : '') . '(' . $where2 . ')';
		    	} else {
		    		$where .= (strlen($where) ? " AND " : '') . "2=1";
		    	}
	    	} else {
	    		$where .= (strlen($where) ? " AND " : '') . "2=1";
	    	}
    	}
    	    	
    	$params->set('table', 'custom_values');
    	$params->set('where', $where);
    	return $this->callService('SqlTable', 'delete', $params);
    }
    
    function insertFieldValue($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');
    	
        if(!$params->checkFields(array('field_id'))) {
        	$response->set('error', $this->state->lang->get('missingMandatoryFields'));
        	return false;
        }
    	
        if (!$this->deleteFieldValue($params)) {
        	return false;
        }
        
        //if there is no value, don't insert new line
        if (!strlen(trim($params->get('field_value')))) {
        	return true;
        }
        
        $params->setField('value_id', 0);
        
    	$params->set('table', 'custom_values');
    	return $this->callService('SqlTable', 'insert', $params);
    }
    
    function updateCustomFields($params) {
    	$response =& $this->getByRef('response');
    	$arrFields = $params->get('custom');
		//convert object to array
		if (is_object($arrFields)) {
			$arrFields = get_object_vars($arrFields);
		}
		
		$objTickets = QUnit::newObj('App_Service_Tickets');
		$objTickets->state = $this->state;
		$objTickets->response = $response;
		if ($params->get('ticket_id') && $ticket = $objTickets->loadTicket($params)) {
			$user_id = $ticket['customer_id'];
			$params->set('user_id', $user_id);
		} else {
			$user_id = $params->get('user_id');
		}

        $objUsers = QUnit::newObj('App_Service_Users');
        $objUsers->state = $this->state;
        $objUsers->response = $response;
        $group_id = $objUsers->getUserGroupByUserId($user_id);

        if (is_array($arrFields)) {
	    	foreach ($arrFields as $field_id => $fld) {
	    		if (strlen($field_id)) {
	    			$fldParams = $this->createParamsObject();
	    			$fldParams->setField('field_id', $field_id);
	    			$fldParams->setField('user_id', $user_id);
	    			$fldParams->setField('ticket_id', $params->get('ticket_id'));
                    $fldParams->setField('groupid', $group_id);
	    			$fldParams->setField('field_value', $fld);
	    			if (!$this->callService('Fields', 'insertFieldValue', $fldParams)) {
	    				return false;
	    			}
	    		}
	    	}
	    	}
    	return true;
    }
    
    
}
?>