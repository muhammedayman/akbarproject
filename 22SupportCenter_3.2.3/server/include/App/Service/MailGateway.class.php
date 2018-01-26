<?php
/**
*   Mail Forwarding (Agent To User and User to Agent forwarding)
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/
QUnit::includeClass("QUnit_Rpc_Service");

class App_Service_MailGateway extends QUnit_Rpc_Service {
	
	function forwardMail($ticket_id, $mail_id) {
		if ($this->state->config->get('disableMailGateway') == 'y') {
			return true;
		}
		
		if (!($ticket = $this->loadTicket($ticket_id))) {
			return false;
		}
		if (!($mail = $this->loadMail($mail_id))) {
			return false;
		}
		if (!($mail_users = $this->loadMailUsers($mail_id))) {
			return false;
		}
		
		switch ($this->isCustomerToAgentDirection($ticket, $mail_users)) {
			case 'c2a':
				return $this->forwardCustomerToAgent($ticket, $mail, $mail_users);
			case 'a2c': 
				return $this->forwardAgentToCustomer($ticket, $mail, $mail_users);
			default:
				return true;
		}
	}
	
	/**
	 * Load Ticket to array
	 */
	function loadTicket($ticket_id) {
    	$response =& $this->getByRef('response');
		$params = $this->createParamsObject();
		$params->set('ticket_id', $ticket_id);
		$tObj = QUnit::newObj('App_Service_Tickets');
		$tObj->state = $this->state;
		$tObj->response = $response;
		return $tObj->loadTicket($params);
	}

	/**
	 * Load mail to array
	 */
	function loadMail($mail_id) {
    	$response =& $this->getByRef('response');
		$params = $this->createParamsObject();
		$params->set('mail_id', $mail_id);
		$tObj = QUnit::newObj('App_Service_Mails');
		$tObj->state = $this->state;
		$tObj->response = $response;
		return $tObj->loadMail($params);
	}

	/**
	 * Load mail to array
	 */
	function loadMailUsers($mail_id) {
    	$response =& $this->getByRef('response');
		$params = $this->createParamsObject();
		$params->set('mail_id', $mail_id);
		$tObj = QUnit::newObj('App_Service_Mails');
		$tObj->state = $this->state;
		$tObj->response = $response;
		return $tObj->loadMailUsers($params);
	}
	
	function isCustomerToAgentDirection(&$ticket, &$mail_users) {
		
		foreach ($mail_users as $usr) {
			if ($usr['mail_role'] == 'from') break;
		}
		
		//if ticket is owned by user, which sent mail, than direction is user to agents
		if ($ticket['customer_id'] == $usr['user_id']) {
			return 'c2a';
		} elseif ($ticket['customer_id'] != $usr['user_id'] && $usr['user_type'] != 'u') {
			return 'a2c';
		} else {
			//what to do now ... do nothing ???
			//this is not customer and not agent (any other user answered ticket, which he doesn't own)
			return 'c2a';
		}
	}
	
	function forwardCustomerToAgent(&$ticket, &$mail, &$mail_users) {
    	$response =& $this->getByRef('response');
		
		if ($agent_mails = $this->getAgentsListRequestingForwardedMails($ticket, $mail, $mail_users)) {
			
			foreach ($agent_mails as $agent_mail) {
				if (strlen(trim($agent_mail))) {
					//send email
					$param = $this->createParamsObject();
					//nahrat mailbody mailu
					if (strlen($mail['body_html'])) {
						$param->set('body', $mail['body_html']);
					} else {
						$param->set('body', str_replace(array("\r", "\n"), array('', '<br />'), $mail['body']));
					}
					$param->set('Auto-Submitted', 'auto-replied');
					
					//definovat FROM ako "customer mail" account@from.com
					foreach ($mail_users as $usr) {
						if ($usr['mail_role'] == 'from') break;
					}
					$param->set('from_name', $usr['name'] . ' ' . $usr['email']);
					//mal by sa vybrat mail account rovnaky ako mal prijaty mail
					$param->set('account_id', $mail['account_id']);
					$param->set('queue_id', $ticket['queue_id']);
					
					//naplnit header hodnoty ako thread-id, message-id atd. aby sa dalo matchnut odpovede
					$param->set('Thread-Index', $ticket['thread_id']);
					$param->set('Message-ID', $mail['hdr_message_id']);
					$param->set('to', $agent_mail);
					
					if ($this->state->config->get('hideTicketIdFromSubject') == 'y') {
						$param->set('subject', $mail['subject']);
					} else {
						$objQueues = QUnit::newObj('App_Service_Queues');
						$objQueues->state = $this->state;
						$objQueues->response = $response;
						$paramsQueue = $this->createParamsObject();
						$paramsQueue->set('queue_id', $ticket['queue_id']);
						$queue = $objQueues->loadQueue($paramsQueue);
						
						$param->set('subject', '[' . $queue['ticket_id_prefix'] . '#' . $ticket['subject_ticket_id'] . '] '  . $mail['subject']);
					}

					//pridat attachmenty
					$paramsAtt = $this->createParamsObject();
					$paramsAtt->set('mail_id', $mail['mail_id']);
					
					$arrAttachments = array();
					if ($this->callService('Attachments', 'getMailAttachmentsList', $paramsAtt)) {
						$res = & $response->getByRef('result');
		    			if ($res['count'] > 0) {
		    				$file = $res['rs']->getRows($res['md']);
		    				
		    				foreach ($file as $attachment) {
		    					$arrAttachments[] = $attachment['file_id'];
		    				}
		    			}
					}
					$param->set('attachment_ids', $arrAttachments);

					
					//send mail
					if (!($ret = $this->callService('SendMail', 'send', $param))) {
						//if failed, continue with another mail
					}
				}				
			}
			
		}
		
		return true;
	}
	
	function forwardAgentToCustomer(&$ticket, &$mail, &$mail_users) {
		
		//don't forward comments or answers to comments to customers
		if ($mail['is_comment'] == 'y') {
			return true;
		}
		
    	$response =& $this->getByRef('response');
		//send email
		$param = $this->createParamsObject();
		//nahrat mailbody mailu
		if (strlen($mail['body_html'])) {
			$param->set('body', $mail['body_html']);
		} else {
			$param->set('body', str_replace("\n", '<br/>', $mail['body']));
		}
		
		//definovat FROM - meno agenta
		foreach ($mail_users as $usr) {
			if ($usr['mail_role'] == 'from') break;
		}
		$param->set('from_name', $usr['name']);
		//mal by sa vybrat mail account rovnaky ako mal prijaty mail
		$param->set('account_id', $mail['account_id']);
		$param->set('queue_id', $ticket['queue_id']);
		
		//naplnit header hodnoty ako thread-id, message-id atd. aby sa dalo matchnut odpovede
		$param->set('Thread-Index', $ticket['thread_id']);
		$param->set('Message-ID', $mail['hdr_message_id']);
		
		//todo poslat to zakaznikovi
		//load customer user
		$objUser = QUnit::newObj('App_Service_Users');
		$objUser->state = $this->state;
		$objUser->response = $response;
		$paramUser = $this->createParamsObject();
		$paramUser->set('user_id', $ticket['customer_id']);
		$customer_user = $objUser->loadUser($paramUser);
		
		$param->set('to', $customer_user['email']);
		
		if ($this->state->config->get('hideTicketIdFromSubject') == 'y') {
			$param->set('subject', $mail['subject']);
		} else {
			//nahrat queue a odoslat aj ticket id v subject
			$objQueues = QUnit::newObj('App_Service_Queues');
			$objQueues->state = $this->state;
			$objQueues->response = $response;
			$paramsQueue = $this->createParamsObject();
			$paramsQueue->set('queue_id', $ticket['queue_id']);
			$queue = $objQueues->loadQueue($paramsQueue);
			
			$param->set('subject', '[' . $queue['ticket_id_prefix'] . '#' . $ticket['subject_ticket_id'] . '] '  . $mail['subject']);
		}

		//pridat attachmenty
		$paramsAtt = $this->createParamsObject();
		$paramsAtt->set('mail_id', $mail['mail_id']);
		
		$arrAttachments = array();
		if ($this->callService('Attachments', 'getMailAttachmentsList', $paramsAtt)) {
			$res = & $response->getByRef('result');
   			if ($res['count'] > 0) {
   				$file = $res['rs']->getRows($res['md']);
   				
   				foreach ($file as $attachment) {
   					$arrAttachments[] = $attachment['file_id'];
   				}
   			}
		}
		$param->set('attachment_ids', $arrAttachments);

		
		//send mail
		if (!($ret = $this->callService('SendMail', 'send', $param))) {
			//if failed, continue with another mail
		}
		return true;
	}
	
	/**
	 * Load list of mails, where to forward mail
	 */
	function getAgentsListRequestingForwardedMails(&$ticket, &$mail, &$mail_users) {
		
		//check if gateWay is not disabled
		if ($this->state->config->get('disableMailGateway') == 'y') {
			return array();
		}
		
		
		//podla pravidiel vratit pole mailov

		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		$session = QUnit::newObj('QUnit_Session');
		
		$columns = "u.email";
		$from = "users u INNER JOIN mail_gateways mg ON (mg.user_id = u.user_id)";
		$where = "u.user_type <> 'u'";
		
		$where .= " AND (mg.statuses='||' OR mg.statuses IS NULL OR mg.statuses LIKE '%|" . $ticket['status'] . "|%')";
		$where .= " AND (mg.priorities='||' OR mg.priorities IS NULL OR mg.priorities LIKE '%|" . ltrim($ticket['priority'], '0') . "|%')";
		$where .= " AND (mg.queues='||' OR mg.queues IS NULL OR mg.queues LIKE '%|" . $ticket['queue_id'] . "|%')";
		$where .= " AND (mg.ticket_owners LIKE '" . (strlen($ticket['agent_owner_id']) ? ("%|" . $ticket['agent_owner_id'] . "|%") : "%|none|%") . "'" . 
						
						(!strlen($ticket['agent_owner_id']) ? " OR (mg.ticket_owners = '||' OR mg.ticket_owners IS NULL)" : '') .

						"OR 
						(mg.ticket_owners LIKE '%|answered|%' AND 
						mg.user_id IN (
							SELECT mu.user_id 
							FROM mail_users mu INNER JOIN mails m ON m.mail_id = mu.mail_id  
							WHERE mu.user_id = mg.user_id AND (mail_role='from_user' OR mail_role='from') AND m.ticket_id = '" . $ticket['ticket_id'] . "'
							)
						)
					)";
		
		
		$params = $this->createParamsObject();
		$params->set('distinct', true);
		$params->set('count_columns', $columns);
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('where', $where);
		$params->set('table', "users");
   		$mails = array();
		if ($this->callService('SqlTable', 'select', $params)) {
   			$res = & $response->getByRef('result');
   			if ($res['count'] > 0) {
				$users = $res['rs']->getRows($res['md']);
				foreach ($users as $user) {
					$mails[] = $user['email'];
				}
   			}			
		}
		
		return $mails;
	}
}
?>