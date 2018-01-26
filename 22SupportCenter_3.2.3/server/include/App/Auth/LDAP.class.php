<?php
/**
 *   SupportCenter authentification method
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("QUnit_Rpc_Service");

class App_Auth_LDAP extends QUnit_Rpc_Service {

	function ldap_auth($params) {
		if (!function_exists('ldap_connect')) {
			$this->state->log('error', "LDAP extension not available.", 'LDAP');
			return false;
		}

		$ds=@ldap_connect($this->state->config->get('ldapServer'));

		if ($ds) {
			if (!@ldap_bind($ds, $params->get('email'), $params->get('password'))) {
				$this->state->log('error', "Failed to bind to LDAP server " . $this->state->config->get('ldapServer') . " with user " . $params->get('email') . ' and pw ' . $params->get('password'), 'LDAP');
				ldap_close($ds);
				return false;
			}
				
			ldap_close($ds);
			return true;
		} else {
			$this->state->log('error', "Unable to connect to LDAP server: " . $this->state->config->get('ldapServer'), 'LDAP');
			return false;
		}
	}

	function auth($params) {
		$response =& $this->getByRef('response');

		if ($ldap_res = $this->ldap_auth($params)) {
			if ($res = $this->loadSCUser($params)) {
				//check if user already exist in SC
				if ($response->getResultVar('count') == 1) {
					return true;
				} else {
					//insert new user
			    	$paramsUser = $this->createParamsObject();
			    	$paramsUser->setField('email', $params->get('email'));
			    	$paramsUser->setField('name', '');
			    	$paramsUser->set('plain_password', $params->get('password'));
					if (!$this->callService('Users', 'insertUser', $paramsUser)) {
				        $response->set('result', null);
        				$response->set('error', $this->state->lang->get('LDAPAuthenticationFailed'));
						return false;
					} else {
						return $this->loadSCUser($params);
					}
				}
			}
		}
        $response->set('result', null);
        $response->set('error', $this->state->lang->get('LDAPAuthenticationFailed'));
        return false;
	}

	//******************** User manipulation *********************************

	function loadSCUser($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		$session = QUnit::newObj('QUnit_Session');

		$params->set('columns', "*");
		$params->set('from', "users");
		$params->set('where', "email='".$db->escapeString(strtolower($params->get('email')))."'");
		$params->set('table', 'users');
		return $this->callService('SqlTable', 'select', $params);
	}
}
?>