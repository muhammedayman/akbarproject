<?php
/**
 *   Represents Rule
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

define('TEXT_COND_EQUAL', '=');
define('TEXT_COND_NOTEQUAL', '!=');
define('TEXT_COND_CONTAINS', 'substr');
define('TEXT_COND_DOES_NOT_CONTAIN', '!substr');

define('TEXT_COND_EQUAL_INSENSITIVE', '=i');
define('TEXT_COND_NOTEQUAL_INSENSITIVE', '!=i');
define('TEXT_COND_CONTAINS_INSENSITIVE', 'substri');
define('TEXT_COND_DOES_NOT_CONTAIN_INSENSITIVE', '!substri');


define('TEXT_COND_REGEXP', 'regexp');

QUnit::includeClass("QUnit_Rpc_Service");
QUnit::includeClass("App_Service_Users");

class App_Rule_Rule extends QUnit_Rpc_Service {
    var $rule_id;
    var $name;
    var $rule_order;
    var $from_addr_cond;
    var $from_addr_value;
    var $subject_cond;
    var $subject_value;
    var $body_cond;
    var $body_value;
    var $account_cond;
    var $account_value;
    var $to_addr_cond;
    var $to_addr_value;
    var $cc_addr_cond;
    var $cc_addr_value;
    var $queue_cond;
    var $queue_id;
    var $group_cond;
    var $group_id;
    var $is_new_ticket_cond;
    var $is_registered_user_cond;
    var $move_to_queue_id;
    var $change_to_status_id;
    var $change_to_prio;
    var $assign_to_agent_id;
    var $delete_mail;
    var $stop_processing;


    function checkTextCondition($condition, $condition_value, $value) {
        //check text condition
        if ($condition) {
            switch ($condition) {
                case TEXT_COND_EQUAL:
                    if ($value != $condition_value) {
                        return false;
                    }
                    break;
                case TEXT_COND_NOTEQUAL:
                    if ($value == $condition_value) {
                        return false;
                    }
                    break;
                case TEXT_COND_CONTAINS:
                    if (strlen($condition_value) && false === strpos($value, $condition_value)) {
                        return false;
                    }
                    break;
                case TEXT_COND_DOES_NOT_CONTAIN:
                    if (false !== strpos($value, $condition_value)) {
                        return false;
                    }
                    break;
                     
                case TEXT_COND_EQUAL_INSENSITIVE:
                    if ($this->strtolower($value) != $this->strtolower($condition_value)) {
                        return false;
                    }
                    break;
                case TEXT_COND_NOTEQUAL_INSENSITIVE:
                    if ($this->strtolower($value) == $this->strtolower($condition_value)) {
                        return false;
                    }
                    break;
                case TEXT_COND_CONTAINS_INSENSITIVE:
                    if (strlen($this->strtolower($condition_value)) && false === strpos($this->strtolower($value), $this->strtolower($condition_value))) {
                        return false;
                    }
                    break;
                case TEXT_COND_DOES_NOT_CONTAIN_INSENSITIVE:
                    if (strlen($this->strtolower($condition_value)) && false !== strpos($this->strtolower($value), $this->strtolower($condition_value))) {
                        return false;
                    }
                    break;
                     
                case TEXT_COND_REGEXP:
                    if (!preg_match("/" . $condition_value . "/ims", $value)) {
                        return false;
                    }
                    break;
                default:

            }
        }
        return true;
    }

    function strtolower($inValue) {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($inValue);
        } else {
            return strtolower($inValue);
        }
    }

    /**
     * Check queue rule condition
     */
    function checkQueueCondition(&$params) {
        $response =& $this->getByRef('response');
        $mailHeaders = unserialize($params->get('headers'));
        //check queue condition
        if (strlen($this->queue_cond)) {

            $queue_id = '';

            //try if mail has already assigned ticket, if not, compare queue with default queue_id
            if (!strlen($params->get('ticket_id'))) {

                $paramsQueues = $this->createParamsObject();
                $paramsQueues->set('queue_email', QUnit_Net_Mail::getEmailAddress($mailHeaders['envelope-to:']));

                if (!$this->callService('Queues', 'getQueueByEmail', $paramsQueues)) {
                    $this->state->log('error', $this->state->lang->get('failedSelectQueue'),'MailParser');
                } else {
                    $result = & $response->getByRef('result');
                    if ($result['count'] > 0) {
                        $rows = $result['rs']->getRows($result['md']);
                        $queue_id = $rows[0]['queue_id'];
                    }
                }


                //if was not found other queue_id, set default queue_id
                if (!strlen($queue_id)) {
                    $paramsQueues = $this->createParamsObject();
                    if ($this->callService('Queues', 'getDefaultQueue', $paramsQueues)) {
                        $result = & $response->getByRef('result');
                        if ($result['count'] < 1) {
                            $this->state->log('error', $this->state->lang->get('missingDefaultQueue'), 'MailParser');
                        } elseif ($result['count'] > 1) {
                            $this->state->log('warning', $this->state->lang->get('existMoreDefaultQueues'), 'MailParser');
                            $rows = $result['rs']->getRows($result['md']);
                            $queue_id = $rows[0]['queue_id'];
                        } else {
                            $rows = $result['rs']->getRows($result['md']);
                            $queue_id = $rows[0]['queue_id'];
                        }
                    } else {
                        $this->state->log('error', $this->state->lang->get('failedSelectDefaultQueue'), 'MailParser');
                    }
                }
            } else {
                //load ticket
                $paramTicket = $this->createParamsObject();
                $paramTicket->set('ticket_id', $params->get('ticket_id'));
                if (!($ticket = $this->callService('Tickets', 'loadTicket', $paramTicket))) {
                    return false;
                }
                $queue_id = $ticket['queue_id'];
            }
             
            //check values with rule setting
            switch ($this->queue_cond) {
                case TEXT_COND_EQUAL:
                    if ($this->queue_id != $queue_id) {
                        return false;
                    }
                    break;
                case TEXT_COND_NOTEQUAL:
                    if ($this->queue_id == $queue_id) {
                        return false;
                    }
                    break;
                default:
            }
        }
        return true;
    }


    /**
     * Check mail account rule condition
     */
    function checkMailAccountCondition(&$params) {
        $response =& $this->getByRef('response');
        //check account condition
        if (strlen($this->account_cond)) {

            //check values with rule setting
            switch ($this->account_cond) {
                case TEXT_COND_EQUAL:
                    if ($this->account_value != $params->get('account_id')) {
                        return false;
                    }
                    break;
                case TEXT_COND_NOTEQUAL:
                    if ($this->account_value == $params->get('account_id')) {
                        return false;
                    }
                    break;
                default:
            }
        }
        return true;
    }


    /**
     * Check user group rule condition
     */
    function checkUserGroupCondition(&$params, $fromEmail) {
        $response =& $this->getByRef('response');
        //check account condition
        if (strlen($this->group_cond)) {

            //load user group
            $user = QUnit::newObj('App_Service_Users');
            $user->state = $this->state;
            $user->response = $response;
            $userGroup = $user->getUserGroup($fromEmail);

            //check values with rule setting
            switch ($this->group_cond) {
                case TEXT_COND_EQUAL:
                    if (($this->group_id+0) != $userGroup) {
                        return false;
                    }
                    break;
                case TEXT_COND_NOTEQUAL:
                    if (($this->group_id+0) == $userGroup) {
                        return false;
                    }
                    break;
                default:
            }
        }
        return true;
    }


    //check if rule is applicable on current email
    function isRuleApplicable(&$params) {
        //check if rule is applicable
        $mailHeaders = unserialize($params->get('headers'));


        //Check subject condition
        if (!$this->checkTextCondition($this->subject_cond, $this->subject_value, $params->get('subject'))) {
            return false;
        }

        //Check body condition
        if (!$this->checkTextCondition($this->body_cond, $this->body_value, $params->get('body'))) {
            return false;
        }

        //Check From condition
        if (!$this->checkTextCondition($this->from_addr_cond, $this->from_addr_value, QUnit_Net_Mail::getEmailAddress($mailHeaders['from:']))) {
            return false;
        }

        //Check Cc condition
        if (strlen($this->cc_addr_cond)) {
            $result = false;
            if (isset($mailHeaders['cc:']) && is_array($mailHeaders['cc:'])) {
                foreach ($mailHeaders['cc:'] as $idx => $to) {
                    if ($this->checkTextCondition(
                    $this->cc_addr_cond,
                    $this->cc_addr_value,
                    QUnit_Net_Mail::getEmailAddress($mailHeaders['cc:'], $idx))) {
                        $result = true;
                        break;
                    }
                }
            }
            if (!$result) return $result;
        }

        if (strlen($this->to_addr_cond)) {
            $result = false;
            //Check To condition
            if (isset($mailHeaders['to:']) && is_array($mailHeaders['to:'])) {
                foreach ($mailHeaders['to:'] as $idx => $to) {
                    if ($this->checkTextCondition(
                    $this->to_addr_cond,
                    $this->to_addr_value,
                    QUnit_Net_Mail::getEmailAddress($mailHeaders['to:'], $idx))) {
                        $result = true;
                        break;
                    }
                }
            }
            if (!$result && isset($mailHeaders['envelope-to:']) && is_array($mailHeaders['envelope-to:'])) {
                foreach ($mailHeaders['envelope-to:'] as $idx => $to) {
                    if ($this->checkTextCondition(
                    $this->to_addr_cond,
                    $this->to_addr_value,
                    QUnit_Net_Mail::getEmailAddress($mailHeaders['envelope-to:'], $idx))) {
                        $result = true;
                        break;
                    }
                }
            }
            if (!$result) return $result;
        }

        //check queue condition
        if (!$this->checkQueueCondition($params)) {
            return false;
        }

        //check queue condition
        if (!$this->checkMailAccountCondition($params)) {
            return false;
        }

        //check user group condition
        if (!$this->checkUserGroupCondition($params, QUnit_Net_Mail::getEmailAddress($mailHeaders['from:']))) {
            return false;
        }

        //is new ticket condition and is not new ticket condition
        if ($this->is_new_ticket_cond == 'y' && strlen($params->get('ticket_id')) || $this->is_new_ticket_cond == 'n' && !strlen($params->get('ticket_id'))) {
            return false;
        }

        //is registered user condition
        if ($this->is_registered_user_cond == 'y' || $this->is_registered_user_cond == 'n') {
            $is_registered = $this->isUserRegistered(QUnit_Net_Mail::getEmailAddress($mailHeaders['from:']));
            if ($this->is_registered_user_cond == 'y' && !$is_registered || $this->is_registered_user_cond == 'n' && $is_registered) {
                return false;
            }
        }

        return $this->matchCustomFields($params);
    }

    function isUserRegistered($email) {
        $response =& $this->getByRef('response');
        $params = $this->createParamsObject();
        $params->set('email', $email);
        if (!$this->callService('Users', 'getUserByEmail', $params)) {
            $this->state->log('error', 'Failed request if user ' . $email . ' exist with error: ' . $response->error,'MailParser');
            return false;
        }
        if($response->getResultVar('count') == 0) {
            return false;
        }
        return true;
    }

    function cleanupRuleVariables(&$params) {
        $params->unsetParam('rule_delete_mail');
        $params->unsetParam('rule_assign_ticket_to_agent');
        $params->unsetParam('rule_change_priority');
        $params->unsetParam('change_to_status_id');
        $params->unsetParam('rule_change_queue');
    }


    /**
     * execute rule
     * if execution should continue, return true
     * if execution of next rule should be stopped, return false
     */
    function setupRule(&$params) {
        //move ticket to new queue_id
        if (strlen($this->move_to_queue_id)) {
            //zisti, ci existuje takato queue
            $paramsQueue = $this->createParamsObject();
            $paramsQueue->set('queue_id', $this->move_to_queue_id);
            if ($this->callService('Queues', 'existQueue', $paramsQueue)) {
                $params->set('rule_change_queue', $this->move_to_queue_id);
            } else {
                $this->state->log('error', $this->state->lang->get('ruleWrongQueueSetup', $this->name), 'MailParser');
            }
        }
         
        //change status to new status
        if (strlen($this->change_to_status_id)) {
            $params->set('rule_change_status', $this->change_to_status_id);
        }
         
        //change priority of ticket
        if (strlen($this->change_to_prio) && $this->change_to_prio != 0) {
            $params->set('rule_change_priority', $this->change_to_prio);
        }
         
        //assign ticket to agent or admin user
        if (strlen($this->assign_to_agent_id)) {
            $paramsUser = $this->createParamsObject();
            $paramsUser->set('user_id', $this->assign_to_agent_id);
            //check if it is agent or admin, normal user can't receive ticket
            if (($paramsUser->set('user_type', USERTYPE_AGENT) && $this->callService('Users', 'existUser', $paramsUser)) ||
            ($paramsUser->set('user_type', USERTYPE_ADMIN) && $this->callService('users', 'existUser', $paramsUser))) {
                $params->set('rule_assign_ticket_to_agent', $this->assign_to_agent_id);
            } else {
                $this->state->log('error', $this->state->lang->get('ruleWrongUserSetup', $this->name), 'MailParser');
            }
        }
         
        //delete mail
        if ($this->delete_mail == 'y') {
            $params->set('rule_delete_mail', $this->name);
        }
        $this->state->log('info', $this->state->lang->get('appliedRuleOnTicket', $this->name, $params->get('subject')), 'Rule');
        //if stop_processing='y', return false
        return $this->stop_processing != 'y';
    }

    function matchCustomFields(&$params) {
        $response =& $this->getByRef('response');
        $custParams = $this->createParamsObject();
        $custParams->set('rule_id', $this->rule_id);
        $this->callService('Rules', 'getCustomFieldPatternsListAllFields', $custParams);
        $result = $response->result;
        $rows = $result['rs']->getRows($result['md']);
        $arrCustom = array();
        foreach ($rows as $row) {
            switch ($row['target_type']) {
                case 'h':
                    break;
                case 'b':
                    if (preg_match('/' . $row['match_pattern'] . '/ms', $params->get('body'), $match)) {
                        $arrCustom[$row['field_id']] = $match[1];
                    }
                    break;
                default:
            }
             
            if (!$this->checkTextCondition($row['condition_type'], $row['condition_value'], $arrCustom[$row['field_id']])) {
                return false;
            }
        }
        $params->set('custom', $arrCustom);

        return true;
    }

    /**
     * Execute final setup from rules
     *
     * @param QUnit_Rpc_Params $paramsMail
     */
    function executeRuleSetup(&$paramsTicket, &$paramsMail, &$parser) {
        //execute rule setup

        $call_update = false;

        //move to queue
        if (strlen($paramsMail->get('rule_change_queue'))) {
            $paramsTicket->setField('queue_id', $paramsMail->get('rule_change_queue'));
            $call_update = true;
        }
         
        //change status to
        if (strlen($paramsMail->get('rule_change_status'))) {
            $paramsTicket->setField('status', $paramsMail->get('rule_change_status'));
            $call_update = true;
        }
         
        //change priority to
        if (strlen($paramsMail->get('rule_change_priority'))) {
            $paramsTicket->setField('priority', $paramsMail->get('rule_change_priority'));
            $call_update = true;
        }
         
        //assign to agent
        if (strlen($paramsMail->get('rule_assign_ticket_to_agent'))) {
            $paramsTicket->setField('agent_owner_id', $paramsMail->get('rule_assign_ticket_to_agent'));
            $call_update = true;
        }
         
        $paramsTicket->set('custom', $paramsMail->get('custom'));
        QUnit::includeClass('App_Rule_CustomRule');
        $call_update == App_Rule_CustomRule::execute($paramsTicket, $paramsMail, $parser) || $call_update;

        return $call_update;
    }
}
?>
