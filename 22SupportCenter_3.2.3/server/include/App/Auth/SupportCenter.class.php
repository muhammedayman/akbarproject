<?php
/**
*   SupportCenter authentification method
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");

class App_Auth_SupportCenter extends QUnit_Rpc_Service {
	
	function auth($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
    	$session = QUnit::newObj('QUnit_Session');

    	if ($params->get('password_md5')) {
    		$password = $params->get('password_md5');
    	} else {
	    	$password = $params->get('password');
	    	switch ($this->state->config->get('passwordEncoding')) {
	    		case 'plain':
	    			 $password = md5($password);
	    			break;
	    		case 'md5':
	    		default:
	    			break;
	    	}
    	}
    	
		$params->set('columns', "*");
		$params->set('from', "users");
		$params->set('where', "email='".$db->escapeString($params->get('email')).
					"' AND password='".$db->escapeString($password)."'");
		$params->set('table', 'users');
		return $this->callService('SqlTable', 'select', $params);
	}
}
?>