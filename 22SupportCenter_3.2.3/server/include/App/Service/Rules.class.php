<?php
/**
 *   Handler class for Rules managements
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Rules extends QUnit_Rpc_Service {

	function initMethod($params) {
		$method = $params->get('method');

		switch($method) {
			case 'getRulesList':
			case 'deleteRule':
			case 'moveRuleUp':
			case 'moveRuleDown':
			case 'insertRule':
			case 'updateRule':
			case 'getRule':
				return $this->callService('Users', 'authenticateAdmin', $params);
				break;
			default:
				return false;
				break;
		}
	}

	function getRulesList($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');

		$params->set('columns', "*");
		$params->set('from', "parsing_rules");
		$where = "1";
		if($id = $params->get('rule_id')) {
			$where .= " and rule_id = '".$db->escapeString($id)."'";
		}

		$params->set('where', $where);
		$params->set('table', 'parsing_rules');
		$params->set('order', 'rule_order');
		return $this->callService('SqlTable', 'select', $params);
	}


	function getRule($params) {
		if(!$params->check(array('rule_id'))) {
			$response->set('error', $this->state->lang->get('noIdProvided'));
			return false;
		}
		return $this->getRulesListAllFields($params);
	}

	function getRulesListAllFields($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		$resultPatterns = null;
		$params->set('columns', "*");
		$params->set('from', "parsing_rules");
		$where = "1";
		if(strlen($id = $params->get('rule_id'))) {
			$where .= " and rule_id = '".$db->escapeString($id)."'";

			if ($this->getCustomFieldPatternsListAllFields($params)) {
				$resultPatterns = $response->get('result');
			} else {
				return false;
			}
		}

		$params->set('where', $where);
		$params->set('table', 'parsing_rules');
		$params->set('order', 'rule_order');
		if ($this->callService('SqlTable', 'select', $params)) {

			if(strlen($id = $params->get('rule_id'))) {
				$result = & $response->getByRef('result');
				$rows = $result['rs']->getRows($result['md']);
				$rs = $response->getResultVar('rs');
				$rs->rows[0][] = $resultPatterns;
				$md = $response->getResultVar('md');
				$md->addColumn('custom_fields', 'object');
				$response->setResultVar('rs', $rs);
				$response->setResultVar('md', $md);
			}
			return true;
		} else {
			return false;
		}
	}

	function getCustomFieldPatternsListAllFields($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');

		$params->set('columns', "cf.field_id, cf.field_title, cf.field_type, 
								cf.default_value, cf.options, cf.related_to, cf.order_value, cf.user_access, 
								prf.match_pattern, prf.target_type, prf.condition_type, prf.condition_value");
		$params->set('from', "custom_fields cf 
							LEFT JOIN parsing_rules_fields prf ON 
							(cf.field_id = prf.field_id and rule_id = '".$db->escapeString($params->get('rule_id'))."')");

		$params->set('where', '');
		$params->set('table', 'parsing_rules_fields');
		$params->set('order', 'order_value');
		return $this->callService('SqlTable', 'select', $params);
	}


	function moveRuleUp($params) {
		$response =& $this->getByRef('response');

		if(!$params->check(array('rule_id', 'rule_order'))) {
			$response->set('error', $this->state->lang->get('noIdProvided'));
			return false;
		}
		$db =& $this->state->getByRef('db');

		$rule_order = $params->get('rule_order');
		$rule_id = $params->get('rule_id');

		$sql = "UPDATE parsing_rules SET rule_order=rule_order+1 where rule_order=" . ($rule_order - 1);
		$sth = $db->execute($sql);
		$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
		if(!$this->_checkDbError($sth)) {
			$response->set('error', $this->state->lang->get('failedUpdateRuleOrder') . $rule_id);
			$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Rule');
			return false;
		}

		$params->set('table', 'parsing_rules');
		$params->setField('rule_order', $rule_order - 1);
		$params->set('where', "rule_id = '".$db->escapeString($rule_id)."'");
		return $this->callService('SqlTable', 'update', $params);
	}

	function moveRuleDown($params) {
		$response =& $this->getByRef('response');

		if(!$params->check(array('rule_id', 'rule_order'))) {
			$response->set('error', $this->state->lang->get('noIdProvided'));
			return false;
		}
		$db =& $this->state->getByRef('db');

		$rule_order = $params->get('rule_order');
		$rule_id = $params->get('rule_id');


		$sql = "UPDATE parsing_rules SET rule_order=rule_order-1 where rule_order=" . ($rule_order + 1);
		$sth = $db->execute($sql);
		$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
		if(!$this->_checkDbError($sth)) {
			$response->set('error', $this->state->lang->get('failedUpdateRuleOrder') . $rule_id);
			$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Rule');
			return false;
		}

		$params->set('table', 'parsing_rules');
		$params->setField('rule_order', $rule_order + 1);
		$params->set('where', "rule_id = '".$db->escapeString($rule_id)."'");
		return $this->callService('SqlTable', 'update', $params);
	}

	/**
	 * Delete Rule
	 *
	 * @param QUnit_Rpc_Params $params
	 */
	function deleteRule($params) {
		$response =& $this->getByRef('response');
		$db = $this->state->get('db');
   
		$ids = explode('|',$params->get('rule_id'));

		if ($this->isDemoMode('Rules', $ids)) {
			$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));
			return false;
		}
   
		$session = QUnit::newObj('QUnit_Session');
   
		$where_ids = '';
		foreach ($ids as $id) {
			if (strlen(trim($id))) {
				$where_ids .= (strlen($where_ids) ? ', ': '');
				$where_ids .= "'" . $db->escapeString(trim($id)) . "'";
			}
		}
   
		if(!$params->check(array('rule_id')) || !strlen($where_ids)) {
			$response->set('error', $this->state->lang->get('noIdProvided'));
			return false;
		}

		//update rule order vyssich pravidiel (-1)
		$rule_orders = array_reverse(explode('|', $params->get('rule_order')));
		foreach ($rule_orders as $rule_order) {
			if (strlen(trim($rule_order))) {
				$sql = "UPDATE parsing_rules SET rule_order=rule_order-1 where rule_order > " . $rule_order;
				$sth = $db->execute($sql);
				$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
				if(!$this->_checkDbError($sth)) {
					$response->set('error', $this->state->lang->get('failedUpdateRuleOrder') . $params->get('rule_id'));
					$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Rule');
					return false;
				}
			}
		}
   
		$this->callService('Rules', 'deleteCustomFieldsPatterns', $params);
   
		$params->set('table', 'parsing_rules');
		$params->set('where', "rule_id IN (" . $where_ids . ")");
		if ($ret = $this->callService('SqlTable', 'delete', $params)) {
			$this->state->log('info', 'Deleted rule ' . $params->get('name'), 'Rule');
		} else {
			$this->state->log('error', 'Failed to delete rule ' . $params->get('name'), 'Rule');
		}
		return $ret;
   
	}

	/**
	 * Insert new rule
	 *
	 * @param QUnit_Rpc_Params $params
	 * @return unknown
	 */
	function insertRule(&$params) {
		$response =& $this->getByRef('response');
		$db =& $this->state->getByRef('db');
   
		if(!$params->checkFields(array('name'))) {
			$response->set('error', $this->state->lang->get('ruleMandatoryFields'));
			return false;
		}
   
		if (!strlen($params->get('group_id'))) {
		    $this->set('group_id', 'null');
		}
				
		$params->setField('rule_id', 0);
		$params->setField('rule_order', $this->getLastRule());
   
		$params->set('table', 'parsing_rules');
		if ($ret = $this->callService('SqlTable', 'insert', $params)) {
		} else {
			$this->state->log('error', 'Failed to insert rule ' . $params->getField('name'), 'Rule');
		}
		$this->insertCustomFieldsPatterns($response->result, $params->get('custom_fields'));
		return $ret;
	}


	/**
	 * Update Rule
	 *
	 * @param QUnit_Rpc_Params $params
	 * @return unknown
	 */
	function updateRule($params) {
		$response =& $this->getByRef('response');
		$db =& $this->state->getByRef('db');
   
		if ($this->isDemoMode('Rules', $params->get('rule_id'))) {
			$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));
			return false;
		}

		if(!$params->check(array('rule_id'))) {
			$response->set('error', $this->state->lang->get('noIdProvided'));
			return false;
		}
		if(!$params->checkFields(array('name'))) {
			$response->set('error', $this->state->lang->get('ruleMandatoryFields'));
			return false;
		}
		
		if (!strlen($params->get('group_id'))) {
		    $this->set('group_id', 'null');
		}
		
		$this->callService('Rules', 'deleteCustomFieldsPatterns', $params);
		$this->insertCustomFieldsPatterns($params->get('rule_id'), $params->get('custom_fields'));
   
		$params->set('table', 'parsing_rules');
		$params->set('where', "rule_id = '".$db->escapeString($params->get('rule_id'))."'");
		return $this->callService('SqlTable', 'update', $params);
	}

	function getLastRule() {
		$db =& $this->state->getByRef('db');
		$sql = "SELECT max(rule_order) as max_order FROM parsing_rules";
		$sth = $db->execute($sql);
		$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
		if(!$this->_checkDbError($sth)) {
			$response->set('error',  $sth->get('errorMessage'));
			$this->state->log('error', $sth->get('errorMessage'), 'Rule');
			return false;
		}
		$rs = QUnit::newObj('QUnit_Rpc_ResultSet');
		$rs->setRows($sth->fetchAllRows());
		if (count($rs->rows) > 0) {
			return $rs->rows[0][0] + 1;
		} else {
			return 1;
		}
	}


	function deleteCustomFieldsPatterns($params) {
		$response =& $this->getByRef('response');
		$db = $this->state->get('db');
   
		$ids = explode('|',$params->get('rule_id'));
   
		$where_ids = '';
		foreach ($ids as $id) {
			if (strlen(trim($id))) {
				$where_ids .= (strlen($where_ids) ? ', ': '');
				$where_ids .= "'" . $db->escapeString(trim($id)) . "'";
			}
		}
   
		if(!$params->check(array('rule_id')) || !strlen($where_ids)) {
			$response->set('error', $this->state->lang->get('noIdProvided'));
			return false;
		}
   
		$params->set('table', 'parsing_rules_fields');
		$params->set('where', "rule_id IN (" . $where_ids . ")");
		return $this->callService('SqlTable', 'delete', $params);
	}

	function insertCustomFieldsPatterns($rule_id, $custom_fields) {
		if (!empty($custom_fields) && is_array($custom_fields) && !empty($rule_id)) {
			$sql = 'INSERT INTO parsing_rules_fields (field_id, rule_id, match_pattern, target_type, condition_type, condition_value) VALUES ';
			$sqlValues = '';
			foreach ($custom_fields as $customField) {
				$sqlValues .= (strlen($sqlValues) ? ',' : '') .
				"('" . $customField[2] . "','" .
				$rule_id . "', '" .
				$customField[0] . "', '" .
				$customField[1] . "', '" . $customField[3] . "', '" . $customField[4] . "')";
			}
			$sql .= $sqlValues;
			$db = $this->state->get('db');
			$sth = $db->execute($sql);
			$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
			if(!$this->_checkDbError($sth)) {
				return false;
			}
		}
		return true;
	}

}
?>