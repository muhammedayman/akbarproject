<?php
/**
 *   Handler class for Escalation Rules
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_EscalationRules extends QUnit_Rpc_Service {

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
        $params->set('from', "escalation_rules");
        $where = "1";
        if($id = $params->get('rule_id')) {
            $where .= " and rule_id = '".$db->escapeString($id)."'";
        }

        if($id = $params->get('last_execution')) {
            $where .= " and (last_execution < '".$db->escapeString($id)."' OR last_execution is null)";
        }
         
         
        $params->set('where', $where);
        $params->set('table', 'escalation_rules');
        $params->set('order', 'rule_order, name');
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
        return $this->getRulesList($params);
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

        $sql = "UPDATE escalation_rules SET rule_order=rule_order+1 where rule_order=" . ($rule_order - 1);
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $this->state->lang->get('failedUpdateRuleOrder') . $rule_id);
            $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Rule');
            return false;
        }

        $params->set('table', 'escalation_rules');
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


        $sql = "UPDATE escalation_rules SET rule_order=rule_order-1 where rule_order=" . ($rule_order + 1);
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $this->state->lang->get('failedUpdateRuleOrder') . $rule_id);
            $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Rule');
            return false;
        }

        $params->set('table', 'escalation_rules');
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
                $sql = "UPDATE escalation_rules SET rule_order=rule_order-1 where rule_order > " . $rule_order;
                $sth = $db->execute($sql);
                $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
                if(!$this->_checkDbError($sth)) {
                    $response->set('error', $this->state->lang->get('failedUpdateRuleOrder') .
                    $params->get('rule_id'));
                    $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'),
'Rule');
                    return false;
                }
            }
        }
         
        $params->set('table', 'escalation_rules');
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
         
         
        $params->setField('rule_id', 0);
        $params->setField('rule_order', $this->getLastRule());
         
        if (strlen(ltrim(trim($params->getField('action_priority')), '0'))) {
            $params->setField('action_priority', $params->getField('action_priority') * 1);
        } else {
            $params->unsetField('action_priority');
        }
         
         
        if ($params->getField('action_delete_ticket') != 'y') {
            $params->setField('action_delete_ticket', 'n');
            $params->setField('action_delete_ticket_users', 'n');
        }
        if ($params->getField('action_delete_ticket_users') != 'y') {
            $params->setField('action_delete_ticket_users', 'n');
        }
         
        $params->set('table', 'escalation_rules');
        if ($ret = $this->callService('SqlTable', 'insert', $params)) {
        } else {
            $this->state->log('error', 'Failed to insert rule ' . $params->getField('name'), 'Rule');
        }
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
        if (!$params->get('last_execution')) {
            $params->setField('last_execution', 'null');
        }

        if($params->getField('action_priority') === ''){
            $params->setField('action_priority', 'null');
        } else if ($params->getField('action_priority')) {
            $params->setField('action_priority', $params->getField('action_priority') * 1);
        }
         

        $params->set('table', 'escalation_rules');
        $params->set('where', "rule_id = '".$db->escapeString($params->get('rule_id'))."'");
        return $this->callService('SqlTable', 'update', $params);
    }

    function getLastRule() {
        $db =& $this->state->getByRef('db');
        $sql = "SELECT max(rule_order) as max_order FROM escalation_rules";
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



    function runAllRules() {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $params = $this->createParamsObject();
        $time = time() - 120;
        $params->set('last_execution', $db->getDateString($time));
        if ($this->getRulesListAllFields($params)) {
            $rs = $response->getResultVar('rs');
            $rules = $rs->getRows($response->getResultVar('md'));

            foreach ($rules as $rule) {
                if (!$this->executeRule($rule)) {
                    return false;
                }
            }
        }
        return true;
    }

    function getEscalatedTicketsSearchSql($rule) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        //get due statuses
        $statusesObj = QUnit::newObj('App_Service_Statuses');
        $statusesObj->state = $this->state;
        $statusesObj->response = $response;
        $dueStatuses = $statusesObj->getDueStatusesArray();
        $dueStatuses_created = array();
        $dueStatuses_modified = array();
        foreach ($dueStatuses as $status => $due_basetime) {
            if ($due_basetime == 'c') {
                $dueStatuses_created[] = $status;
            } else {
                $dueStatuses_modified[] = $status;
            }
        }

        $sql = "	SELECT
						ticket_id, 
						CASE
							WHEN t.status IN ('" . implode("', '", $dueStatuses_modified) . "') 
THEN FROM_UNIXTIME((q.answer_time * 3600) + UNIX_TIMESTAMP(t.last_update))
							WHEN t.status IN ('" . implode("', '", $dueStatuses_created) . "') 
THEN FROM_UNIXTIME((q.answer_time * 3600) + UNIX_TIMESTAMP(t.created))
							ELSE 0
						END as due 
					FROM 
						tickets t 
						INNER JOIN queues q ON (t.queue_id = q.queue_id)
						INNER JOIN users u ON (t.customer_id = u.user_id)
					WHERE 1
				";
        if (strlen($rule['queue_cond'])) {
            $arrEntries = explode('|', $rule['queue_cond']);
            $inStr = '';
            foreach ($arrEntries as $queue_id) {
                if (strlen(trim($queue_id))) {
                    $inStr .= (strlen($inStr) ? ",'" : "'") . $queue_id . "'";
                }
            }

            if (strlen($inStr)) {
                $sql .= " AND t.queue_id IN ($inStr)";
            }
        }

         
        if (strlen($rule['owner_cond'])) {
            $arrEntries = explode('|', $rule['owner_cond']);
            $inStr = '';
            $noneWhere = '';
            foreach ($arrEntries as $id) {
                if (strlen(trim($id))) {
                    if (trim($id) != 'none') {
                        $inStr .= (strlen($inStr) ? ",'" : "'") . $id . "'";
                    } else {
                        $noneWhere = "(t.agent_owner_id='' OR t.agent_owner_id IS NULL)";
                    }
                }
            }

            if (strlen($inStr)) {
                if (strlen(trim($noneWhere))) {
                    $noneWhere = ' OR ' . $noneWhere;
                }
                $sql .= " AND (t.agent_owner_id IN ($inStr)$noneWhere)";
            } else {
                $sql .= ' AND ' . $noneWhere;
            }
        }
         
        if (strlen($rule['priority_cond'])) {
            $arrEntries = explode('|', $rule['priority_cond']);
            $inStr = '';
            foreach ($arrEntries as $id) {
                if (strlen(trim($id))) {
                    $id = $id *1;
                    $inStr .= (strlen($inStr) ? "," : "") . $id;
                }
            }

            if (strlen($inStr)) {
                $sql .= " AND t.priority IN ($inStr)";
            }
        }

        if (strlen($rule['status_cond'])) {
            $arrEntries = explode('|', $rule['status_cond']);
            $inStr = '';
            foreach ($arrEntries as $id) {
                if (strlen(trim($id))) {
                    $inStr .= (strlen($inStr) ? ",'" : "'") . $id . "'";
                }
            }

            if (strlen($inStr)) {
                $sql .= " AND t.status IN ($inStr)";
            }
        }

        if (strlen($rule['group_cond'])) {
            $arrEntries = explode('|', $rule['group_cond']);
            $inStr = '';
            foreach ($arrEntries as $id) {
                if (strlen(trim($id))) {
                    $inStr .= (strlen($inStr) ? ",'" : "'") . $db->escapeString($id) . "'";
                }
            }

            if (strlen($inStr)) {
                $sql .= " AND u.groupid IN ($inStr)";
            }
        }
        
        if (strlen($rule['customer_cond'])) {
            $inStr = $db->escapeString($rule['customer_cond']);
            $sql .= " AND u.email = '$inStr'";
        }
        
        
        if (strlen($rule['last_reply_cond'])) {
            $date = time() - $rule['last_reply_cond'] * 3600;
            $sql .= " AND t.last_update <= '" . $db->getDateString($date) . "'";
        }
         
        if (strlen($rule['ticket_age_cond'])) {
            $date = time() - $rule['ticket_age_cond'] * 3600;
            $sql .= " AND t.created <= '" . $db->getDateString($date) . "'";
        }
        if (strlen($rule['answer_time_cond'])) {
            $date = time() - $rule['answer_time_cond'] * 3600;
            $sql .= "HAVING due IS NOT NULL AND due <= '" . $db->getDateString($date) . "'";
        }
         
        $sql .= " LIMIT 0,500";
         
        return $sql;
    }

    function executeRuleAction($rule, $ticket_ids) {
        if (empty($ticket_ids)) return true;
         
        $arr_start = @gettimeofday();
         
         
        $response =& $this->getByRef('response');
         
        $params = $this->createParamsObject();
        $params->set('ticket_id', $ticket_ids);
        if ($rule['action_delete_ticket'] == 'y') {
            if ($rule['action_delete_ticket_users'] == 'y') {
                $params->set('delete_ticket_users', true);
            }
            if (!$this->callService('Tickets', 'deleteTicket', $params)) {
                return false;
            }
        } else {
            if (strlen($rule['action_queue'])) {
                $params->setField('queue_id', $rule['action_queue']);
            }
            if (strlen($rule['action_status'])) {
                $params->setField('status', $rule['action_status']);
            }
            if (strlen(ltrim(trim($rule['action_priority']), '0'))) {
                $params->setField('priority', $rule['action_priority'] * 1);
            }
            if (strlen($rule['action_owner'])) {
                $params->setField('agent_owner_id', $rule['action_owner']);
            }

            if (!$this->callService('Tickets', 'updateTicket', $params)) {
                $this->state->log('error', $response->get('error'), 'EscalationRules');
                return false;
            }
        }
        $arr_end = @gettimeofday();
        $delay_sec = $arr_end['sec'] - $arr_start['sec'];
        $delay_usec = $arr_end['usec'] - $arr_start['usec'];
        if ($delay_usec < 0) {
            $delay_sec--;
            $delay_usec = 1000000 + $delay_usec;
        }
        $delay_usec = str_repeat('0', 6 - strlen($delay_usec)) . $delay_usec;
        $execution_time = $delay_sec + $delay_usec/1000000;

        $this->state->log('info', "Executed rule: " . $rule['name'] .
        						" ($execution_time s) on " . count($ticket_ids) . 
        						" tickets", 'EscalationRules');

        return true;
    }

    function executeRule($rule) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $params = $this->createParamsObject();
        $params->set('rule_id', $rule['rule_id']);
        $params->setField('last_execution', $db->getDateString());
        $this->updateRule($params);
         
         
        $sql = $this->getEscalatedTicketsSearchSql($rule);
         
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'select');
        if(QUnit_Object::isError($sth)) {
            $this->state->log('error', $this->state->lang->get('dbError').$sth->get('errorMessage'), 'EscalationRules');
            $this->state->log('error', $sql, 'EscalationRules');
            return false;
        }

        $ticket_ids = array();
        while ($row = $sth->fetchArray()) {
            $ticket_ids[] = $row['ticket_id'];
        }
       	if (!$this->executeRuleAction($rule, $ticket_ids)) {
       	    $this->state->log('error', $response->get('error'), 'EscalationRules');
       	    return false;
       	}


       	return true;
    }

}
?>