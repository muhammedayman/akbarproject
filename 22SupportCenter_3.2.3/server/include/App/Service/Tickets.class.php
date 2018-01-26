<?php
/**
 *   Handler class for Tickets
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

define('TICKET_STATUS_NEW', 'n');
define('TICKET_STATUS_RESOLVED', 'r');
define('TICKET_STATUS_SPAM', 's');
define('TICKET_STATUS_DEAD', 'd');
define('TICKET_STATUS_BOUNCED', 'b');
define('TICKET_STATUS_AWAITING_REPLY', 'a');
define('TICKET_STATUS_CUSTOMER_REPLY', 'c');
define('TICKET_STATUS_WORK_IN_PROGRESS', 'w');

QUnit::includeClass("QUnit_Rpc_Service");
QUnit::includeClass('QUnit_Net_Mail');
class App_Service_Tickets extends QUnit_Rpc_Service {

    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
            case 'newAnonymTicket':
                return true;

            case 'getTicketsList':
            case 'getMailsData':
            case 'replyMail':
            case 'updateTicket':
            case 'getCustomFieldValues':
                return $this->callService('Users', 'authenticate', $params);
                break;

            case 'deleteTicket':
            case 'moveMailToNewTicket':
                return $this->callService('Users', 'authenticateAgent', $params);
                break;

            case 'nieco pre admina':
                return $this->callService('Users', 'authenticateAdmin', $params);
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * returns list of tickets together with real values of joined tables
     */
    function getTicketHistory($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        if(!$params->check(array('ticket_id'))) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }
        $columns = "tc.log_id, tc.ticket_id, tc.created, tc.created_by_user_id,
					tc.new_status, tc.new_queue_id, tc.new_agent_owner_id, tc.new_priority,
					uc.name as created_name, uc.email as created_email, uc.user_type as created_user_type, uc.email_quality as created_email_quality,
					ua.name as agent_name, ua.email as agent_email, ua.user_type as agent_user_type, ua.email_quality as agent_email_quality,
					q.name as queue_name";
        $from = "ticket_changes tc
				LEFT JOIN users uc ON (tc.created_by_user_id = uc.user_id)
				LEFT JOIN users ua ON (tc.new_agent_owner_id = ua.user_id)
				LEFT JOIN queues q ON (tc.new_queue_id = q.queue_id)";
        $where = "ticket_id ='" . $db->escapeString($params->get('ticket_id')) . "'";

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'ticket_changes');
        $params->set('order', 'tc.created');
        $params->set('orderDirection', 'DESC');
        return $this->callService('SqlTable', 'select', $params);
         
    }

    /**
     * returns list of tickets together with real values of joined tables
     */
    function getTicketsList($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');


        if ($params->get('filter_id')) {
            //update info which filter was used as last
            $parmsFilter = $this->createParamsObject();
            $parmsFilter->set('filter_id', $params->get('filter_id'));
            $parmsFilter->setField('last_used', $db->getDateString());
            $this->callService('Filters', 'updateFilter', $parmsFilter);
        }

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


        $columns = "t.ticket_id as t_ticket_id,
				t.queue_id as t_queue_id,
			    t.subject_ticket_id as t_subject_ticket_id,
			    t.thread_id as t_thread_id,
			    t.status as t_status,
			    t.priority as t_priority,
			    t.customer_id as t_customer_id,
			    t.agent_owner_id as t_agent_owner_id,
			    t.created as t_created,
			    t.last_update as t_last_update,
			    t.first_subject as t_first_subject, 
				cust.name as customer_name, cust.email as customer_email, cust.email_quality as customer_email_quality,
				cust.groupid as groupid,
				g.group_name as group_name,
				ag.name as agent_name, ag.email as agent_email, q.name as queue_name,
				CASE
					WHEN t.status IN ('" . implode("', '", $dueStatuses_created) . "') THEN ((q.answer_time * 3600) - (UNIX_TIMESTAMP('" . $db->getDateString() . "') - UNIX_TIMESTAMP(t.created)))
					WHEN t.status IN ('" . implode("', '", $dueStatuses_modified) . "') THEN ((q.answer_time * 3600) - (UNIX_TIMESTAMP('" . $db->getDateString() . "') - UNIX_TIMESTAMP(t.last_update)))
					ELSE 0
				END as due,
				mails_count, dt.created as last_read";
        $from = " 	tickets t
					LEFT JOIN users cust ON (cust.user_id = t.customer_id)
					LEFT JOIN users ag ON (ag.user_id = t.agent_owner_id)
					LEFT JOIN queues q ON (q.queue_id = t.queue_id)
					LEFT JOIN groups g ON (g.groupid = cust.groupid)
					LEFT JOIN displayed_tickets dt ON (t.ticket_id = dt.ticket_id AND dt.user_id = '" . $session->getVar('userId') . "') ";
        $where = "";

        $from_mails = false;

        if($id = $params->get('ticket_id')) {
            $where .= (strlen($where) ? " AND " : '') . "t.ticket_id = '".$db->escapeString($id)."'";
        }

        if($id = $params->get('subject_ticket_id')) {
            $where .= (strlen($where) ? " AND " : '') . "t.subject_ticket_id = BINARY '".$db->escapeString($id)."'";
        }
        if($id = $params->get('email')) {
            $where .= (strlen($where) ? " AND " : '') . "cust.email LIKE '%".$db->escapeString($id)."%'";
        }
        if($id = $params->get('subject')) {
            $where .= (strlen($where) ? " AND " : '') . "t.first_subject LIKE '%".$db->escapeString($id)."%'";
        }


        if($id = $params->get('mail_account')) {
            if (!$from_mails) {
                $from .= " INNER JOIN mails m ON (m.ticket_id = t.ticket_id) ";
                $from_mails = true;
            }
            if (is_array($id)) {
                $ids = '';
                foreach ($id as $filter_value) {
                    if (strlen($filter_value)) {
                        $ids .= (strlen($ids) ? ',' : '') . "'" . $db->escapeString($filter_value) . "'";
                    }
                }
                if (strlen($ids)) {
                    $where .= (strlen($where) ? " AND " : '') . "m.account_id IN (".$ids.")";
                }
            } else {
                $where .= (strlen($where) ? " AND " : '') . "m.account_id = '".$db->escapeString($id)."'";
            }
            $params->set('count_group_by', 'no');
        }


        if($id = $params->get('queue_id')) {
            if (is_array($id)) {
                $ids = '';
                foreach ($id as $filter_value) {
                    if (strlen($filter_value)) {
                        $ids .= (strlen($ids) ? ',' : '') . "'" . $db->escapeString($filter_value) . "'";
                    }
                }
                if (strlen($ids)) {
                    $where .= (strlen($where) ? " AND " : '') . "t.queue_id IN (".$ids.")";
                }
            } else {
                $where .= (strlen($where) ? " AND " : '') . "t.queue_id = '".$db->escapeString($id)."'";
            }
        }


        if($id = $params->get('groupid')) {
            if (is_array($id)) {
                $ids = '';
                foreach ($id as $filter_value) {
                    if (strlen($filter_value)) {
                        $ids .= (strlen($ids) ? ',' : '') . "'" . $db->escapeString($filter_value) . "'";
                    }
                }
                if (strlen($ids)) {
                    $where .= (strlen($where) ? " AND " : '') . "cust.groupid IN (".$ids.")";
                }
            } else {
                $where .= (strlen($where) ? " AND " : '') . "cust.groupid = '".$db->escapeString($id)."'";
            }
        }


        if ($params->get('related_to_agents') != 'y') {
            if($id = $params->get('agent')) {
                $not_assigned = false;

                if (!is_array($id)) {
                    $id = array($id);
                }

                $ids = '';
                foreach ($id as $filter_value) {
                    if (strlen($filter_value)) {
                        if ($filter_value != 'none') {
                            $ids .= (strlen($ids) ? ',' : '') . "'" . $db->escapeString($filter_value) . "'";
                        } else {
                            $not_assigned = true;
                        }
                    }
                }
                if (strlen($ids)) {
                    $where .= (strlen($where) ? " AND " : '') . "(t.agent_owner_id IN (".$ids.")" . ($not_assigned ? ' OR t.agent_owner_id IS NULL OR t.agent_owner_id =\'\'' : '') . ")";
                } else if ($not_assigned) {
                    $where .= (strlen($where) ? " AND " : '') . "(t.agent_owner_id IS NULL OR t.agent_owner_id='')";
                }
            }
        } else {
            if ($id = $params->get('agent')) {
                $not_assigned = false;
                if (!is_array($id)) {
                    $id = array($id);
                }

                $ids = '';
                foreach ($id as $filter_value) {
                    if (strlen($filter_value)) {
                        if ($filter_value != 'none') {
                            $ids .= (strlen($ids) ? ',' : '') . "'" . $db->escapeString($filter_value) . "'";
                        } else {
                            $not_assigned = true;
                        }
                    }
                }

                if (strlen($ids)) {
                    if (!$from_mails) {
                        $from .= " INNER JOIN mails m ON (m.ticket_id = t.ticket_id) ";
                        $from_mails = true;
                    }
                    $from .= " INNER JOIN mail_users mu ON (m.mail_id = mu.mail_id) ";
                    $where .= (strlen($where) ? " AND " : '') . "mu.user_id IN (".$ids.")";
                    $params->set('count_group_by', 'no');
                } else if ($not_assigned) {
                    $where .= (strlen($where) ? " AND " : '') . "(t.agent_owner_id IS NULL OR t.agent_owner_id='')";
                }
            }
        }

        if($id = $params->get('filter_status')) {
             
            if (is_array($id)) {
                $ids = '';
                foreach ($id as $filter_value) {
                    if (strlen($filter_value)) {
                        $ids .= (strlen($ids) ? ',' : '') . "'" . $db->escapeString($filter_value) . "'";
                    }
                }
                if (strlen($ids)) {
                    $where .= (strlen($where) ? " AND " : '') . "t.status IN (".$ids.")";
                }
            } else {
                $where .= (strlen($where) ? " AND " : '') . "t.status = '".$db->escapeString($id)."'";
            }
        }

        if($id = $params->get('priority')) {
            if (is_array($id)) {
                $ids = '';
                foreach ($id as $filter_value) {
                    if (strlen($filter_value)) {
                        $ids .= (strlen($ids) ? ',' : '') . "'" . $db->escapeString($filter_value) . "'";
                    }
                }
                if (strlen($ids)) {
                    $where .= (strlen($where) ? " AND " : '') . "t.priority IN (".$ids.")";
                }
            } else {
                $where .= (strlen($where) ? " AND " : '') . "t.priority = '".($db->escapeString($id)*1)."'";
            }
        }

        if($id = $params->get('last_update_from')) {
            $where .= (strlen($where) ? " AND " : '') . "t.last_update > '" . $db->escapeString($id) . "'";
        }
        if($id = $params->get('last_update_to')) {
            $where .= (strlen($where) ? " AND " : '') . "t.last_update < '" . $db->escapeString($id) . "'";
        }

        if($id = $params->get('body')) {
            if (!$from_mails) {
                $from .= " INNER JOIN mails m ON (m.ticket_id = t.ticket_id) ";
                $from_mails = true;
            }
            $where .= (strlen($where) ? " AND " : '') . "MATCH (m.subject, m.body) AGAINST ('".$db->escapeString($id)."' IN BOOLEAN MODE)";
            $params->set('count_group_by', 'no');
        }

        if($id = $params->get('thread_id')) {
            $where .= (strlen($where) ? " AND " : '') . "thread_id = '".$db->escapeString($id)."'";
        }

        if($id = $params->get('customer_id')) {
            $where .= (strlen($where) ? " AND " : '') . "(customer_id = '".$db->escapeString($id)."'
						OR agent_owner_id = '".$db->escapeString($id)."')";
        }

        if (is_array($arrCustomFields = $params->get('custom_fields'))) {
            $objFields = QUnit::newObj('App_Service_Fields');
            $objFields->state = $this->state;
            $objFields->response = $response;
            $arrFields = $objFields->getCustomFieldsRowsArray(null);
            foreach ($arrCustomFields as $customField) {
                if ($customField[1] != "" && isset($arrFields[$customField[0]])) {
                    $where .= (strlen($where) ? " AND " : '') . "(t.ticket_id IN (SELECT ticket_id FROM custom_values cv WHERE t.ticket_id = cv.ticket_id AND cv.field_id='" . $db->escapeString($customField[0]) . "'";
                    switch($arrFields[$customField[0]]['field_type']) {
                        case 'a':
                        case 't':
                            $where .= (strlen($where) ? " AND " : '') . "cv.field_value LIKE '%" . $db->escapeString($customField[1]) . "%'";
                            break;
                        case 'm':
                            $where .= (strlen($where) ? " AND " : '') . "(";
                            $arrValues = explode(',', $customField[1]);
                            $whereMultiple = '0';
                            foreach ($arrValues as $val) {
                                if (strlen($val)) {
                                    $whereMultiple .= " OR cv.field_value LIKE '%" . $db->escapeString($val) . "%'";
                                }
                            }
                            $where .= $whereMultiple . ")";
                            break;
                        case 'c':
                        case 'o':
                            $where .= (strlen($where) ? " AND " : '') . "cv.field_value = '" . $db->escapeString($customField[1]) . "'";
                    }
                    $where .= ")";
                    $where .= " OR t.customer_id IN (SELECT cv.user_id FROM custom_values cv WHERE cv.ticket_id IS NULL AND t.customer_id = cv.user_id AND cv.field_id='" . $db->escapeString($customField[0]) . "'";
                    switch($arrFields[$customField[0]]['field_type']) {
                        case 'a':
                        case 't':
                            $where .= (strlen($where) ? " AND " : '') . "cv.field_value LIKE '%" . $db->escapeString($customField[1]) . "%'";
                            break;
                        case 'm':
                            $where .= (strlen($where) ? " AND " : '') . "(";
                            $arrValues = explode(',', $customField[1]);
                            $whereMultiple = '0';
                            foreach ($arrValues as $val) {
                                if (strlen($val)) {
                                    $whereMultiple .= " OR cv.field_value LIKE '%" . $db->escapeString($val) . "%'";
                                }
                            }
                            $where .= $whereMultiple . ")";
                            break;
                        case 'c':
                        case 'o':
                            $where .= (strlen($where) ? " AND " : '') . "cv.field_value = '" . $db->escapeString($customField[1]) . "'";
                    }
                    $where .= "))";
                }
            }
        }


        //return just tickets, where user has rights
        switch ($session->getVar('userType')) {
            case 'g':
                //limitations for agent - can see just tickets from public queues a z queues kde je priradeny
                $where .= (strlen($where) ? " AND " : '') . "(t.agent_owner_id = '" . $session->getVar('userId') . "' OR t.queue_id IN (SELECT queue_id from queues where public = 'y' OR (public<>'y' AND queue_id IN (SELECT queue_id FROM queue_agents WHERE user_id = '" . $session->getVar('userId') . "'))))";
                break;
            case 'u':
                if ($this->state->config->get('userCanSeeAllTicketsInGroup') == 'y') {
                    // all tickets of same group
                    $where .= (strlen($where) ? " AND " : '') . "(t.customer_id = '" . $session->getVar('userId') . "' OR t.customer_id IN (SELECT user_id FROM users WHERE groupid='" . $session->getVar('groupId') . "'))";
                } else {
                    //    limitation for user - just own tickets
                    $where .= (strlen($where) ? " AND " : '') . "t.customer_id = '" . $session->getVar('userId') . "'";
                }
                break;
            default:
                // this is request from server or from admin user, do nothing
        }

        if ($from_mails) {
            $params->set('distinct', true);
            $params->set('group', 't.ticket_id');
            $params->set('count_columns', 't.ticket_id');
        }

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'tickets');


        //log reading
        $user = QUnit::newObj('App_Service_Users');
        $user->state = $this->state;
        $user->response = $this->response;
        $user->updateLoginLog('tickets_list', 'null');


        return $this->callService('SqlTable', 'select', $params);
    }

    /**
     * Returns just values from tickets table (no joins)
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function getTicketsRows($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $columns = "*";
        $from = "tickets";
        $where = "";
        if($id = $params->get('ticket_id')) {
            $where .= (strlen($where) ? " AND " : '') . "ticket_id = '".$db->escapeString($id)."'";
        }
        if($id = $params->get('subject_ticket_id')) {
            $where .= (strlen($where) ? " AND " : '') . "subject_ticket_id = BINARY '".$db->escapeString($id)."'";
        }
        if($id = $params->get('thread_id')) {
            $where .= (strlen($where) ? " AND " : '') . "thread_id = '".$db->escapeString($id)."'";
        }
        if($id = $params->get('account_id')) {
            $where .= (strlen($where) ? " AND " : '') . "ticket_id IN (SELECT ticket_id from mails WHERE account_id='" . $db->escapeString($id) . "')";
        }

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'tickets');
        return $this->callService('SqlTable', 'select', $params);
    }

    /**
     * Generates random string depending on format
     * Z - represents any character between A-Z
     * X - represents characters 0-9, a-z, A-Z
     * z - represents any character between a-z
     * 9 - represents any number between 0-9
     * all other characters are used as in format
     */
    function getRandomString($format='ZZZZZZ-99999') {
        $chars_Z = str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $chars_z = str_shuffle('abcdefghijklmnopqrstuvwzxy');
        $chars_X = str_shuffle('1234567890abcdefghijklmnopqrstuvwzxyABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $chars_9 = str_shuffle('1234567890');
        $result = '';
        for ($i = 0; $i < strlen($format); $i++) {
            switch($format[$i]) {
                case 'X':
                case ' ':
                    $result .= $chars_X[rand(0,strlen($chars_X)-1)];
                    break;
                case 'Z':
                    $result .= $chars_Z[rand(0,strlen($chars_Z)-1)];
                    break;
                case 'z':
                    $result .= $chars_z[rand(0,strlen($chars_z)-1)];
                    break;
                case '9':
                    $result .= $chars_9[rand(0,strlen($chars_9)-1)];
                    break;
                default:
                    $result .= $format[$i];
                    break;
            }
        }
        return $result;
    }

    /**
     * Generates subject ticket id
     *
     * @return unknown
     */
    function generateSubjectTicketId($iteration = 0) {
        $response =& $this->getByRef('response');
         
        if (strlen(trim($this->state->config->get('ticketIdFormat'))) && $iteration < 10) {
            $format = trim($this->state->config->get('ticketIdFormat'));
        } else {
            $format = 'ZZZ-XXXXX-999';
        }

        $newId = $this->getRandomString($format);
         
        //check if newId nieje uz v db
        $paramsTicket = $this->createParamsObject();
        $paramsTicket->set('subject_ticket_id', $newId);
        if ($this->callService('Tickets', 'getTicketsRows', $paramsTicket)) {
            $result = & $response->getByRef('result');
            if ($result['count'] > 0) {
                //call in recursion and get new Id if this is used already
                return $this->generateSubjectTicketId($iteration++);
            }
        }

         
        return $newId;
    }

    function getSubjectTicketId($subject) {
        if (is_string($subject) && preg_match('/\[.*?#(.*?)\]/', $subject, $match)) {
            return $match[1];
        }
        return false;
    }

    function removeTicketIdFromSubject($subject, $subject_ticket_id) {
        if (strlen($subject_ticket_id)) {
            $subject = preg_replace('|\[.*?#' . preg_quote($subject_ticket_id) . '\][\s:]*|', '', $subject);
        }
        return $subject;
    }

    function buildSubjectTicketId($arrTicket) {
        if ($this->state->config->get('hideTicketIdFromSubject') == 'y') {
            return '';
        }

        //get ticket_id_prefix from queue of ticket
        $response =& $this->getByRef('response');
   	   	$db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
        $ticket_id_prefix = '';
        //load ticket
        if (strlen($arrTicket['queue_id'])) {
            $paramsQueue = $this->createParamsObject();
            $paramsQueue->set('queue_id', $arrTicket['queue_id']);
            if ($ret = $this->callService('Queues', 'getQueueListAllFields', $paramsQueue)) {
                $res = & $response->getByRef('result');
                if ($res['count'] > 0) {
                    $queue = $res['rs']->getRows($res['md']);
                    $queue = $queue[0];
                    $queue_prefix = $queue['ticket_id_prefix'];
                }
            }
        }

        return '[' . $queue_prefix . '#' . $arrTicket['subject_ticket_id'] . ']';
    }

    function getTicketByMailSubject($subject) {
        $response =& $this->getByRef('response');
        //parse subject
        if ($ticket_id = $this->getSubjectTicketId($subject)) {
            //if found id, check if it is a valid ticket_id
            $paramsTicket = $this->createParamsObject();
            $paramsTicket->set('subject_ticket_id', $ticket_id);
            if ($this->callService('Tickets', 'getTicketsRows', $paramsTicket)) {
                $result = & $response->getByRef('result');
                if ($result['count'] > 0) {
                    $rows = $result['rs']->getRows($result['md']);
                    return $rows[0]['ticket_id'];
                }
            }
        }
        return false;
    }


    function logTicketStatusChange($ticket_id, $new_status, $new_queue_id, $new_agent_owner_id, $new_priority) {
        $db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
        if (!strlen($ticket_id) || (!strlen($new_status) && !strlen($new_queue_id) && !strlen($new_agent_owner_id) && !strlen($new_priority))) {
            return false;
        }

        if (!is_array($ticket_id)) $ticket_id = array($ticket_id);

        foreach ($ticket_id as $t) {
            $params = $this->createParamsObject();
            $params->setField('log_id', 0);
            $params->setField('ticket_id', $t);
            $params->setField('created', $db->getDateString());
            $params->setField('created_by_user_id', (!strlen($session->getVar('userId')) ? '' : $session->getVar('userId')));
            $params->setField('new_status', $new_status);
            $params->setField('new_queue_id', $new_queue_id);
            $params->setField('new_agent_owner_id', $new_agent_owner_id);
            if (strlen($new_priority)) {
                $params->setField('new_priority', $new_priority);
            }
             
            $params->set('table', 'ticket_changes');
            if (!$this->callService('SqlTable', 'insert', $params)) {
                return false;
            }
        }
    }


    function reportNewTicket(&$params) {
        $session = QUnit::newObj('QUnit_Session');
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');

        if (!$params->check(array('subject', 'body'))) {
            $response->set('error', $this->state->lang->get('missingMandatoryFields'));
            return false;
        }

        $paramsTicket = $this->createParamsObject();
        $paramsTicket->set('mail_body', $params->get('body'));
        $paramsTicket->set('mail_body_html', $params->get('body_html'));

        $paramsTicket->setField('ticket_id', 0);
        if (strlen(trim($params->get('queue_id')))) {
            $paramsTicket->setField('queue_id', $params->get('queue_id'));
        }

        if (strlen($params->get('priority'))) {
            $paramsTicket->setField('priority', $params->get('priority'));
        }

        if (strlen($params->getField('customer_id'))) {
            $paramsTicket->setField('customer_id', $params->getField('customer_id'));
        } else {
            $paramsTicket->setField('customer_id', $session->getVar('userId'));
        }

        if (strlen($params->get('agent_owner_id'))) {
            $paramsTicket->setField('agent_owner_id', $params->get('agent_owner_id'));
        }

        if (strlen($params->get('subject'))) {
            $paramsTicket->setField('first_subject', $params->get('subject'));
        }

        //custom Fields
        $paramsTicket->set('custom', $params->get('custom'));

        if ($ret = $this->insertTicket($paramsTicket)) {
            $params->set('ticket_id', $paramsTicket->get('ticket_id'));
        } else {
            $this->state->log('error', 'Failed to insert new Ticket', 'Ticket');
            return false;
        }
        return $ret;
    }

    function insertTicket(&$params) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
        $params->set('table', 'tickets');

        $params->setField('subject_ticket_id', $this->generateSubjectTicketId());
        $params->setField('ticket_id', 0);
        $params->setField('mails_count', 1);

        if (strlen($params->getField('thread_id'))) {
            //check if thread id already exists
            $paramsTickets = $this->createParamsObject();
            $paramsTickets->set('thread_id', $params->getField('thread_id'));
            if ($this->getTicketsRows($paramsTickets)) {
                $result = & $response->getByRef('result');
                if ($result['count'] > 0) {
                    //unset thread id and generate new value, because it will fail on duplicity
                    $params->setField('thread_id', '');
                }
            }
        }

        if (!strlen($params->getField('thread_id'))) {
            $domain = '@supportcenterpro.com';
            if (isset($_SERVER['HTTP_HOST']) && strlen($_SERVER['HTTP_HOST'])) {
                $domain = '@'.$_SERVER['HTTP_HOST'];
            }
            $params->setField('thread_id', md5(uniqid(rand(), true)) . $domain);
        }
        if (!strlen($params->getField('status'))) {
            $params->setField('status', TICKET_STATUS_NEW);
        }
        if (!strlen($params->getField('priority'))) {
            if (is_numeric($this->state->config->get('defaultPriority'))) {
                $params->setField('priority', $this->state->config->get('defaultPriority'));
            } else {
                $params->setField('priority', 50);
            }
        }

        if (!strlen($params->getField('queue_id'))) {
            $paramsQueues = $this->createParamsObject();
            if ($this->callService('Queues', 'getDefaultQueue', $paramsQueues)) {
                $result = & $response->getByRef('result');
                if ($result['count'] < 1) {
                    $this->state->log('error', 'Missing Default Queue', 'MailParser');
                    return false;
                } elseif ($result['count'] > 1) {
                    $this->state->log('warning', 'Exist more than one Default Queue, choosed first Queue', 'MailParser');
                }
                $rows = $result['rs']->getRows($result['md']);
                $params->setField('queue_id', $rows[0]['queue_id']);
            } else {
                $this->state->log('error', 'Failed to retrieve Default Queue', 'MailParser');
                return false;
            }
        }

        $params->setField('created', $db->getDateString());
        $params->setField('last_update', $params->getField('created'));

        if ($ret = $this->callService('SqlTable', 'insert', $params)) {
            $params->set('ticket_id', $response->result);
            $this->updateCustomFields($params, true);
            $oNotifications = QUnit::newObj('App_Service_Notifications');
            $oNotifications->state = $this->state;
            $oNotifications->response = $response;
            $notifications = $oNotifications->getApplicableNotificationSettings("'" . $params->get('ticket_id') . "'", $params->get('status'), $params->get('queue_id'), $params->get('agent_owner_id'), $params->get('priority'));
            $oNotifications->notifyUsers($notifications, $params->get('status'), $params->get('queue_id'), $params->get('agent_owner_id'), $params->get('priority'), $params->get('mail_body'), $params->get('hdr_message_id'));
             
            //log change of ticket
            $this->logTicketStatusChange($params->get('ticket_id'), $params->get('status'), $params->get('queue_id'), $params->get('agent_owner_id'), $params->get('priority'));
        } else {
        }
        return $ret;
    }

    function updateCustomFields($params, $justNotEmpty = false) {
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
                    $fldParams->setField('ticket_id', $params->get('ticket_id'));
                    $fldParams->setField('user_id', $params->get('customer_id'));
                    $fldParams->setField('field_value', $fld);
                    if ($justNotEmpty && !strlen($fld)) {
                        continue;
                    }
                    if (!$this->callService('Fields', 'insertFieldValue', $fldParams)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    function updateTicket($params) {
        //update ticket
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');
         
        $ids = $params->get('ticket_id');
        $where_ids = '';
        if (!is_array($ids)) $ids = array($ids);
        foreach ($ids as $id) {
            if (strlen(trim($id))) {
                $where_ids .= (strlen($where_ids) ? ', ': '');
                $where_ids .= "'" . $db->escapeString(trim($id)) . "'";
            }
        }

        if(!$params->check(array('ticket_id')) || !strlen($where_ids)) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }
         
        $this->deleteDisplayedTicketStatus($where_ids);

         
        $where = "ticket_id IN (" . $where_ids . ")";
         
        //security check - user can update just own tickets
        if ($session->getVar('userType') == 'u') {
            if ($this->state->config->get('userCanSeeAllTicketsInGroup') == 'y') {
                // all tickets of same group
                $where .= " AND (customer_id = '" . $session->getVar('userId') . "' OR customer_id IN (SELECT user_id FROM users WHERE groupid='" . $session->getVar('groupId') . "'))";
            } else {
                //    limitation for user - just own tickets
                $where .= " AND customer_id = '" . $session->getVar('userId') . "'";
            }
        }
         
        $params->set('table', 'tickets');
        $params->set('where', $where);

        $oNotifications = QUnit::newObj('App_Service_Notifications');
        $oNotifications->state = $this->state;
        $oNotifications->response = $response;
        if (!$params->get('contact_form')) {
            $notifications = $oNotifications->getApplicableNotificationSettings($where_ids, $params->get('status'), $params->get('queue_id'), $params->get('agent_owner_id'), $params->get('priority'));
        } else {
            $notifications = array();
        }


        if ($ret = $this->callService('SqlTable', 'update', $params)) {
            $this->updateMailsCount($params->get('ticket_id'));
            $oNotifications->notifyUsers($notifications, $params->get('status'), $params->get('queue_id'), $params->get('agent_owner_id'), $params->get('priority'), $params->get('mail_body'));
            $this->logTicketStatusChange($params->get('ticket_id'), $params->get('status'), $params->get('queue_id'), $params->get('agent_owner_id'), $params->get('priority'));
        }
         
        return $ret;
    }

    function updateMailsCount($ticket_id)  {
        $db =& $this->state->getByRef('db');

        $where_ids = '';
        if (!is_array($ticket_id)) $ticket_id = array($ticket_id);
        foreach ($ticket_id as $id) {
            if (strlen(trim($id))) {
                $where_ids .= (strlen($where_ids) ? ', ': '');
                $where_ids .= "'" . $db->escapeString(trim($id)) . "'";
            }
        }
         
        $sql = "UPDATE tickets t
				SET mails_count=(SELECT count(*) from mails m WHERE m.ticket_id = t.ticket_id)
				WHERE t.ticket_id IN (" . $where_ids . ")";    	
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            return false;
        }
        return true;
    }

    function insertDisplayedTicketStatus($ticket_id) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');

        if (!strlen($session->getVar('userId'))) {
            return true;
        }
         
        $params = $this->createParamsObject();
        $params->set('table', 'displayed_tickets');

        $params->setField('created', $db->getDateString());
        $params->setField('user_id', $session->getVar('userId'));
        $params->setField('ticket_id', $ticket_id);
        $params->set('ignore', true);
        return $this->deleteDisplayedTicketStatus($ticket_id, $session->getVar('userId')) &&
        $this->callService('SqlTable', 'insert', $params);
    }

    /**
     * Delete displayed status for selected tickets
     */
    function deleteDisplayedTicketStatus($where_ids, $user_id = '') {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
         
        if ($user_id != '' && $user_id == $session->getVar('userId')) {
            return true;
        }
         
        if (strlen($where_ids)) {
            $sql = "delete from displayed_tickets where ticket_id IN (" . $where_ids . ")";

            if (strlen($user_id)) {
                $sql .= ' AND user_id=\'' .  $db->escapeString($user_id) . "'";
            }
            if (strlen($session->getVar('userId'))) {
                $sql .= " AND user_id <> '" .  $db->escapeString($session->getVar('userId')) . "'";
            }

            $sth = $db->execute($sql);
            $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
            if(!$this->_checkDbError($sth)) {
                $response->set('error', $sth->get('errorMessage'));
                $this->state->log('error', $sth->get('errorMessage') , 'Ticket');
                return false;
            }
        }
        return true;
    }


    /**
     *  deleteTicket
     *
     *  @param string table
     *  @param string id id of task
     *  @return boolean
     */
    function deleteTicket($params) {
        global $serverDeleteTickets;
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
        if ($this->isDemoMode()) {
            $response->set('error', $this->state->lang->get('notAlloweInDemoMode'));
            return false;
        }

   	   	if (!$serverDeleteTickets && $session->getVar("userType") != 'a' &&
   	   	!($session->getVar("userType") == 'g' && $this->state->config->get('agentDeleteTickets') == 'y'))  {
   	   	    $response->set('error', $this->state->lang->get('permissionDenied'));
   	   	    return false;
   	   	}

   	   	if (is_array($params->get('ticket_id'))) {
   	   	    $ids = $params->get('ticket_id');
   	   	} else {
   	   	    $ids = explode('|',$params->get('ticket_id'));
   	   	}
   	   	$where_ids = '';
   	   	foreach ($ids as $id) {
   	   	    if (strlen(trim($id))) {
   	   	        $where_ids .= (strlen($where_ids) ? ', ': '');
   	   	        $where_ids .= "'" . $db->escapeString(trim($id)) . "'";
   	   	    }
   	   	}
   	   	 
   	   	if(!$params->check(array('ticket_id')) || !strlen($where_ids)) {
   	   	    $response->set('error', $this->state->lang->get('noIdProvided'));
   	   	    return false;
   	   	}

   	   	$this->deleteDisplayedTicketStatus($where_ids);
   	   	 
   	   	//Load mail_ids
   	   	$sql = "SELECT mail_id FROM mails WHERE ticket_id IN (" . $where_ids . ")";
   	   	$sth = $db->execute($sql);
   	   	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
   	   	if(!$this->_checkDbError($sth)) {
   	   	    $response->set('error', $this->state->lang->get('failedToLoadMailIds') . $params->get('subject_ticket_id'));
   	   	    $this->state->log('error', $response->get('error') . " -> " . $sth->get('errorMessage') , 'Ticket');
   	   	    return false;
   	   	}
   	   	$rows = $sth->fetchAllRows();
   	   	$mail_ids = '';
   	   	foreach ($rows as $row) {
   	   	    if (strlen($row[0])) {
   	   	        $mail_ids .= (strlen($mail_ids) ? ', ' : '') . "'" . $db->escapeString($row[0]) . "'";
   	   	    }
   	   	}
   	   	 
   	   	if (strlen($where_ids)) {
   	   	    $sql = "delete from custom_values where ticket_id IN (" . $where_ids . ")";
   	   	    $sth = $db->execute($sql);
   	   	    $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
   	   	    if(!$this->_checkDbError($sth)) {
   	   	        $response->set('error', $this->state->lang->get('failedDeleteCustomFieldsValues') . $params->get('subject_ticket_id'));
   	   	        $this->state->log('error', $response->get('error') . " -> " . $sth->get('errorMessage') , 'Ticket');
   	   	        return false;
   	   	    }
   	   	}

   	   	//delete mail_users
   	   	if (strlen($mail_ids)) {

   	   	    if ($params->get('delete_ticket_users')) {
   	   	        //load list of ticket users with only one ticket

   	   	        $sql = "SELECT v.user_id
						FROM (SELECT DISTINCT u.user_id, m.ticket_id 
						FROM mail_users mu 
						INNER JOIN (
							SELECT DISTINCT mu.user_id 
							FROM `mail_users` mu 
							INNER JOIN users u ON (u.user_id=mu.user_id AND u.user_type='u') 
							WHERE mail_id IN (" . $mail_ids . ")
						) u ON mu.user_id = u.user_id
						INNER JOIN mails m on m.mail_id = mu.mail_id) v
						GROUP BY v.user_id
						HAVING COUNT(v.ticket_id) < 2
						";

   	   	        $sth = $db->execute($sql);
   	   	        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
   	   	        if(!$this->_checkDbError($sth)) {
   	   	            $response->set('error', $this->state->lang->get('failedDeleteMailUsers') . $params->get('subject_ticket_id'));
   	   	            $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Ticket');
   	   	            return false;
   	   	        }
   	   	        $delete_user_ids = array();
   	   	        while($row=$sth->fetchArray()) {
   	   	            if (strlen($row['user_id'])) $delete_user_ids[] = $row['user_id'];
   	   	        }
   	   	         
   	   	        if (!empty($delete_user_ids)) {

   	   	            $paramsDeleteUsers = $this->createParamsObject();
   	   	            $paramsDeleteUsers->set('user_id', $delete_user_ids);

   	   	            if (!$this->callService('Users', 'deleteUser', $paramsDeleteUsers)) {
   	   	                $this->state->log('error', $response->get('error'), 'Ticket');
   	   	                return false;
   	   	            }
   	   	        }
   	   	    }
   	   	     
   	   	    //delete mail users
   	   	    $sql = "delete from mail_users where mail_id IN (" . $mail_ids . ")";
   	   	    $sth = $db->execute($sql);
   	   	    $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
   	   	    if(!$this->_checkDbError($sth)) {
   	   	        $response->set('error', $this->state->lang->get('failedDeleteMailUsers') . $params->get('subject_ticket_id'));
   	   	        $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Ticket');
   	   	        return false;
   	   	    }
   	   	}

   	   	//delete ticket_statuses
   	   	if (strlen($where_ids)) {
   	   	    $sql = "delete from ticket_changes where ticket_id IN (" . $where_ids . ")";
   	   	    $sth = $db->execute($sql);
   	   	    $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
   	   	    if(!$this->_checkDbError($sth)) {
   	   	        $response->set('error', $this->state->lang->get('failedDeleteTicketStatuses') . $params->get('subject_ticket_id'));
   	   	        $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Ticket');
   	   	        return false;
   	   	    }
   	   	}

   	   	//Load file ids
   	   	$file_ids = '';
   	   	if (strlen($mail_ids)) {
   	   	    $sql = "SELECT file_id FROM mail_attachments WHERE mail_id IN (" . $mail_ids . ")";
   	   	    $sth = $db->execute($sql);
   	   	    $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
   	   	    if(!$this->_checkDbError($sth)) {
   	   	        $response->set('error', $this->state->lang->get('failedToLoadFileIds'));
   	   	        $this->state->log('error', $response->get('error') . " -> " . $sth->get('errorMessage') , 'Ticket');
   	   	        return false;
   	   	    }
   	   	    $rows = $sth->fetchAllRows();
   	   	    foreach ($rows as $row) {
   	   	        if (strlen($row[0])) {
   	   	            $file_ids .= (strlen($file_ids) ? ', ' : '') . "'" . $db->escapeString($row[0]) . "'";
   	   	        }
   	   	    }
   	   	}

   	   	 
   	   	//delete file_contents
   	   	if (strlen($file_ids)) {
   	   	    $sql = "delete from file_contents where file_id IN ($file_ids)";
   	   	    $sth = $db->execute($sql);
   	   	    $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
   	   	    if(!$this->_checkDbError($sth)) {
   	   	        $response->set('error', $this->state->lang->get('failedDeleteMailAttContent') . $params->get('subject_ticket_id'));
   	   	        $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Ticket');
   	   	        return false;
   	   	    }
   	   	}


   	   	//delete mail_attachments
   	   	if (strlen($mail_ids)) {
   	   	    $sql = "delete from mail_attachments WHERE mail_id IN (" . $mail_ids . ")";
   	   	    $sth = $db->execute($sql);
   	   	    $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
   	   	    if(!$this->_checkDbError($sth)) {
   	   	        $response->set('error', $this->state->lang->get('failedDeleteMailAttachments') . $params->get('subject_ticket_id'));
   	   	        $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Ticket');
   	   	        return false;
   	   	    }
   	   	}
   	   	 
   	   	//delete files, which have no contents
   	   	if (strlen($file_ids)) {
   	   	    $sql = "delete from files where file_id IN ($file_ids)";
   	   	    $sth = $db->execute($sql);
   	   	    $this->state->log('debug', $sql , 'Ticket');
   	   	    if(!$this->_checkDbError($sth)) {
   	   	        $response->set('error', $this->state->lang->get('failedDeleteMailAttContent') . $params->get('subject_ticket_id'));
   	   	        $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Ticket');
   	   	        return false;
   	   	    }
   	   	}
   	   	 
   	   	//delete mails
   	   	if (strlen($where_ids)) {
   	   	    $sql = "delete FROM mails WHERE ticket_id IN (" . $where_ids . ")";
   	   	    $sth = $db->execute($sql);
   	   	    $this->state->log('debug', $sql , 'Ticket');
   	   	    if(!$this->_checkDbError($sth)) {
   	   	        $response->set('error', $this->state->lang->get('failedDeleteMails') . $params->get('subject_ticket_id'));
   	   	        $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Ticket');
   	   	        return false;
   	   	    }
   	   	}
   	   	 
   	   	$params->set('table', 'tickets');
   	   	$params->set('where', "ticket_id IN (" . $where_ids . ")");
   	   	if ($ret = $this->callService('SqlTable', 'delete', $params)) {
   	   	    $this->state->log('info', 'Deleted tickets ' . $params->get('subject_ticket_id'), 'Ticket');
   	   	} else {
   	   	    $sql_error = $response->get('error');
   	   	    $response->set('error', $this->state->lang->get('failedDeleteTicket') . $params->get('subject_ticket_id'));
   	   	    $this->state->log('error', $response->get('error') . ' ' . $sql_error, 'Ticket');
   	   	}
   	   	return $ret;
    }

    /**
     * Get mails by ticket_id
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function getMailsList($params) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
         
        if(!$params->check(array('ticket_id'))) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }
         
       	$columns = "mail_id, delivery_status, ma.account_id, ma.account_name, ma.account_email, subject, is_answered, created, delivery_date, (UNIX_TIMESTAMP('" . $db->getDateString() . "') - UNIX_TIMESTAMP(created)) as age, is_comment";
       	 
       	if ($params->get('body')) {
       	    $columns .= ', body, body_html';
       	}


        $from = "mails LEFT JOIN mail_accounts ma ON (ma.account_id = mails.account_id)";
        $where = "ticket_id = '".$db->escapeString($params->get('ticket_id'))."'";

        if ($session->getVar('userType') == 'u') {
            $where .= " AND is_comment = 'n'";
        }

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'queues');
        $params->set('order', $params->get('order') ? $params->get('order') : 'created');
        $params->set('orderDirection', $params->get('orderDirection') ? $params->get('orderDirection') : 'DESC');
        return $this->callService('SqlTable', 'select', $params);
    }

    /**
     * Get attachments by ticket_id
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function getMailAttachmentsList($params) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
         
        if(!$params->check(array('ticket_id'))) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }
         
       	$columns = "ma.mail_id, f.file_id, f.filename, f.filesize, f.filetype, f.contentid";
       	 
        $from = "mail_attachments ma
				INNER JOIN files f ON (ma.file_id = f.file_id) 
				INNER JOIN mails ON (ma.mail_id = mails.mail_id)";
        $where = "mails.ticket_id = '".$db->escapeString($params->get('ticket_id'))."'";

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'queues');
        $params->set('order', 'ma.mail_id');
        return $this->callService('SqlTable', 'select', $params);
    }

    /**
     * Get users by ticket_id
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function getUsersList($params) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
         
        if(!$params->check(array('ticket_id'))) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }
         
       	$columns = "mails.mail_id, u.user_id, u.name, u.email, mail_role, u.user_type, u.email_quality";
       	 
        $from = "users u INNER JOIN mail_users ON (u.user_id = mail_users.user_id) INNER JOIN mails ON (mail_users.mail_id = mails.mail_id)";
        $where = "mails.ticket_id = '".$db->escapeString($params->get('ticket_id'))."'";

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'queues');
        $params->set('order', 'mail_id');
        return $this->callService('SqlTable', 'select', $params);
    }

    /**
     * get data about all mails in ticket
     */
    function getMailsData($params) {
        $result = array();
        $response =& $this->getByRef('response');
         
        if ($ret = $this->getMailsList($params)) {
            $result['mails'] = $response->get('result');
        } else {
            $response->set('error', $this->state->lang->get('failedToListTicketMails'));
            return false;
        }
        if ($ret = $this->getMailAttachmentsList($params)) {
            $result['attachments'] = $response->get('result');
        } else {
            $response->set('error', $this->state->lang->get('failedToListTicketAttachments') . ' ' . $response->get('error'));
            return false;
        }
        if ($ret = $this->getUsersList($params)) {
            $result['users'] = $response->get('result');
        } else {
            $response->set('error', $this->state->lang->get('failedToListTicketUsers'));
            return false;
        }

        if ($ret = $this->getTicketHistory($params)) {
            $result['history'] = $response->get('result');
        } else {
            $response->set('error', $this->state->lang->get('failedToListTicketHistory'));
            return false;
        }
         
        if ($ret = $this->getTicketHistory($params)) {
            $result['history'] = $response->get('result');
        } else {
            $response->set('error', $this->state->lang->get('failedToListTicketHistory'));
            return false;
        }
         
        if ($result['mails']['count'] > 0) {
            $paramsMailBody = $this->createParamsObject();
            $paramsMailBody->set('mail_id', $result['mails']['rs']->rows[0][0]);
            if ($ret = $this->callService('Mails', 'getMailBody', $paramsMailBody)) {
                $result['firstBody'] = $response->get('result');
            } else {
                return false;
            }
        }

        if ($ret = $this->getCustomFieldValues($params)) {
            $result['custom'] = $response->get('result');
        } else {
            return false;
        }
         
        //log reading
        $user = QUnit::newObj('App_Service_Users');
        $user->state = $this->state;
        $user->response = $this->response;
        $user->updateLoginLog('reading', $params->get('ticket_id'));

        //add status, that ticket was seen by user
        $this->insertDisplayedTicketStatus($params->get('ticket_id'));
         
        $response->set('result', $result);
        return true;
    }

    function getAttachmentsFromSubmitForm() {
        global $_FILES;
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
        $arrFiles = array();
        if (isset($_FILES) && is_array($_FILES)) {
            foreach ($_FILES as $file) {
                if ($file['error'] == UPLOAD_ERR_OK) {
                    $downloader = QUnit::newObj('App_Service_Files');
                    $downloader->state = $this->state;
                    $downloader->response = $response;
                    if ($file_id = $downloader->uploadFileLocal($file)) {
                        $arrFiles[] = $file_id;
                    }
                }
            }
        }
        return $arrFiles;
    }

    function newAnonymTicket(&$params) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');

        if (!$params->check(array('name', 'email', 'subject', 'body'))) {
            $response->set('error', $this->state->lang->get('missingMandatoryFields'));
            return false;
        }

        //check attachments
        $arrAttachments = $this->getAttachmentsFromSubmitForm();
        if ($arrAttachments === false) {
            return false;
        }

        if ($this->state->config->get('checkEmailQuality')=='y') {
            $objUser = QUnit::newObj('App_Service_Users');
            $objUser->state = $this->state;
            $objUser->response = $response;
             
            if ($objUser->getEmailQuality($params->get('email')) < 2) {
                $response->set('error', $this->state->lang->get('incorrectEmail'));
                return false;
            }
        }

        $paramsTicket = $this->createParamsObject();
        $paramsUser = $this->createParamsObject();
        $paramsUser->set('email', $params->get('email'));

        if ($arrAttachments !== false && !empty($arrAttachments)) {
            $paramsTicket->set('attach_ids', $arrAttachments);
        } elseif ($params->check('attach_ids')) {
            $paramsTicket->set('attach_ids', $params->get('attach_ids'));
        }

        //if user exist, log his userId
        if($this->callService('Users', 'getUserByEmail', $paramsUser)) {
            if($response->getResultVar('count') > 0) {
                $result = $response->result;
                $rows = $result['rs']->getRows($result['md']);
                $paramsTicket->setField('customer_id',$rows[0]['user_id']);
            } else {
                //create new user if user doesn't exist
                $paramsUser = $this->createParamsObject();
                $paramsUser->setField('name', $params->get('name'));
                $paramsUser->setField('email', $params->get('email'));

                if (!$this->callService('Users', 'insertUser', $paramsUser)) {
                    $this->state->log('error', 'Failed to create new user ' . $params->get('email') . ' with error: ' . $response->error,'MailParser');
                    return false;
                } else {
                    $paramsTicket->setField('customer_id',$paramsUser->get('user_id'));
                }
            }
        } else {
            return false;
        }

        $paramsTicket->set('email', $params->get('email'));


        //report ticket
        $paramsTicket->set('isPlainText', "1");
        $paramsTicket->set('contact_form', true);
        $paramsTicket->setField('subject', $params->get('subject'));
        $paramsTicket->setField('priority', $params->get('priority'));

        //set custom fields if there are any
        $paramsTicket->set('custom', $params->get('custom'));



        if (strlen($params->get('queue_id'))) {
            //check if submitted queue_id exist
            $paramsQueue = $this->createParamsObject();
            $paramsQueue->set('queue_id', $params->get('queue_id'));
            if ($this->callService('Queues', 'existQueue', $paramsQueue)) {
                $paramsTicket->setField('queue_id', $params->get('queue_id'));
            } else {
                $response->set('error', $this->state->lang->get('queueDoesntExists'));
                return false;
            }
        }

        $paramsTicket->setField('body', $params->get('body'));
        $paramsTicket->setField('body_html', $params->get('body_html'));
        $paramsTicket->set('status', TICKET_STATUS_NEW);
        $res = $this->replyMail($paramsTicket);
        $params->set('ticket_id', $paramsTicket->get('ticket_id'));
        return $res;
    }

    function replyMail(&$params) {
        $response =& $this->getByRef('response');
   	   	$db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');

   	   	if (!$params->get('contact_form') && ($session->getVar("userType") == 'a' || $session->getVar("userType") == 'g')) {
   	   	    if (!($mailAccount = $this->loadReplyAccount($params))) {
   	   	        $response->set('error', $this->state->lang->get('failedToLoadMailAccount'));
   	   	        return false;
   	   	    }
   	   	}

        //check if it is new ticket
   	   	if (!$params->check(array('ticket_id'))) {

   	   	    if (!$params->get('contact_form') && !strlen($params->getField('customer_id')) &&
   	   	    ($session->getVar('userType') == 'a' || $session->getVar('userType') == 'g')) {
   	   	        //set customer_id from TO mail address and not as agent


   	   	        $to_emails = QUnit_Net_Mail::prepareEmail(str_replace(';', ',', $params->get('to')));

   	   	        foreach ($to_emails as $idx => $email) {

   	   	            if (strlen($newMail = QUnit_Net_Mail::getEmailAddress($to_emails, $idx))) {
   	   	                 
   	   	                $params->set('new_ticket_customer_email', $newMail);


   	   	                //load or create user
   	   	                 
   	   	                $paramsUsrSearch = $this->createParamsObject();
   	   	                $paramsUsrSearch->set('email', $newMail);
   	   	                 
   	   	                if (!$this->callService('Users', 'getUserByEmail', $paramsUsrSearch)) {
   	   	                    $this->state->log('error', 'Failed request if user ' . $user['email'] . ' exist with error: ' . $response->error,'MailParser');
   	   	                    return false;
   	   	                }
   	   	                if($response->getResultVar('count') == 0) {
   	   	                    //ak neexistuje, vytvor ho
   	   	                    $paramsUser = $this->createParamsObject();
   	   	                    $paramsUser->setField('name', '');
   	   	                    $paramsUser->setField('email', $newMail);
   	   	                     
   	   	                    if (!$this->callService('Users', 'insertUser', $paramsUser)) {
   	   	                        $this->state->log('error', 'Failed to create new user ' . $user['email'] . ' with error: ' . $response->error,'MailParser');
   	   	                        return false;
   	   	                    } else {
   	   	                        $params->setField('customer_id', $paramsUser->get('user_id'));
   	   	                    }
   	   	                } else {
   	   	                    //nasiel usera
   	   	                    $result = $response->result;
   	   	                    $rows = $result['rs']->getRows($result['md']);
   	   	                    $params->setField('customer_id', $rows[0]['user_id']);
   	   	                }
   	   	                break;
   	   	            }
   	   	        }

   	   	    }
   	   	     
   	   	    //generate new ticket id
   	   	    if (!$this->reportNewTicket($params)) {
   	   	        return false;
   	   	    }
   	   	} else {
   	   	    if (!($parentMail = $this->loadParentMail($params))) {
   	   	        $response->set('error', $this->state->lang->get('failedToLoadParentMail'));
   	   	        return false;
   	   	    }
   	   	}
   	   	if (!($parentTicket = $this->loadTicket($params))) {
   	   	    $response->set('error', $this->state->lang->get('failedToLoadParentTicket'));
   	   	    return false;
   	   	}

   	   	//Send mail
   	   	//   	   	if ($params->get('send_mail') != 'n') {
   	   	if (!$this->sendMail($params, $mailAccount, $parentTicket, $parentMail)) {
   	   	    if (!strlen($response->get('error'))) {
   	   	        $response->set('error', $this->state->lang->get('failedToSendEmail'));
   	   	    }
   	   	    return false;
   	   	}
   	   	//   	   	}

   	   	//report work on ticket
   	   	$this->reportWork($params);

   	   	//mark ticket as read
   	   	$this->insertDisplayedTicketStatus($params->get('ticket_id'));


   	   	if ($params->get('send_mail') != 'n') {
       	   	if ($this->state->config->get('useOutbox') == 'y' && ($session->getVar("userType") == 'a' || $session->getVar("userType") == 'g')) {
       	   	    $response->set('result', $this->state->lang->get('emailSavedToOutbox'));
       	   	} else {
       	   	    $response->set('result', $this->state->lang->get('emailSent'));
       	   	}
   	   	} else {
   	   	    $response->set('result', $this->state->lang->get('ticketSaved'));
   	   	}
   	   	return true;
    }

    function reportWork($params) {
        if ($this->state->config->get('workReporting') != 'y') {
            return true;
        }

        $paramsReport = $this->createParamsObject();
        $paramsReport->setField('ticket_id', $params->get('ticket_id'));
        $paramsReport->setField('billing_time', $params->get('billing_time'));
        $paramsReport->setField('work_time', $params->get('work_time'));
        $paramsReport->setField('ticket_time', $params->get('ticket_time'));
        $paramsReport->setField('note', $params->get('note'));
        return $this->callService('WorkReports', 'insertReport', $paramsReport);
    }

    /**
     * Load ticket row and store it into array
     */
    function loadTicket($params) {
        $response =& $this->getByRef('response');
   	   	$db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
        $ticket = false;
        //load ticket
        if ($params->check(array('ticket_id'))) {
            $paramsTicket = $this->createParamsObject();
            $paramsTicket->set('ticket_id', $params->get('ticket_id'));
            if ($ret = $this->callService('Tickets', 'getTicketsRows', $paramsTicket)) {
                $res = & $response->getByRef('result');
                if ($res['count'] > 0) {
                    $ticket = $res['rs']->getRows($res['md']);
                    $ticket = $ticket[0];
                }
            }
        }
        return $ticket;
    }

    function loadParentMail($params) {
        $response =& $this->getByRef('response');
   	   	$db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
        $parentMail = false;
        //load mail
        if ($params->check(array('email_id'))) {
            $paramsMail = $this->createParamsObject();
            $paramsMail->set('mail_id', $params->get('email_id'));
            if ($ret = $this->callService('Mails', 'getMailList', $paramsMail)) {
                $res = & $response->getByRef('result');
                if ($res['count'] > 0) {
                    $parentMail = $res['rs']->getRows($res['md']);
                    $parentMail = $parentMail[0];
                }
            }
        }
        return $parentMail;
    }


    function loadReplyAccount($params) {
        $response =& $this->getByRef('response');
   	   	$db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
        $mailAccount = false;

        //identify account from which will be send email
        $paramsAccount = $this->createParamsObject();
        if ($params->check(array('account_id'))) {
            $paramsAccount->set('account_id', $params->get('account_id'));
            $paramsAccount->set('password', true);
            if ($ret = $this->callService('MailAccounts', 'getMailAccount', $paramsAccount)) {
                $res = & $response->getByRef('result');
                if ($res['count'] == 0) {
                    //no mail account selected, try to request default mail account
                    $paramsAccount->unsetParam('account_id');
                    if (! $this->callService('MailAccounts', 'getDefaultMailAccount', $paramsAccount)) {
                        $this->state->log('error', $this->state->lang->get('failedToSelectMailAccount', $response->error), 'Ticket');
                        return false;
                    }
                    $res = & $response->getByRef('result');
                    if ($res['count'] == 0) {
                        $this->state->log('error', $this->state->lang->get('failedToSelectMailAccount', $response->error), 'Ticket');
                        return false;
                    }
                }
                 
                $mailAccount = $res['rs']->getRows($res['md']);
                $mailAccount = $mailAccount[0];
            } else {
                $this->state->log('error', $this->state->lang->get('failedToSelectMailAccount', $response->error), 'Ticket');
                return false;
            }
        }
        return $mailAccount;
   	}

   	function normalizeEmailAddress($emails, $accountEmail) {
   	    $emails = QUnit_Net_Mail::prepareEmail(str_replace(';', ',', $emails));
   	     
   	    $ret = '';
   	    foreach ($emails as $idx => $email) {
   	        if (strlen($newMail = QUnit_Net_Mail::getEmailAddress($emails, $idx)) && $newMail != $accountEmail) {
   	            $ret .= (strlen($ret) ? ',' : '') . $newMail;
   	        }
   	    }
   	    return $ret;
   	}

    function sendMail($params, $mailAccount, $parentTicket, $parentMail) {
        $response =& $this->getByRef('response');
   	   	$db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');


       	$domain = '@qualityunit.com';
       	if (isset($_SERVER['HTTP_HOST']) && strlen($_SERVER['HTTP_HOST'])) {
       	    $domain = '@'.$_SERVER['HTTP_HOST'];
       	}


       	$paramsSmtp = array();
       	if ($mailAccount['use_smtp'] == 'y') {
       	    $paramsSmtp['host'] = ($mailAccount['smtp_ssl'] == 'y' && $mailAccount['smtp_tls'] != 'y' ? 'tls://' : '') . $mailAccount['smtp_server'];
       	    $paramsSmtp['port'] = $mailAccount['smtp_port'];
       	    $paramsSmtp['auth'] = ($mailAccount['smtp_require_auth'] == 'y');
       	    $paramsSmtp['username'] = $mailAccount['smtp_username'];
       	    $paramsSmtp['password'] = $mailAccount['smtp_password'];
       	    $paramsSmtp['tls'] = $mailAccount['smtp_tls'] == 'y';

       	    //compute localhost hostname
       	    $url = parse_url($this->state->config->get('applicationURL'));
       	    if (strlen($url['host'])) {
       	        $paramsSmtp['localhost'] = $url['host'];
       	    } else {
       	        $paramsSmtp['localhost'] = 'localhost';
       	    }
       	}

       	$headers = array();
       	$headers['Date'] = date('j M Y H:i:s O');
       	$headers['Reply-To'] = $mailAccount['account_email'];
       	$headers['Message-ID'] = '<' . md5(rand() . $db->getDateString()) . $domain . '>';
       	if (isset($parentMail) && isset($parentMail['hdr_message_id'])) {
       	    $headers['In-Reply-To'] = $parentMail['hdr_message_id'];
       	    $headers['References'] = $headers['In-Reply-To'];
       	}

       	if (!$this->state->config->get('mailClientIdentification')) {
       	    $headers['User-Agent'] = 'www.QualityUnit.com SupportCenter';
       	} else {
       	    $headers['User-Agent'] = $this->state->config->get('mailClientIdentification');
       	}
       	$headers['Auto-Submitted'] = 'no';

       	if (isset($parentTicket['thread_id'])) {
       	    $headers['Thread-Index'] = $parentTicket['thread_id'];
       	}
       	 
       	//add missing to parameter from queue if it is empty
       	if (!$params->check(array('to')) && strlen($parentTicket['queue_id'])) {
       	    //load queue
       	    $paramsQ = $this->createParamsObject();
       	    $paramsQ->set('queue_id', $parentTicket['queue_id']);
       	    if ($queue = $this->callService('Queues', 'loadQueue', $paramsQ)) {
       	        if (strlen(trim($queue['queue_email']))) {
       	            $params->set('to', $queue['queue_email']);
       	        }
       	    }
       	}
       	 
       	$headers['To'] = $this->normalizeEmailAddress($params->get('to'), $mailAccount['account_email']);
       	$headers['Cc'] = $this->normalizeEmailAddress($params->get('cc'), $mailAccount['account_email']);
       	$headers['Bcc'] = $this->normalizeEmailAddress($params->get('bcc'), $mailAccount['account_email']);

       	$oMail = QUnit::newObj('App_Mail_Outbox');
        $oMail->state = $this->state;

        if (!$this->getSubjectTicketId($params->get('subject')) && $this->state->config->get('hideTicketIdFromSubject') != 'y') {
            $oMail->set('subject', $this->buildSubjectTicketId($parentTicket) . ' ' . $params->get('subject'));
        } else {
            $oMail->set('subject', $params->get('subject'));
        }

        //define from address
        if ($params->get('new_ticket_customer_email')) {
            $oMail->from_user_mail = $params->get('new_ticket_customer_email');
        }

        if ($params->get('contact_form') && $params->get('email')) {
            //sent as not logged in user from contact form
            $oMail->set('from', $params->get('email'));
        } else if (($session->getVar('userType') == 'a' || $session->getVar('userType') == 'g') && $mailAccount['account_email']) {
            if ($mailAccount['from_name_format'] == 'c' && strlen(trim($mailAccount['from_name']))) {
                $oMail->set('from', '"' . $mailAccount['from_name'] . '" <' . $mailAccount['account_email'] . '>');
            } else if ($mailAccount['from_name_format'] == 'a' && strlen($session->getVar('name'))) {
                $oMail->set('from', '"' . $session->getVar('name') . '" <' . $mailAccount['account_email'] . '>');
            } else {
                $oMail->set('from', $mailAccount['account_email']);
            }
        } else if ($session->getVar('userType') == 'u' && $session->getVar('username')) {
            //sent as logged in user
            $oMail->set('from', $session->getVar('username'));
        }

        $headers['Return-Path'] = $mailAccount['account_email'];


        //attach attachment to mail
        if ($params->check('attach_ids')) {
            $ids = $params->get('attach_ids');

            $objFile = QUnit::newObj('App_Service_Files');
            $objFile->state = $this->state;
            $objFile->response = $this->response;
             
            $max_send_filesize = ini_get('memory_limit');
             
            if (!strlen(trim($max_send_filesize))) {
                $max_send_filesize = '10g';
            }
            $last = strtolower($max_send_filesize{strlen($max_send_filesize)-1});
            switch($last) {
                case 'g':
                    $max_send_filesize *= 1024;
                case 'm':
                    $max_send_filesize *= 1024;
                case 'k':
                    $max_send_filesize *= 1024;
            }
             
            $current_mail_size = 0;
             
            foreach($ids as $id) {
                if (strlen($id)) {

                    $paramsFile = $this->createParamsObject();
                    $paramsFile->set('file_id', $id);
                    if ($this->callService('Files', 'getFilesList', $paramsFile)) {
                        $res = & $response->getByRef('result');
                        if ($res['count'] > 0) {
                            $file = $res['rs']->getRows($res['md']);
                            $file = $file[0];
                            $attachment = array();
                            $attachment['filename'] = $file['filename'];
                            $attachment['filetype'] = $file['filetype'];

                            $current_mail_size += $file['filesize'];
                            if ($this->state->config->getIniSize('memory_limit') > 0) {
                                if ($this->state->config->getIniSize('memory_limit') < 2 * $current_mail_size) {
                                    $response->set('error', $this->state->lang->get('sizeOfMailTooBig'));
                                    return false;
                                }
                            }

                            $attachment['content'] = $objFile->getFileContent($id);
                            $oMail->addAttachment($attachment);
                        }
                    }
                }
            }
        }


        $templates = QUnit::newObj('App_Service_MailTemplates');
        $templates->state = $this->state;
        $templates->response = $response;
        $template = $templates->loadTemplate('CoverMail', $parentTicket['queue_id']);

        if ($this->isHTMLMail($params)) {
            $params->set('template_text', $template['body_html']);
            $oMail->set('txt_mail', false);
            if (strlen($params->get('body_html'))) {
                $params->set('body', $params->get('body_html'));
            }
        } else {
            $params->set('template_text', $template['body_text']);
            $oMail->set('txt_mail', 1);
        }


        //load template variables: ${subject_ticket_id}, ${ticket_subject}${ticket_url}${ticket_link}
        $params->set('subject_ticket_id', $parentTicket['subject_ticket_id']);
        $params->set('ticket_subject', $parentTicket['first_subject']);
        $params->set('ticket_url', $this->state->config->get('applicationURL') . 'client/index.php#tid=' . $parentTicket['subject_ticket_id']);
        $params->set('ticket_link', '<a href="' . $params->get('ticket_url') . '">' . $parentTicket['subject_ticket_id'] . '</a>');


        $sendMail = QUnit::newObj('App_Service_SendMail');
        $oMail->set('body', $sendMail->loadBodyFromTemplate($params));

        if ($mailAccount['use_smtp'] == 'y') {
            $recipients = '';
            if (strlen($headers['To'])) {
                $recipients = $headers['To'];
            }
            if (strlen($headers['Cc'])) {
                $recipients .= (strlen($recipients) ? ',':'') . $headers['Cc'];
            }
            if (strlen($headers['Bcc'])) {
                $recipients .= (strlen($recipients) ? ',':'') . $headers['Bcc'];
                unset($headers['Bcc']);
            }
             
            $oMail->set('recipients', $recipients);
        } else {
            $oMail->set('recipients', $headers['To']);
        }
        $oMail->set('headers', $headers);
        $oMail->set('ticket_id', $parentTicket['ticket_id']);

        $method = $mailAccount['use_smtp'] == 'y' ? 'smtp' : 'mail';
        //store mail to system
        if (!$this->storeSentMail($params, $mailAccount, $parentTicket, $parentMail, $oMail)) {
            $response->set('error', $this->state->lang->get('failedToSaveTicket') . ': ' . $response->get('error'));
            $this->state->log('error', $response->error, 'Ticket');
            return false;
        }

        if ($this->isDemoMode()) {
            return true;
        }

        if ($params->get('send_mail') != 'n') {
            //send email just if you're agent or admin (user has no right to send email)
            if (!$params->get('contact_form') &&
            ($session->getVar('userType') == 'a' || $session->getVar('userType') == 'g') &&
            $params->get('is_comment') != 'y') {
//            !($params->get('is_comment') == 'y' && !strlen($oMail->get('recipients')))) {
                if(!$oMail->send($method, $paramsSmtp, $this->state->config->get('useOutbox') != 'y')) {
                    $response->set('error', $this->state->lang->get('mailNotSent') . $oMail->get('error'));
                    return false;
                }
            }
        }
        return true;
    }

    function isHTMLMail($params) {
        return !strlen($params->get('isPlainText'));
    }

    function storeSentMail($params, $mailAccount, $parentTicket, $parentMail, &$oMail) {
        $response =& $this->getByRef('response');
   	   	$db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
         
        $paramsMail = $this->createParamsObject();
        $paramsMail->setField('mail_id', md5(uniqid(rand(), true)));
        $oMail->set('mail_id', $paramsMail->getField('mail_id'));
        $paramsMail->setField('ticket_id', $parentTicket['ticket_id']);
        $paramsMail->setField('account_id', isset($mailAccount['account_id']) ? $mailAccount['account_id'] : $parentMail['account_id']);
        $paramsMail->setField('parent_mail_id', $parentMail['mail_id']);
        $headers = $oMail->get('headers');
        $paramsMail->setField('hdr_message_id', $headers['Message-ID']);
        $paramsMail->setField('unique_msg_id', $headers['Message-ID']);
        $paramsMail->setField('subject', $params->get('subject'));
        $paramsMail->setField('is_comment', $params->get('is_comment'));
        $paramsMail->setField('headers', serialize($headers));
        if ($this->isHTMLMail($params)) {
            $paramsMail->setField('body_html', $oMail->get('body'));
            $txtconverter = QUnit::newObj('QUnit_Txt_Html2Text');
            $txtconverter->set_html($oMail->get('body'));
            $paramsMail->setField('body', $txtconverter->get_text());
        } else {
            $paramsMail->setField('body_html', '');
            $paramsMail->setField('body', str_replace('<', '&lt;', $oMail->get('body')));
        }
        $paramsMail->setField('is_answered', 'n');

        if (!$this->callService('Mails', 'insertMail', $paramsMail)) {
            $this->state->log('error', 'Failed to insert mail into database with error: ' . $response->error, 'Ticket');
            return false;
        }

        //attach attachment to mail
        if ($params->check('attach_ids')) {
            $ids = $params->get('attach_ids');
            foreach($ids as $id) {
                $mailAttachmentParams = $this->createParamsObject();
                $mailAttachmentParams->setField('mail_id', $paramsMail->getField('mail_id'));
                $mailAttachmentParams->setField('file_id', $id);
                if (!$this->callService('Attachments', 'insertMailAttachment', $mailAttachmentParams)) {
                    $this->state->log('error', 'Failed to attach file to mail with error: ' . $response->error, 'Ticket');
                    return false;
                }

            }
        }

        //update ticket fields from request
        $paramsTicket = $this->createParamsObject();
        $paramsTicket->set('ticket_id', $parentTicket['ticket_id']);
         
        $paramsTicket->setField('status', $params->get('status'));

        if ($session->getVar('userType') == 'a' || $session->getVar('userType') == 'g') {
            if (strlen($params->get('queue_id'))) {
                $paramsTicket->setField('queue_id', $params->get('queue_id'));
            }
            if (strlen($params->get('priority'))) {
                $paramsTicket->setField('priority', $params->get('priority'));
            }
            $agentId = $params->get('agent_owner_id');
            if(strlen($agentId)) {
                if($agentId == "none") {
                    $paramsTicket->setField('agent_owner_id', "");
                } else {
                    $paramsTicket->setField('agent_owner_id', $agentId);
                }
            }
        }
        $paramsTicket->setField('last_update', $db->getDateString());
        $paramsTicket->set('mail_body', $paramsMail->get('body'));
        $paramsTicket->set('contact_form', $params->get('contact_form'));
        if (!$this->callService('Tickets', 'updateTicket', $paramsTicket)) {
            $this->state->log('error', $this->state->lang->get('failedUpdateTicket'), 'Ticket');
            return false;
        }

        //assign to users
        if (!$this->assignMailReplyToUsers($oMail, $paramsMail->get('mail_id'), $parentTicket['ticket_id'])) {
            return false;
        }

        //if it is submitted from contact form, send notification to user from queue
        if ($params->get('contact_form')) {
            if (!$this->callService('Queues', 'notifyUserAboutNewTicket', $paramsMail)) {
                $this->state->log('error', 'Failed to notify user about new ticket: ' . $response->error, 'MailParser');
                return false;
            }

            //forward mail from contact form to relevant agents
            $forwarder = QUnit::newObj('App_Service_MailGateway');
            $forwarder->state = $this->state;
            $forwarder->response = $response;
            $forwarder->forwardMail($parentTicket['ticket_id'], $paramsMail->get('mail_id'));
        }
        return true;
    }

    /**
     * assign email to users from email
     * if user doesn't exist, create it
     */
    function assignMailReplyToUsers($oMail, $mail_id, $ticket_id) {
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');

        $arrUsers = array();

        //zisti aky userovia su v maily a s akymi rolami



        if ($session->getVar('userType') != 'u' && strlen($oMail->from_user_mail)) {
            $arrUsers[] = array('email'=> $oMail->from_user_mail,
                            'name' => '',
                            'role' => 'from_user');
        } else if (strlen($session->getVar('username'))) {
            //TODO here should be assigned customer in case it is new ticket submitted by agent
            $arrUsers[] = array('email'=> $session->getVar('username'),
							'name' => $session->getVar('name'),
							'role' => 'from_user');
        }
         
        $from = QUnit_Net_Mail::prepareEmail($oMail->get('from'));
        foreach ($from as $idx => $to_mail) {
            if (strlen(QUnit_Net_Mail::getEmailAddress($from, $idx))) {
                $arrUsers[] = array('email'=> QUnit_Net_Mail::getEmailAddress($from, $idx),
								'name' => QUnit_Net_Mail::getPersonalName($from, $idx),
								'role' => 'from');
            }
        }

        $headers = $oMail->get('headers');

        if (isset($headers['cc'])) {
            $cc = QUnit_Net_Mail::prepareEmail($headers['cc']);
            foreach ($cc as $idx => $to_mail) {
                if (strlen(QUnit_Net_Mail::getEmailAddress($cc, $idx))) {
                    $arrUsers[] = array('email'=> QUnit_Net_Mail::getEmailAddress($cc, $idx),
									'name' => QUnit_Net_Mail::getPersonalName($cc, $idx),
									'role' => 'cc');
                }
            }
        }

        if (isset($headers['bcc'])) {
            $bcc = QUnit_Net_Mail::prepareEmail($headers['bcc']);
            foreach ($bcc as $idx => $to_mail) {
                if (strlen(QUnit_Net_Mail::getEmailAddress($bcc, $idx))) {
                    $arrUsers[] = array('email'=> QUnit_Net_Mail::getEmailAddress($bcc, $idx),
									'name' => QUnit_Net_Mail::getPersonalName($bcc, $idx),
									'role' => 'bcc');
                }
            }
        }

        $to = QUnit_Net_Mail::prepareEmail($oMail->get('recipients'));
        foreach ($to as $idx => $to_mail) {
            if (strlen(QUnit_Net_Mail::getEmailAddress($to, $idx))) {
                $arrUsers[] = array('email'=> QUnit_Net_Mail::getEmailAddress($to, $idx),
								'name' => QUnit_Net_Mail::getPersonalName($to, $idx),
								'role' => 'to');
            }
        }

        //pre kazdeho usera vytvor priradenie
        $arrAssignedUsers = array();
        foreach ($arrUsers as $user) {
            if (!strlen(trim($user['email']))) continue;
            //najdi usera
            $params = $this->createParamsObject();
            $params->set('email', $user['email']);
            if (!$this->callService('Users', 'getUserByEmail', $params)) {
                $this->state->log('error', 'Failed request if user ' . $user['email'] . ' exist with error: ' . $response->error,'MailParser');
                return false;
            }
            if($response->getResultVar('count') == 0) {
                //ak neexistuje, vytvor ho
                $paramsUser = $this->createParamsObject();
                $paramsUser->setField('name', $user['name']);
                $paramsUser->setField('email', $user['email']);

                if (!$this->callService('Users', 'insertUser', $paramsUser)) {
                    $this->state->log('error', 'Failed to create new user ' . $user['email'] . ' with error: ' . $response->error,'MailParser');
                    return false;
                } else {
                    $user['user_id'] = $paramsUser->get('user_id');
                }
            } else {
                //nasiel usera
                $result = $response->result;
                $rows = $result['rs']->getRows($result['md']);
                $user['user_id'] = $rows[0]['user_id'];
            }
             
            //init array
            if (!isset($arrAssignedUsers[$user['role']])) $arrAssignedUsers[$user['role']] = array();
             
            //prirad usera mailu
            if (!in_array($user['user_id'], $arrAssignedUsers[$user['role']])) {
                $objMail = QUnit::newObj('App_Service_Mails');
                $objMail->state = $this->state;
                $objMail->response = $response;
                if ($objMail->assignUserToMail($user['user_id'], $mail_id, $user['role'])) {
                    $arrAssignedUsers[$user['role']][] = $user['user_id'];
                } else {
                    $this->state->log('error', 'Failed to create User to Mail assignment with error: '. $response->error,'MailParser');
                }
            }
        }

        $users = array();
        foreach ($arrAssignedUsers as $arrAU) {
            $users = array_unique(array_merge($arrAU, $users));
        }
        $this->state->log('info', $this->state->lang->get('mailReplySent', QUnit_Net_Mail::getEmailAddress($from), $oMail->get('subject')), 'Ticket', $users);
        return true;
    }

    function removePrefixFromSubject(&$subject) {
        $arrPrefixes = $this->state->config->get('subjectPrefixes');
        $arrPrefixes = explode(',', $arrPrefixes);
        if (is_array($arrPrefixes) && count($arrPrefixes) > 0) {
            foreach ($arrPrefixes as $prefix) {
                $old_subject = $subject;
                $subject = preg_replace('/^\s*?' . preg_quote(trim($prefix)) . '\s:*?/i', '', $subject);
                if ($old_subject != $subject) return true;
            }
        }
        return false;
    }

    function cleanSubjectPrefixes($subject) {
        while ($this->removePrefixFromSubject($subject)) {
        }
        return $subject;
    }

    function loadMail($params) {
        $response =& $this->getByRef('response');
        $objMails = QUnit::newObj('App_Service_Mails');
        $objMails->state = $this->state;
        $objMails->response = $response;
        return $objMails->loadMail($params);
    }

    function moveMailToNewTicket($params) {
        $session = QUnit::newObj('QUnit_Session');
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');

        if ($ticket = $this->loadTicket($params)) {
            if ($mail = $this->loadMail($params)) {

                $paramsTicket = $this->createParamsObject();
                $paramsTicket->set('mail_body', $mail['body']);

                $paramsTicket->setField('ticket_id', 0);
                $paramsTicket->setField('queue_id', $ticket['queue_id']);
                $paramsTicket->setField('priority', $ticket['priority']);
                $paramsTicket->setField('customer_id', $ticket['customer_id']);
                $paramsTicket->setField('agent_owner_id', $ticket['agent_owner_id']);
                $paramsTicket->setField('first_subject', $mail['subject']);

                //create new ticket
                if ($this->insertTicket($paramsTicket)) {
                    $params->set('ticket_id', $paramsTicket->get('ticket_id'));

                    //move mail to new ticket
                    $paramsMail = $this->createParamsObject();
                    $paramsMail->set('mail_id', $mail['mail_id']);
                    $paramsMail->setField('ticket_id', $paramsTicket->get('ticket_id'));
                    If ($this->callService('Mails', 'updateMail', $paramsMail)) {
                        $this->updateMailsCount($ticket['ticket_id']);
                        $response->set('result', $paramsTicket->get('subject_ticket_id'));
                        return true;
                    } else {
                        $this->state->log('error', 'Failed to update Mail', 'Ticket');
                    }
                } else {
                    $this->state->log('error', 'Failed to insert new Ticket', 'Ticket');
                }
            }
        }

        return false;
    }

    function getCustomFieldValues($params) {
   	   	$db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');

        $where = '';

        if ($session->getVar('userType') == 'u') {
            $where .= (strlen($where) ? " AND " : '') . "cf.user_access='u'";
        }

        if ($id = $params->get('ticket_id')) {
            $ticket = $this->loadTicket($params);
            $user_id = $ticket['customer_id'];
        }

        if (!strlen($user_id)) {
            $user_id = $session->getVar('userId');
        }
        
       $objUsers = QUnit::newObj('App_Service_Users');
        $objUsers->state = $this->state;
        $objUsers->response = $response;
        $group_id = $objUsers->getUserGroupByUserId($user_id);
        

        $params->set('columns', "*");
        $params->set('from', "custom_fields cf
							  LEFT JOIN custom_values cv ON 
								(cf.field_id=cv.field_id AND
								 (
									(cf.related_to='t' AND cv.ticket_id = '" . $db->escapeString($params->get('ticket_id')) . "') OR 
								 	(cf.related_to='u' AND cv.user_id='" . $db->escapeString($user_id) . "') OR 
								 	(cf.related_to='g' AND cv.groupid='" . $db->escapeString($group_id) . "')
								 )
								)");
        $params->set('where', $where);
        $params->set('order', 'cf.order_value, cf.field_title');
        $params->set('table', 'custom_fields');
        return $this->callService('SqlTable', 'select', $params);
    }
}
?>
