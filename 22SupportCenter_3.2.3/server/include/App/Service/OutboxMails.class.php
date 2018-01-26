<?php
/**
 *   Handler class for Outbox mails
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("QUnit_Rpc_Service");
QUnit::includeClass("App_Mail_Outbox");

class App_Service_OutboxMails extends QUnit_Rpc_Service {

    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
            case 'getMailsList':
            case 'deleteMail':
            case 'retryMail':
                return $this->callService('Users', 'authenticateAgent', $params);
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * Create outbox mail
     */
    function insertMail($params) {
        global $_SERVER;

        $response =& $this->getByRef('response');
        $db = $this->state->get('db');


        if (!strlen($params->get('recipients'))) {
            $response->set('error', $this->state->lang->get('recipientsEmpty'));
            return false;
        }

        $params->set('table', 'outbox');

        $params->setField('out_id', 0);

        if (!strlen($params->getField('method'))) {
            $params->setField('method', 'mail');
        }
        if (!strlen($params->getField('created'))) {
            $params->setField('created', $db->getDateString());
        }
        if (!strlen($params->getField('scheduled'))) {
            $params->setField('scheduled', $db->getDateString());
        }

        $params->setField('retry_nr', 0);
        $params->setField('error_msg', '');
        $params->setField('status', 'p');

        if ($this->callService('SqlTable', 'insert', $params)) {
            $params->setField('out_id', $this->response->result);
            $params->set('table', 'outbox_contents');
            $iteration = 0;

            while (strlen($params->get('mail_object'))) {
                $params->setField('content', substr($params->get('mail_object'), 0, 500000));
                $params->setField('content_nr', $iteration);
                if (!$this->callService('SqlTable', 'insert', $params)) {
                    return false;
                }
                $params->setField('mail_object', substr($params->get('mail_object'), 500000));
                $iteration++;
            }

            if (strlen($params->get('mail_id'))) {
                $params1 = $this->createParamsObject();
                $params1->setField('delivery_status', $params->get('status'));
                $params1->set('mail_id', $params->get('mail_id'));
                return $this->callService('Mails', 'updateMail', $params1);
            }

            return true;
        }
        return false;
    }

    function sendOutBoxMails() {
        $db = $this->state->get('db');
        while($mail = $this->findOutBoxMail()) {
            //init error message
            $error = 'failed to send email';
            	
            //set status sending
            $this->setStatus($mail, 's');
            	
            //load object data
            $rawData = $this->loadMailObjectContent($mail['out_id']);
            	
            if (strlen($rawData)) {
                //unserialize mail data and try to send
                $oMail = unserialize($rawData);
                $oMail->state = $this->state;
                $rawData = null;
                if (is_object($oMail)) {
                    if ($oMail->send($mail['method'], unserialize($mail['params']), true)) {
                        	
                        //delete mail from outbox
                        $paramsDelete = $this->createParamsObject();
                        $paramsDelete->set('out_id', $mail['out_id']);
                        if ($this->deleteMail($paramsDelete)) {
                            if ($mail['mail_id']) {
                                //update mail status in mails
                                $params = $this->createParamsObject();
                                $params->setField('delivery_status', 'd');
                                $params->setField('delivery_date', $db->getDateString());
                                $params->set('mail_id', $mail['mail_id']);
                                if (!$this->callService('Mails', 'updateMail', $params)) {
                                    return false;
                                }
                            }
                        }
                        continue;
                    } else {
                        $error = $oMail->get('error');
                    }
                } else {
                    $error = "Failed to unserialize outbox mail";
                }
            }
            	
            //schedule outbox mail in X minutes
            $params = $this->createParamsObject();
            $params->set('out_id', $mail['out_id']);
            $params->setField('status', 'p');
            $params->setField('retry_nr', $mail['retry_nr']+1);
            $params->setField('last_retry', $db->getDateString());
            $params->setField('error_msg', $error);
            	
            $delay = $this->state->config->get('retryAfterDelay');
            if (!strlen($delay) || !is_numeric($delay)) $delay = 10;
            $delay = $delay * 60;
            	
            $params->setField('scheduled', $db->getDateString(time() + $delay));
            if (!$this->updateMail($params)) {
                return false;
            }
            //update delivery_status in mail as error
            if (strlen($mail['mail_id'])) {
                $params = $this->createParamsObject();
                $params->setField('delivery_status', 'e');
                $params->set('mail_id', $mail['mail_id']);
                if (!$this->callService('Mails', 'updateMail', $params)) {
                    return false;
                }
            }
        }
        return true;
    }

    function findOutBoxMail() {
        $response =& $this->getByRef('response');
   	   	$db = $this->state->get('db');

   	   	$maxRetries = $this->state->config->get('maxRetryCount');
   	   	if (!strlen($maxRetries) || !is_numeric($maxRetries)) $maxRetries = 10;

        $params = $this->createParamsObject();
        $user = false;
        //load mail
        $columns = "*";
        $from = "outbox";
        $where = "status='p'";
        $where .= " AND scheduled < '" . $db->getDateString() . "'";
        $where .= " AND retry_nr <= " . $maxRetries;
        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('limit', 1);
        $params->set('order', 'scheduled');
        $params->set('where', $where);
        $params->set('table', 'outbox');
        if ($this->callService('SqlTable', 'select', $params)) {
            $res = & $response->getByRef('result');
            if ($res['count'] > 0) {
                $mail = $res['rs']->getRows($res['md']);
                return $mail[0];
            }
        } else {
            $this->state->log('error', $response->get('error'), 'Outbox');
        }
        return false;
    }

    function updateMail($params) {
        $response =& $this->getByRef('response');
        $db =& $this->state->getByRef('db');
         
        if(!$params->check(array('out_id'))) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }
         
        $params->set('table', 'outbox');
        $params->set('where', "out_id = '".$db->escapeString($params->get('out_id'))."'");
        return $this->callService('SqlTable', 'update', $params);
    }

    function deleteMail($params) {
        $response =& $this->getByRef('response');
        $db =& $this->state->getByRef('db');
         
        $ids = explode('|',$params->get('out_id'));
         
        $where_ids = '';
        foreach ($ids as $id) {
            if (strlen(trim($id))) {
                $where_ids .= (strlen($where_ids) ? ', ': '');
                $where_ids .= "'" . $db->escapeString(trim($id)) . "'";
            }
        }
         
        if(!$params->check(array('out_id')) || !strlen($where_ids)) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }

        //updatni status mailov
        $sql = "UPDATE mails m, outbox o SET m.delivery_status='n' WHERE o.out_id IN (" . $where_ids . ") AND o.mail_id = m.mail_id";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $sth->get('errorMessage'));
            $this->state->log('error', $response->get('error'), 'Outbox');
            return false;
        }
         
         
        $params->set('table', 'outbox_contents');
        $params->set('where', "out_id IN (".$where_ids.")");
        if ($this->callService('SqlTable', 'delete', $params)) {
            $params->set('table', 'outbox');
            return $this->callService('SqlTable', 'delete', $params);
        }
        return false;
    }


    function retryMail($params) {
        $response =& $this->getByRef('response');
        $db =& $this->state->getByRef('db');

        $executor = QUnit::newObj('App_Service_ExecutionController');
        $executor->setByRef('state', $state);
        $executor->setByRef('response', $response);
        $executor->state = $this->state;
        $executor->setRunning(false);
         
         
        $ids = explode('|',$params->get('out_id'));
         
        $where_ids = '';
        foreach ($ids as $id) {
            if (strlen(trim($id))) {
                $where_ids .= (strlen($where_ids) ? ', ': '');
                $where_ids .= "'" . $db->escapeString(trim($id)) . "'";
            }
        }
         
        if(!$params->check(array('out_id')) || !strlen($where_ids)) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }

        //update status of mails
        $sql = "UPDATE mails m, outbox o SET m.delivery_status='p' WHERE o.out_id IN (" . $where_ids . ") AND o.mail_id = m.mail_id";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $sth->get('errorMessage'));
            $this->state->log('error', $response->get('error'), 'Outbox');
            return false;
        }

        $params->setField('status', 'p');
        $params->setField('retry_nr', 0);
        $params->setField('error_msg', '');
        $params->setField('scheduled', $db->getDateString());

        $params->set('table', 'outbox');
        $params->set('where', "out_id IN (".$where_ids.")");
        return $this->callService('SqlTable', 'update', $params);
    }


    function setStatus($mail, $newStatus) {
        $params = $this->createParamsObject();
        $params->set('out_id', $mail['out_id']);
        $params->setField('status', $newStatus);
        if ($this->updateMail($params)) {
            if (strlen($mail['mail_id'])) {
                $params = $this->createParamsObject();
                $params->setField('delivery_status', $newStatus);
                $params->set('mail_id', $mail['mail_id']);
                return $this->callService('Mails', 'updateMail', $params);
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    function loadMailObjectContent($out_id) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $sql = 'SELECT content FROM outbox_contents
		WHERE out_id = \'' . $db->escapeString($out_id) . "'
		ORDER BY content_nr";
         
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'select');
        if(QUnit_Object::isError($sth)) {
            $this->state->log('error', $this->state->lang->get('dbError').$sth->get('errorMessage'), 'Attachment');
            return false;
        }

        $value = '';
        while ($row = $sth->fetchRow()) {
            $value .= $row[0];
        }
        return $value;
    }


    /*
     * Return list of log entries defined by input parameters
     */
    function getMailsList($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $columns = "o.out_id, o.user_id, u.name, u.email, o.ticket_id, t.subject_ticket_id, o.recipients, o.subject,
					o.scheduled, o.created, o.retry_nr, o.error_msg, o.last_retry, 
					o.status";
        $from = "outbox o
				LEFT JOIN users u ON (u.user_id = o.user_id)
				LEFT JOIN tickets t ON (t.ticket_id = o.ticket_id)";
        $where = "1";

        //rights
        $session = QUnit::newObj('QUnit_Session');
        switch ($session->getVar('userType')) {
            case 'a':
                break;
            case 'g':
                $from .= " LEFT JOIN queues q ON (t.queue_id = q.queue_id)";
                $where .= " AND (o.ticket_id IS NULL OR
							t.agent_owner_id='" . $db->escapeString($session->getVar('userId')) . "' OR
							q.public='y' OR 
							q.queue_id IN (
								SELECT queue_id 
								FROM queue_agents 
								WHERE user_id='" . $db->escapeString($session->getVar('userId')) . "'))"; 
                break;
            default:
                $params->set('user_id', 'nouser');
                break;
        }

        if($id = $params->get('user_id')) {
            $where .= " AND o.user_id = '".$db->escapeString($id)."'";
        }

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'outbox');
        return $this->callService('SqlTable', 'select', $params);
    }

}
?>
