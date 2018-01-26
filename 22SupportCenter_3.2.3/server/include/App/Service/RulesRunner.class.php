<?php
/**
*   Execute all active rules on email
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_RulesRunner extends QUnit_Rpc_Service {
	
	/**
	 * Returns array of all rules
	 *
	 * @param QUnit_Rpc_Params $params
	 * @return unknown
	 */
	function run(&$params) {
		$response =& $this->getByRef('response'); 
		$rules = QUnit::newObj('App_Service_Rules');
        $rules->setByRef('state', $this->state);
        $rules->setByRef('response', $response);
		
		if ($arrMailAccounts = $rules->getRulesListAllFields($params)) {
			$rs = $response->getResultVar('rs'); 
			foreach ($rs->getRows($response->getResultVar('md')) as $rowid => $row) {
				
				$objRule = QUnit::newObj('App_Rule_Rule');
				$objRule->loadObjectAttributes($row);
				$objRule->setByRef('state', $this->state);
	            $objRule->setByRef('response', $this->response);
				
				if ($objRule->isRuleApplicable($params)) {
					if (!$objRule->setupRule($params)) {
						//stop execution of next rule
						return true;
					}
				}
			}
		}
		return true;
	}
}
?>