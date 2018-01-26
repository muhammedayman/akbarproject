<?php
/**
*   Handler class for empty request
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Nop extends QUnit_Rpc_Service {

	function initMethod($params) {
		$method = $params->get('method');

		switch($method) {
			default:
				return true;
				break;
		}
	}
	
	
	/*
	 * do nothing
	 */
	function nop($params) {
		$response =& $this->getByRef('response');
		$response->set('result', true);
		return true;
	}
}
?>
