<?php
/**
*   Base handler class
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SeHistory
*/

QUnit::includeClass('QUnit_Object');
class QUnit_Rpc_Service extends QUnit_Object {

    function _init() {
        $this->attrAccessor('state');
        $this->attrAccessor('response');
    }

    function &createServiceObject($service) {
        $className = 'App_Service_'.$service;
        if(!QUnit::existsClass($className)) {
            $this->response->set('error', 'No such service: '.$service);
            return false;
        }
        $obj = QUnit::newObj($className);
        return $obj;
    }

    function callService($service, $method, &$params) {
        if(!strlen($service) || !strlen($method) || !is_object($params)) {
            $this->response->set('error', 'Service, method or params not provided ' . "($service:$method)");
            return false;
        }

        if($obj =& $this->createServiceObject($service)) {
        	global $state;
        	if (!isset($this->state) && is_object($state)) {
        		$this->state = $state;
        	}
        	
        	if (!isset($this->response)) {
        		$this->response = QUnit::newObj('QUnit_Rpc_Response');
        		$this->response->set('id', '2');
        	}
        	
            $obj->setByRef('state', $this->state);
            $obj->setByRef('response', $this->response);
            return call_user_func_array(array(&$obj, $method), array(&$params));
        }
        return false;
    }

    function createParamsObject() {
        return QUnit::newObj('QUnit_Rpc_Params');
    }

    function _checkDbError($sth) {
        $response =& $this->getByRef('response');

        if(QUnit_Object::isError($sth)) {
            $response->set('error', $this->state->lang->get('dbError').$sth->get('errorMessage'));
            return false;
        }
        return true;
    }
}
?>