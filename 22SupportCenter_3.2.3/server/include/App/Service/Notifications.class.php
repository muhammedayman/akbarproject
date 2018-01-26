<?php
/**
*   Handler class for otifications
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
QUnit::includeClass('App_Template');

class App_Service_Notifications extends QUnit_Rpc_Service {

	function initMethod($params) {
		$method = $params->get('method');

		switch($method) {
			case 'updateNotification':
			case 'getNotification':
			case 'getNotificationsList':
			case 'deleteNotification':
			case 'insertNotification':
				return $this->callService('Users', 'authenticateAgent', $params);
				break;
			default:
				return false;
				break;
		}
	}

    /**
     * Update Notification
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function updateNotification($params) {
    	$response =& $this->getByRef('response');
    	$db =& $this->state->getByRef('db');
    	
    	if(!$params->check(array('notification_id'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
    	
    	$params->set('table', 'notifications');
    	$params->set('where', "notification_id = '".$db->escapeString($params->get('notification_id'))."'");
    	return $this->callService('SqlTable', 'update', $params);
    }
	
    function callUrl($notification, $paramNotification) {
    	$url = App_Template::evaluateTemplate($paramNotification, $notification['call_url'], true);
    	if (strlen($url)) {
    		
	    	if (!$url_info = parse_url($url)) {
				$this->state->log('error', 'Invalid URL: ' . $url . " in notification: " . $notification['notif_name'], 'Notification');
	    		return false;   
	        }
	       
	        switch ($url_info['scheme']) {
	            case 'https':
	                $scheme = 'ssl://';
	                $port = 443;
	                break;
	            case 'http':
	            default:
	                $scheme = '';
	                $port = 80;   
	        }
	       
	        $data = "";
	        $fid = @fsockopen($scheme . $url_info['host'], $port, $errno, $errstr, 30);
	        if ($fid) {
	            fputs($fid, 'HEAD ' . (isset($url_info['path'])? $url_info['path']: '/') . (isset($url_info['query'])? '?' . $url_info['query']: '') . " HTTP/1.0\r\n" .
	                        "Connection: close\r\n" .
	                        'Host: ' . $url_info['host'] . "\r\n\r\n");   
	            while (!feof($fid)) {
	                $data .= @fgets($fid, 128);
	            }
	            fclose($fid);
				$this->state->log('debug', 'Executed URL: ' . $url . " returned content: " . $data, 'Notification');
	            return true;
	        } else {
	            return false;
	        }    		
	    		
    		
    	}
    	return true;
    }
	
	function notifyUsers($notifications, $new_status, $new_queue_id, $new_agent_owner_id, $new_priority, $mail_body = '', $hdr_message_id = '') {
		if ($this->state->config->get('disableNotifications') == 'y') {
			return true;
		}
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		$session = QUnit::newObj('QUnit_Session');
		if (is_array($notifications)) {

			$objPriorities = QUnit::newObj('App_Service_Priorities');
			$objPriorities->state = $this->state;
			$objPriorities->response = $this->response;
			$arrPrios = $objPriorities->getPrioritiesArray();
			
			$objStatuses = QUnit::newObj('App_Service_Statuses');
			$objStatuses->state = $this->state;
			$objStatuses->response = $this->response;
			$arrStatuses = $objStatuses->getStatusesArray();
			
			foreach ($notifications as $notification) {
				$recipients = array();
				
				//send email
				$paramNotification = $this->createParamsObject();
				$paramNotification->set('template_text', $notification['body']);
				$paramNotification->set('account_id', $notification['account_id']);
				
				$paramNotification->set('t_id', $notification['ticket_id']);
				$paramNotification->set('ticket_id', $notification['subject_ticket_id']);
				$paramNotification->set('ticket_subject', $notification['first_subject']);

				$paramNotification->set('changed_by', $session->getVar('name'));
				
				$paramNotification->set('customer_name', $notification['customer_name']);
				$paramNotification->set('customer_email', $notification['customer_email']);
				
				$paramNotification->set('old_priority', $arrPrios[$notification['ticket_priority']*1]);
				$paramNotification->set('old_status', $arrStatuses[$notification['ticket_status']]);
				$paramNotification->set('old_owner', $notification['ticket_agent_owner_name']);
				$paramNotification->set('old_queue', $notification['queue_name']);
				$paramNotification->set('queue_id', $notification['queue_id']);
				
				//load custom fields values
			    $paramCustom = $this->createParamsObject();
		    	$paramCustom->set('ticket_id', $notification['ticket_id']);
		    	if ($this->callService('Tickets', 'getCustomFieldValues', $paramCustom)) {
					$res = & $response->getByRef('result');
	    			if ($res['count'] > 0) {
						$customValues = $res['rs']->getRows($res['md']);
	    				foreach ($customValues as $customValue) {
	    					switch ($customValue['field_type']) {
	    						case 'h':
	    							$paramNotification->set('custom_' . $customValue[0], $customValue['options']);
	    							break;
	    						default:
	    							$paramNotification->set('custom_' . $customValue[0], $customValue['field_value']);
	    					}
	    				}
	    			}
				}
				
				if (strlen(trim($mail_body))) {
					$paramNotification->set('mail_body', str_replace("\n", '<br/>', $mail_body));
				} else {
					$paramNotification->set('mail_body', str_replace("\n", '<br/>', $notification['mail_body']));
				}
				
				if (strlen($new_agent_owner_id) && $new_agent_owner_id != $notification['ticket_agent_owner_id']) {
					//load new owner user
			    	$paramUser = $this->createParamsObject();
			    	$paramUser->set('user_id', $new_agent_owner_id);
			    	if (!($user = $this->callService('Users', 'loadUser', $paramUser))) {
			    		$response->set('error', $this->state->lang->get('noIdProvided'));
			    		return false;
					}
					$paramNotification->set('ticket_owner', $user['name']);
				} else {
					$paramNotification->set('ticket_owner', $notification['ticket_agent_owner_name']);
				}
				
				if (strlen(trim($new_priority)) && $new_priority *1 > 0) {
					$paramNotification->set('ticket_priority', $arrPrios[$new_priority*1]);
				} else {
					$paramNotification->set('ticket_priority', $arrPrios[$notification['ticket_priority']*1]);
				}
				
				if (strpos($notification['body'], '${knowledge_suggestions}') !== false && 
				$this->state->config->get('knowledgeBaseModule') == 'y') {
					$objKB = QUnit::newObj('App_Service_KBItems');
					$objKB->state = $this->state;
					$objKB->response = $response;
	
					$arrSimilar = $objKB->loadSearchItems(strip_tags($paramNotification->get('mail_body')));
					$suggestions = '';
					foreach ($arrSimilar as $item) {
						
						$title = (strlen($item['full_parent_subject']) ? ($item['full_parent_subject'] . ' / ') : '') . $item['subject'];
						
						$suggestions .= '<a href="' . $this->state->config->get('knowledgeBaseURL') . $item['full_path'] .
						 '" target="_blank">' . $title . '</a><br />';
					}
					$paramNotification->set('knowledge_suggestions', $suggestions);
				} else {
					$paramNotification->set('knowledge_suggestions', '');
				}
				
				if (strlen($new_status)) {
					$paramNotification->set('ticket_status', $arrStatuses[$new_status]);
				} else {
					$paramNotification->set('ticket_status', $arrStatuses[$notification['ticket_status']]);
				}
					
				if (strlen($new_queue_id) && $new_queue_id != $notification['ticket_queue_id']) {
					//load new queue
					$paramQueue = $this->createParamsObject();
					$paramQueue->set('queue_id', $new_queue_id);
					if (!($queue = $this->callService('Queues', 'loadQueue', $paramQueue))) {
						$response->set('error', $this->state->lang->get('noIdProvided'));
			    		return false;
					}
					$paramNotification->set('answer_time', $queue['answer_time']);
					$paramNotification->set('ticket_queue', $queue['name']);
											
				} else {
					$paramNotification->set('answer_time', $notification['answer_time']);
					$paramNotification->set('ticket_queue', $notification['queue_name']);
				}
				$paramNotification->set('Thread-Index', $notification['thread_id']);
//				if (strlen($notification['hdr_message_id'])) {
//					$paramNotification->set('Message-ID', $notification['hdr_message_id']);
//				} elseif(strlen($hdr_message_id)) {
//					$paramNotification->set('Message-ID', $hdr_message_id);
//				}
                $paramNotification->set('Message-ID', md5(uniqid(rand(), true)));
				
				

				$paramNotification->set('ticket_url', $this->state->config->get('applicationURL') . 'client/index.php#tid=' . $notification['subject_ticket_id']);
				$paramNotification->set('ticket_link', '<a href="' . $paramNotification->get('ticket_url') . '">' . $notification['subject_ticket_id'] . '</a>');
				$paramNotification->set('Auto-Submitted', 'auto-replied');

				$paramNotification->set('subject', $notification['subject']);
				
				//send it to notification owner
				if ($notification['sendto_notif_owner'] == 'y') {
					//check email quality of notification owner
					if ($notification['agent_email_quality'] >= 2 || $notification['agent_email_quality'] == 0) {
						$recipients[$notification['agent_email']] = $notification['agent_email'];
					}
				}

				if ($notification['sendto_customer'] == 'y') {
					//check email quality of customer
					if ($notification['customer_email_quality'] >= 2 || $notification['customer_email_quality'] == 0) {
						$recipients[$notification['customer_email']] = $notification['customer_email'];
					}
				}
				
				if ($notification['sendto_agent_owner'] == 'y') {
					//check email quality of ticket owner 
					if ($notification['ticket_agent_owner_email_quality'] >= 2 || $notification['ticket_agent_owner_email_quality'] == 0) {
						$recipients[$notification['ticket_agent_owner_email']] = $notification['ticket_agent_owner_email'];
					}
					
					if (strlen($new_agent_owner_id)) {
						$objUsers = QUnit::newObj('App_Service_Users');
						$paramsUsr = $this->createParamsObject();
						$paramsUsr->set('user_id', $new_agent_owner_id);
						$objUsers->state = $this->state;
						$objUsers->response = $this->response;
						if ($userAgent = $objUsers->loadUser($paramsUsr)) {
							if ($userAgent['email_quality'] >= 2 || $userAgent['email_quality'] == 0) {
								$recipients[$userAgent['email']] = $userAgent['email'];
							}
						}
					}
					
				}
				
				$this->addCustomNotificationValues($paramNotification);
				
				
				if (strlen($notification['sendto_recipients'])) {
					$notification['sendto_recipients'] = App_Template::evaluateTemplate($paramNotification, $notification['sendto_recipients']);
					$notification['sendto_recipients'] = str_replace(';', ',', $notification['sendto_recipients']);
					$arr = explode(',', $notification['sendto_recipients']);
					foreach($arr as $val){
						$recipients[$val] = $val;
					}
				}
				
				if (strlen(trim($notification['custom_from_mail']))) {
					$notification['custom_from_mail'] = App_Template::evaluateTemplate($paramNotification, trim($notification['custom_from_mail']));
					$paramNotification->set('from', $notification['custom_from_mail']);
				}
				
				if (!empty($recipients)) {
					foreach($recipients as $recipient) {
						if (strlen(trim($recipient)) && trim($recipient) != $session->getVar('username')) {
							$paramNotification->set('to', $recipient);
								
							if (!($ret = $this->callService('SendMail', 'send', $paramNotification))) {
							}
						}
					}
				}
				
				$this->callUrl($notification, $paramNotification);
				
			}
		}
		return true;
	}

	function addCustomNotificationValues(&$notificationParams) {
	    if (defined('CUSTOM_NOTIFICATION_DATA_CLASS')) {
            $response =& $this->getByRef('response');
	        $customObj = QUnit::newObj(CUSTOM_NOTIFICATION_DATA_CLASS);
            $customObj->state = $this->state;
            $customObj->response = $response;
            return $customObj->addParams($notificationParams);
	    }
	    return $notificationParams;
	}
	
	function getApplicableNotificationSettings($where_ticket_ids, $new_status, $new_queue_id, $new_agent_owner_id, $new_priority) {
		if ($this->state->config->get('disableNotifications') == 'y') {
			return array();
		}
		
		if ($new_status==false && $new_queue_id ==false &&
			 $new_agent_owner_id==false && $new_priority == false) {
			return false;
		}
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		$session = QUnit::newObj('QUnit_Session');

		$params = $this->createParamsObject();
		$params->set('distinct', true);
		$params->set('count_columns', 'un.user_id, t.ticket_id');
		$params->set('columns', "un.notification_id, 
								un.account_id,
								un.name as notif_name,
								un.call_url,
								un.custom_from_mail,
								un.user_id as agent_user_id,
								u.name as agent_name,
								u.email as agent_email,
								u.email_quality as agent_email_quality,
								un.body as body,
								un.subject as subject,
								uc.name as customer_name,
								uc.email as customer_email,
								uc.email_quality as customer_email_quality,
								t.queue_id as queue_id,
								q.name as queue_name,
								q.ticket_id_prefix as queue_ticket_id_prefix,
								q.answer_time as answer_time,
								t.ticket_id,
								t.subject_ticket_id,
								t.status as ticket_status,
								t.priority as ticket_priority,
								t.first_subject as first_subject,
								t.agent_owner_id as ticket_agent_owner_id,
								t.queue_id as ticket_queue_id,
								t.thread_id as thread_id,
								ua.name as ticket_agent_owner_name,
								ua.email as ticket_agent_owner_email,
								ua.email_quality as ticket_agent_owner_email_quality,
								m.body as mail_body,
								m.hdr_message_id as hdr_message_id,
								un.sendto_notif_owner,
								un.sendto_customer,
								un.sendto_agent_owner,
								un.sendto_recipients
								");

		$params->set('from', "notifications un
							  INNER JOIN users u ON u.user_id = un.user_id
							  INNER JOIN tickets t
							  INNER JOIN queues q ON t.queue_id = q.queue_id
							  LEFT JOIN (SELECT ticket_id, body, hdr_message_id FROM mails WHERE ticket_id IN (" . $where_ticket_ids . ") ORDER BY created DESC LIMIT 0,1) m ON t.ticket_id = m.ticket_id
							  LEFT JOIN users uc ON t.customer_id = uc.user_id
							  LEFT JOIN users ua ON t.agent_owner_id = ua.user_id
							  LEFT JOIN queue_agents qa ON qa.queue_id = q.queue_id AND qa.user_id = un.user_id");
		
		$where = "t.ticket_id IN (" . $where_ticket_ids . ")";
		$where .= " AND (u.user_type = 'a' OR (u.user_type='g' AND (q.public='y' OR qa.user_id = un.user_id)))";
		
		$orwhere = '';
		if (strlen($new_status)) {
			$orwhere .= (strlen($orwhere) ? ' OR ' : '') . "(un.status_check = 'y' AND (un.to_statuses LIKE '%|$new_status|%' OR un.to_statuses = '||' OR un.to_statuses IS NULL))";
		}
		if (strlen($new_queue_id)) {
			$orwhere .= (strlen($orwhere) ? ' OR ' : '') . "(un.queue_check = 'y' AND (un.to_queues LIKE '%|$new_queue_id|%' OR un.to_queues = '||' OR un.to_queues IS NULL))";
		}
		if (strlen($new_agent_owner_id)) {
			$orwhere .= (strlen($orwhere) ? ' OR ' : '') . "(un.owner_check = 'y' AND (un.to_owners LIKE '%|$new_agent_owner_id|%' OR un.to_owners = '||' OR un.to_owners IS NULL))";
		}
		if (strlen($new_priority)) {
			$orwhere .= (strlen($orwhere) ? ' OR ' : '') . "(un.priority_check = 'y' AND (un.to_priorities LIKE '%|" . ($new_priority*1) . "|%' OR un.to_priorities='||' OR un.to_priorities IS NULL))";
		}
		if (strlen($orwhere)) {
			$where .= " AND (" . $orwhere . ")";
		}

		$where .= " AND (un.ticket_owners LIKE (
												CASE 
													WHEN t.agent_owner_id IS NULL THEN '%|none|%'
													WHEN t.agent_owner_id = '' THEN '%|none|%'
													ELSE CONCAT('%|',t.agent_owner_id, '|%')
												END
												)
						OR
						(un.ticket_owners = '||' OR un.ticket_owners IS NULL) 
						OR 
						(un.ticket_owners LIKE '%|answered|%' AND 
						t.ticket_id IN (
							SELECT ticket_id 
							FROM mails m 
							INNER JOIN mail_users mu ON m.mail_id=mu.mail_id 
							WHERE user_id = un.user_id AND mail_role='from_user'
							)
						)
					)";

		$where .= " AND (un.ticket_status LIKE (CONCAT('%|',t.status, '|%')) OR (un.ticket_status = '||' OR un.ticket_status IS NULL))";
		$where .= " AND (un.ticket_priority LIKE (CONCAT('%|',(t.priority+0), '|%')) OR (un.ticket_priority = '||' OR un.ticket_priority IS NULL))";
		$where .= " AND (un.ticket_queue LIKE (CONCAT('%|',t.queue_id, '|%')) OR (un.ticket_queue = '||' OR un.ticket_queue IS NULL))";
		$where .= " AND (un.customer_groups LIKE (CONCAT('%|',uc.groupid, '|%')) OR (un.customer_groups = '||' OR un.customer_groups IS NULL))";
		
		$params->set('where', $where);
		$params->set('table', 'notifications');
		
    	if ($this->callService('SqlTable', 'select', $params)) {
        	$result = $response->result;
			return $result['rs']->getRows($result['md']);
    	} else {
    		return false;
    	}
	}

	function getNotification($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		$session = QUnit::newObj('QUnit_Session');

		if(!$params->check(array('notification_id'))) {
			$response->set('error', $this->state->lang->get('noIdProvided'));
			return false;
		}
		
		return $this->callService('Notifications', 'getNotificationsList', $params);
	}

	function insertNotification($params) {
		$response =& $this->getByRef('response');
		$db =& $this->state->getByRef('db');
		$session = QUnit::newObj('QUnit_Session');
   
		$params->setField('notification_id', 0);
		$params->setField('user_id', $session->getVar('userId'));
		
		if (!$params->checkFields(array('status_check'))) {
			$params->setField('status_check', 'n');
		}

		if (!$params->checkFields(array('queue_check'))) {
			$params->setField('queue_check', 'n');
		}
		
		if (!$params->checkFields(array('priority_check'))) {
			$params->setField('priority_check', 'n');
		}

		if (!$params->checkFields(array('owner_check'))) {
			$params->setField('owner_check', 'n');
		}

		if (!$params->checkFields(array('sendto_notif_owner'))) {
			$params->setField('sendto_notif_owner', 'y');
		}
		if (!$params->checkFields(array('sendto_customer'))) {
			$params->setField('sendto_customer', 'n');
		}
		if (!$params->checkFields(array('sendto_agent_owner'))) {
			$params->setField('sendto_agent_owner', 'n');
		}
		
		$params->set('table', 'notifications');
		return $this->callService('SqlTable', 'insert', $params);
	}
	
	/*
	 * Return list of notifications defined by user
	 */
	function getNotificationsList($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		$session = QUnit::newObj('QUnit_Session');
		
		$columns = "n.*, u.email";
		$from = "notifications n LEFT JOIN users u ON (n.user_id = u.user_id)";
		$where = '1';
		
		if ($session->getVar('userType') == 'a') {
			if (strlen($params->get('user_id'))) {
				$where .= " AND n.user_id='" . $db->escapeString($params->get('user_id')) . "'";
			}
		} else {
			$where .= " AND n.user_id='" . $db->escapeString($session->getVar('userId')) . "'";
		}
		
		if ($id = $params->get('notification_id')) {
			$where .= " AND notification_id = '" . $db->escapeString($id) . "'";
		}
		
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('where', $where);
		$params->set('table', $from);
		return $this->callService('SqlTable', 'select', $params);
	}
	
	
 /**
     *  deleteNotification
     *
     *  @param string table
     *  @param string id of notification
     *  @return boolean
     */
    function deleteNotification($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');
		$session = QUnit::newObj('QUnit_Session');
    	
    	$ids = explode('|',$params->get('notification_id'));
    	
    	$where_ids = '';
    	foreach ($ids as $id) {
    		if (strlen(trim($id))) {
    			$where_ids .= (strlen($where_ids) ? ', ': '');
    			$where_ids .= "'" . $db->escapeString(trim($id)) . "'";
    		}
    	}
    	
    	if(!$params->check(array('notification_id')) || !strlen($where_ids)) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
    	
    	$where = '1';
    	
		if ($session->getVar('userType') != 'a') {
    		$where .= " AND user_id='" . $db->escapeString($session->getVar('userId')) . "'";
		}
    	$where .= " AND notification_id IN (" . $where_ids . ")";

    	$params->set('table', 'notifications');
    	$params->set('where', $where);
    	return $this->callService('SqlTable', 'delete', $params);
    }
	
}
?>
