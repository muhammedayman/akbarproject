<?php
/**
*   Handler class for Mails
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Mails extends QUnit_Rpc_Service {

    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
			case 'getMailBody':
			case 'getMailHeaders':
				return $this->callService('Users', 'authenticate', $params);
				break;
        	default:
                return false;
                break;
        }
    }

    function computeSpamRatio() {
    	return 0;
    }
    
    function insertMail(&$params) {
    	$db = $this->state->get('db'); 
        $params->set('table', 'mails');
		$response =& $this->getByRef('response');
   		$session = QUnit::newObj('QUnit_Session');
		
        if (!strlen($params->get('unique_msg_id'))) {
        	$params->setField('unique_msg_id', md5(rand() . $db->getDateString()));
        }
        if (!strlen($params->get('hdr_message_id'))) {
        	$params->setField('hdr_message_id', $params->get('unique_msg_id'));
        }
        if ($params->getField('is_comment') != 'y') {
        	$params->setField('is_comment', 'n');
        }
        if (!strlen($params->get('delivery_date'))) {
        	$params->setField('delivery_date', $db->getDateString());
        }
        if (!strlen($params->get('delivery_status'))) {
        	$params->setField('delivery_status', 'd');
        }
        
       if (!strlen($params->get('body_html'))) {
        	$params->setField('body_html', 
        	str_replace("\n", '<br />', $params->get('body')));
        }
        
        if (!strlen($params->getField('mail_id'))) {
        	$params->setField('mail_id', md5($params->get('from') . $params->get('unique_msg_id') . rand(0, 100000)));
        }
        $params->setField('created', $db->getDateString());
        $params->setField('created_date', $db->getDateString());
        $params->setField('is_answered', 'n');
        
        if (!strlen($params->getField('spam_ratio'))) {
        	$params->setField('spam_ratio', $this->computeSpamRatio());
        }
        
        if ($ret = $this->callService('SqlTable', 'insert', $params)) {
        	
        	if (strlen($params->getField('parent_mail_id'))) {
				//update is_answered status of parent mail
				$paramsParentMail = $this->createParamsObject();
				$paramsParentMail->set('mail_id', $params->getField('parent_mail_id'));
				$paramsParentMail->setField('is_answered', 'y');
				if (!$this->callService('Mails', 'updateMail', $paramsParentMail)) {
					$this->state->log('error', 'Failed to update parent with error: ' . $response->error, 'Ticket');
					return false;
				}
        	}

        	$tObj = QUnit::newObj('App_Service_Tickets');
        	$tObj->response = $response;
        	$tObj->state = $this->state;
        	$tObj->updateMailsCount($params->get('ticket_id'));
        	
        	
        	return true;

        } else {
        	return $ret;
        }
    }

    
   /**
     * Get mail by mail_id or by unique_msg_id
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
   function getMailBody($params) {
   		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
   	
    	if(!$params->check(array('mail_id'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
   		
    	if(!$this->hasUserRight($params->get('mail_id'))) {
    		$response->set('error', $this->state->lang->get('noAccessRights'));
    		return false;
    	}
    	
   		
       	$columns = "mail_id, body, body_html";
   		
        $from = "mails";
        if (strlen($params->get('mail_id'))) {
        	$where = "mail_id = '".$db->escapeString($params->get('mail_id'))."'";
        }

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'mails');
        return $this->callService('SqlTable', 'select', $params);
   }

   /**
    * Get mail headers by mail_id
    *
    * @param QUnit_Rpc_Params $params
    * @return unknown
    */
   function getMailHeaders($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		
		if(!$params->check(array('mail_id'))) {
			$response->set('error', $this->state->lang->get('noIdProvided'));
			return false;
		}
		 
		if(!$this->hasUserRight($params->get('mail_id'))) {
			$response->set('error', $this->state->lang->get('noAccessRights'));
			return false;
		}
			
		$columns = "mail_id, headers";
			
		$from = "mails";
		if (strlen($params->get('mail_id'))) {
			$where = "mail_id = '".$db->escapeString($params->get('mail_id'))."'";
		}

		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('where', $where);
	    $params->set('table', 'mails');
	    if ($this->callService('SqlTable', 'select', $params)) {
	    	$res = & $response->getByRef('result');
	    	if ($res['count'] == 0) {
	    		$response->set('error', $this->state->lang->get('failedToSelectMail', $response->error));
	    		$this->state->log('error', $this->state->lang->get('failedToSelectMail', $response->error), 'Mail');
	    		return false;
	    	}
	    	 
	    	$mail = $res['rs']->getRows($res['md']);
	    	$mail = $mail[0];
	    	$headers = unserialize($mail['headers']);
	    	QUnit::includeClass('QUnit_Net_Mail');
	    	if (isset($headers['envelope-to:'])) {
	    		$res = '';
	    		foreach($headers['envelope-to:'] as $idx => $hdr) {
	    			$res .= (strlen($res) ? ',' : '') . QUnit_Net_Mail::getEmailAddress($headers['envelope-to:'], $idx);
	    		}
	    		$headers['envelope-to:'] = $res;
	    	}
	    	if (isset($headers['from:'])) {
	    		$res = '';
	    		foreach($headers['from:'] as $idx => $hdr) {
	    			$res .= (strlen($res) ? ',' : '') . QUnit_Net_Mail::getEmailAddress($headers['from:'], $idx);
	    		}
	    		$headers['from:'] = $res;
	    	}
	    	if (isset($headers['reply-to:'])) {
	    		$res = '';
	    		foreach($headers['reply-to:'] as $idx => $hdr) {
	    			$res .= (strlen($res) ? ',' : '') . QUnit_Net_Mail::getEmailAddress($headers['reply-to:'], $idx);
	    		}
	    		$headers['reply-to:'] = $res;
	    	}
	    	if (isset($headers['to:'])) {
	    		 $res = '';
	    		 foreach($headers['to:'] as $idx => $hdr) {
	    		 	$res .= (strlen($res) ? ',' : '') . QUnit_Net_Mail::getEmailAddress($headers['to:'], $idx);
	    		 }
	    		 $headers['to:'] = $res;
			}
	    	if (isset($headers['cc:'])) {
	    		 $res = '';
	    		 foreach($headers['cc:'] as $idx => $hdr) {
	    		 	$res .= (strlen($res) ? ',' : '') . QUnit_Net_Mail::getEmailAddress($headers['cc:'], $idx);
	    		 }
	    		 $headers['cc:'] = $res;
			}
	    	
	    	$response->set('result', $headers);
	    	return true;
	    } else {
	    	return false;
	    }
    }
    
    
    //check if logged in user has rights to access mail
    function hasUserRight($mail_id) {
   		$session = QUnit::newObj('QUnit_Session');
    	switch ($session->getVar('userType')) {
    		case 'a':
    			return true;
    		case 'g':
    			//TODO check rights of agent to mail
    			return true;
    		default:
    			//TODO check rights of any user to mail
				return true;
    	}
    }
    
    /**
     * Get mail by mail_id or by unique_msg_id
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function getMailList($params) {
   		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
   		$session = QUnit::newObj('QUnit_Session');
		
   		$columns = "*";
   		
        $from = "mails";
        $where = "1";
        
        if ($session->getVar('userType') == 'u') {
        	$where .= " AND is_comment = 'n'";
        }
        
        if (strlen($params->get('mail_id'))) {
        	$where .= " AND mail_id = '".$db->escapeString($params->get('mail_id'))."'";
        }

        if (strlen($params->get('ticket_id'))) {
        	$where .= " AND ticket_id = '".$db->escapeString($params->get('ticket_id'))."'";
        }
        
        if (strlen($params->get('unique_msg_id'))) {
        	$where .= " AND unique_msg_id = BINARY '".$db->escapeString($params->get('unique_msg_id'))."'";
        }

        if (strlen($params->get('account_id'))) {
        	$where .= " AND account_id = '".$db->escapeString($params->get('account_id'))."'";
        }
        
        if (strlen($params->get('hdr_message_id'))) {
        	$where .= " AND hdr_message_id = BINARY '".$db->escapeString($params->get('hdr_message_id'))."'";
        }
        
        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'mails');
        return $this->callService('SqlTable', 'select', $params);
    }
    
    function getFirstMessageId($params) {
		$response =& $this->getByRef('response');
    	if (!$this->callService('Mails', 'getMailList', $params)) {
			$this->state->log('error', 'Failed to request mail_id with error: ' . $response->error, 'MailParser');
			return false;
		} else {
			$res = & $response->getByRef('result');
			if ($res['count'] == 0) {
				return false;
			} else {
				$rows = $res['rs']->getRows($res['md']);
            	return $rows[0]['mail_id'];;
			}
		}
    }
    
    function updateMail($params) {
    	$response =& $this->getByRef('response');
    	$db =& $this->state->getByRef('db');
    	
    	if(!$params->check(array('mail_id'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}

    	$params->set('table', 'mails');
    	$params->set('where', "mail_id = '".$db->escapeString($params->get('mail_id'))."'");
    	return $this->callService('SqlTable', 'update', $params);
    }
    
   
	/**
	 * Create User-Mail assignment
	 *
	 * @param unknown_type $user_id
	 * @param unknown_type $mail_id
	 * @param unknown_type $role
	 */
	function assignUserToMail($user_id, $mail_id, $role) {
		if (!strlen($user_id)) {
			return true;
		}
		$params = $this->createParamsObject();
		$params->setField('user_id', $user_id);
		$params->setField('mail_id', $mail_id);
		$params->setField('mail_role', $role);
		$params->set('table', 'mail_users');
		return $this->callService('SqlTable', 'insert', $params);
	}
    
    /**
     * Load mail row and store it into array 
     */
    function loadMail($params) {
    	$response =& $this->getByRef('response');
   	   	$db = $this->state->get('db'); 
        $session = QUnit::newObj('QUnit_Session');
        $ticket = false;
    	//load mail
    	if ($params->check(array('mail_id'))) {
    		$paramsMail = $this->createParamsObject(); 
    		$paramsMail->set('mail_id', $params->get('mail_id'));
    		if ($ret = $this->callService('Mails', 'getMailList', $paramsMail)) {
    			$res = & $response->getByRef('result');
    			if ($res['count'] > 0) {
					$mail = $res['rs']->getRows($res['md']);
    				$mail = $mail[0];
    			}
    		}
    	}
    	return $mail;
    }
    
    /**
     * Load mail users rows and store it into array 
     */
    function loadMailUsers($params) {
    	$response =& $this->getByRef('response');
   	   	$db = $this->state->get('db'); 
        $session = QUnit::newObj('QUnit_Session');
        $ticket = false;
    	//load mail
    	if ($params->check(array('mail_id'))) {
    		$paramsMail = $this->createParamsObject(); 
    		$paramsMail->set('mail_id', $params->get('mail_id'));
    		if ($ret = $this->callService('Mails', 'getMailUsersList', $paramsMail)) {
    			$res = & $response->getByRef('result');
    			if ($res['count'] > 0) {
					$mail = $res['rs']->getRows($res['md']);
    			}
    		}
    	}
    	return $mail;
    }
    /**
     * Get mail by mail_id or by unique_msg_id
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function getMailUsersList($params) {
   		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
   		
		$from = 'mail_users INNER JOIN users ON (mail_users.user_id = users.user_id)';
		
   		$where = '1';
        if (strlen($params->get('mail_id'))) {
        	$where .= " AND mail_id = '".$db->escapeString($params->get('mail_id'))."'";
        }
                
        $params->set('columns', '*');
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'mail_users');
        return $this->callService('SqlTable', 'select', $params);
    }
    
    
}
?>