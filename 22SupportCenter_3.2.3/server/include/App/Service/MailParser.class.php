<?php
/**
*   Read Emails from all POP3 accounts and store them into system
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/
QUnit::includeClass("QUnit_Rpc_Service");
QUnit::includeClass("QUnit_Net_Mail");
QUnit::includeClass("App_Service_Tickets");

require_once 'PEAR.php';
require_once 'Mail/RFC822.php';

class App_Service_MailParser extends QUnit_Rpc_Service {

	var $pop3;

	/**
	 * Check if another parser is not running
	 * if it is running, email was created in last 10 seconds
	 *
	 * @return unknown
	 */
	function isRunning($account_id) {
		
		if (isset($_GET['debug_session_id']) && strlen($_GET['debug_session_id'])) return false;
		
		//check if another parser is not running (email is created in last 20 seconds)
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');

		$columns = "count(*) as running";
		$from = "mail_accounts";
		$where = "last_msg_received IS NOT NULL AND last_msg_received <> '0000-00-00 00:00:00' AND last_msg_received > ('" . $db->getDateString() . "' - INTERVAL 150 SECOND) AND 
				account_id = '" . $db->escapeString($account_id) . "'";

		$params = $this->createParamsObject();
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('where', $where);
		$params->set('table', 'mails');
		if (!$this->callService('SqlTable', 'select', $params)) {
			return false;
		} else {
			if ($response->getResultVar('count') > 0) {
				//another job is running
				return true;
			} else {
				//is not running
				return false;
			}
		}
	}
	
	function isDirectoryWritable($path) {
		return file_exists($path) && is_writable($path);
	}
	
	/**
	 * Iterate through all registered pop3 accounts and load all emails from them
	 *
	 */
	function runParser() {
		//path has to include also slash at the end !!!
		if (!defined('TEMP_PATH')) {
			if ($this->state->config->get('tmpPath')) {
				define('TEMP_PATH', $this->state->config->get('tmpPath'));
			} else {
				define('TEMP_PATH', '/tmp/');
			}
		}
		
		if (!$this->isDirectoryWritable(TEMP_PATH)) {
			$this->state->log('error', "Temp directory is not writable: " . TEMP_PATH, 'MailParser');
			return false;
		}
		
		$concurentMailAccounts = 0;
		$readingAccountNr = 0;
		
		$params = QUnit::newObj('QUnit_Rpc_Params');
		$response =& $this->getByRef('response');
		if ($arrMailAccounts = $this->callService('MailAccounts', 'getMailAccountsListAllFields', $params)) {
			$rs = $response->getResultVar('rs');
			foreach ($rs->getRows($response->getResultVar('md')) as $rowid => $row) {
				$readingAccountNr ++;				
				
		        if ($concurentMailAccounts > 0 && $readingAccountNr > $concurentMailAccounts) {
					$this->state->log('info', "Please upgrade your license of SupportCenter, you can't read more mail accounts as " . $concurentMailAccounts, 'MailParser');
		        }				
				$this->pop3 = QUnit::newObj('QUnit_Net_Mail_Pop3');
				$this->pop3->join_continuation_header_lines=1;
				$objPop3 = QUnit::newObj('App_Mail_Pop3Account');
				$objPop3->loadObjectAttributes($row);
				if (!$this->isRunning($objPop3->account_id)) {
					$this->state->log('debug', $this->state->lang->get('startPOP3Read') . $objPop3->account_name, 'MailParser');
					$this->checkPOP3AccountMails($objPop3);
					$this->state->log('debug', $this->state->lang->get('endPOP3Read') . $objPop3->account_name, 'MailParser');
				} else {
					$this->state->log('info', $this->state->lang->get('accountReadingIsStillRunning', $objPop3->account_name), 'MailParser');
				}
			}
		}
		return true;
	}
	
	/**
	 * Load emails from defined pop3 account
	 *
	 * @param unknown_type $accountid
	 * @param unknown_type $account_name
	 * @param unknown_type $pop3_server
	 * @param unknown_type $pop3_port
	 * @param unknown_type $pop3_ssl
	 * @param unknown_type $pop3_username
	 * @param unknown_type $pop3_password
	 */
	function checkPOP3AccountMails($pop3Account) {
		
		if ($pop3Account->smtp_ssl == 'y' || $pop3Account->pop3_ssl == 'y') {
			//make check if OpenSsl is avaible

			$version=explode(".",function_exists("phpversion") ? phpversion() : "3.0.7");
			
			$php_version=intval($version[0])*1000000+intval($version[1])*1000+intval($version[2]);
			
			if($php_version<4003000) {
				$this->state->log('error', "establishing SSL connections requires at least PHP version 4.3.0", 'MailParser');
				return true;
			}
			if(!function_exists("extension_loaded")
			|| !extension_loaded("openssl")) {
				$this->state->log('error', "establishing TLS connections requires the OpenSSL extension enabled", 'MailParser');
				return true;
			}
		}
		
		if (!strlen(trim($pop3Account->pop3_server))) {
            $this->state->log('error', $pop3Account->account_name . ' has no pop3 server defined', 'MailParser');
		    return true;
		}
		
		$this->pop3->hostname = $pop3Account->pop3_server;
		$this->pop3->tls=($pop3Account->pop3_ssl == 'y' ? 1 : 0);
		$this->pop3->port = $pop3Account->pop3_port;
		
		//Open POP3 account
		if(($error=$this->pop3->Open())=="") {
			$this->state->log('debug', $this->state->lang->get('pop3ConnectionOpen') . $pop3Account->pop3_server, 'MailParser');

			if(($error=$this->pop3->Login($pop3Account->pop3_username, $pop3Account->pop3_password, $pop3Account->apop))=="") {
				$this->state->log('debug', $this->state->lang->get('loginPop3', $pop3Account->pop3_server, $pop3Account->pop3_username), 'MailParser');

				//retrieve statistic information from pop3 account
				if(($error=$this->pop3->Statistics($messages, $size))=="") {
					$this->state->log('info', $this->state->lang->get('pop3InfoMessage', $pop3Account->account_name, $messages, $size), 'MailParser');

					if ($messages > 0) {
						//load all messages from opened account
						$this->retrieveMessagesFromOpenedPop3Account($pop3Account);
					}
					
				} else {
					$this->state->log('error', $this->state->lang->get('pop3StatisticFailed', $pop3Account->pop3_server, $pop3Account->pop3_username, $error), 'MailParser');
				}

				// All Done, Close POP3 account
				if ( ($error = $this->pop3->Close()) == "") {
					//all ok closed
					$this->state->log('debug', $this->state->lang->get('pop3ConnectionClosed', $pop3Account->pop3_server), 'MailParser');
				} else {
					// report close connection problem
					$this->state->log('error', $this->state->lang->get('failedToCloseConnection', $pop3Account->pop3_server, $error), 'MailParser');
				}
				
			} else {
				$this->state->log('error', $this->state->lang->get('pop3FailedLogin', $pop3Account->pop3_server, $pop3Account->pop3_username, $error), 'MailParser');
			}
				
			$paramsRelease = $this->createParamsObject();
			$paramsRelease->set('account_id', $pop3Account->account_id);
			if (!$this->callService('MailAccounts', 'releaseMailAccount', $paramsRelease)) {
				$this->state->log('error', $this->state->lang->get('failedToReleaseAccount', $pop3Account->pop3_server, $this->response->get('error')), 'MailParser');
			}
		} else {
			//report connection problem
			$this->state->log('error', $this->state->lang->get('failedToConnectToPop3', $pop3Account->pop3_server, $error), 'MailParser');
		}
		return true;
	}
	/**
	 * Retrieve all messages one by one from pop3 account
	 *
	 */
	function retrieveMessagesFromOpenedPop3Account($pop3Account) {
		$result=$this->pop3->ListMessages("",1);
		$response =& $this->getByRef('response');
		
		$processedMails = 0;
		
		if(GetType($result)=="array" && count($result) > 0) {
			
			//zacat stahovat az od posledneho stiahnuteho ak sa maily z mailboxu nemazu
			$last_msg_id = false;
			if (strlen($pop3Account->last_unique_msg_id)) {
				$last_msg_id = array_search($pop3Account->last_unique_msg_id, $result);
				if ($last_msg_id !== false) $this->state->log('debug', 'Last message ID: ' . $pop3Account->last_unique_msg_id, 'MailParser');
			}
			
			foreach ($result as $message => $unique_message_id) {
				
				//skip older messages as last parsed message;
				if (!$this->state->config->get('disableMailFetchingOptimization') && $last_msg_id && $last_msg_id >= $message) continue;

				//check, if message is already in db
				$paramsCheckMail = $this->createParamsObject();
				$paramsCheckMail->set('unique_msg_id', $unique_message_id);
				if ($this->state->config->get('checkUniqueMailJustInAccount')) {
					$paramsCheckMail->set('account_id', $pop3Account->account_id);
				}
				if (!$this->callService('Mails', 'getMailList', $paramsCheckMail)) {
					$this->state->log('error', $this->state->lang->get('failedMessageExistanceCheck', $unique_message_id, $response->error), 'MailParser');
					return false;
				} else {
					
					$res = & $response->getByRef('result');
					if ($res['count'] == 0) {
						//mail is not in db, continue

						$processedMails ++;
							
						if ($processedMails > 100) {
							 return true;
            }
						
						if ($this->retrieveMessage($pop3Account, $message, $unique_message_id)) {
							//set current message as last parsed email in this account
							$paramsAccount = $this->createParamsObject();
							$paramsAccount->set('account_id', $pop3Account->account_id);
							$paramsAccount->set('unique_msg_id', $unique_message_id);
							if (!$this->callService('MailAccounts', 'setLastParsedMessage', $paramsAccount)) {
								$this->state->log('error', $this->state->lang->get('failedUpdateLastParsedMessage', $pop3Account->account_id, $response->error), 'MailParser');
								return false;
							}

							//if account has defined, that messages should be deleted, delete message
							if ($pop3Account->delete_messages == 'y') {
								//messages are in reality deleted when connection will be closed !!!
								if ($this->deleteMessage($message, $unique_message_id)) {
									$this->state->log('info', $this->state->lang->get('messageMarkedAsDeleted', $unique_message_id, $pop3Account->account_name), 'MailParser');
								}
							}
						}
					} else {
						$this->state->log('debug', $this->state->lang->get('mailAlreadyInDatabase', $unique_message_id), 'MailParser');
					}
				}
			}
			return true;
		} else {
			$this->state->log('error', $this->state->lang->get('failedToListMessagesFromPop3', $pop3Account->pop3_server, $result), 'MailParser');
			return false;
		}
	}
	/**
	 * Retrieve defined message from opened pop3 account
	 *
	 * @param unknown_type $message
	 * @param unknown_type $unique_message_id
	 */
	function retrieveMessage($pop3Account, $message, $unique_message_id) {
		if(($error=$this->pop3->OpenMessage($message,-1))=="") {
			$this->state->log('debug', $this->state->lang->get('openedMessage') . $unique_message_id, 'MailParser');

			$endOfMessage = 0;
			$iteration = 0;

			$mimeparser = QUnit::newObj('App_Mail_MimeMailParser');
			$mimeparser->message = $message;
			$mimeparser->unique_message_id = $unique_message_id;
			$mimeparser->parser = &$this;
			if ($mimeparser->Decode($decoded)) {
				$this->state->log('debug', $this->state->lang->get('decodedMessage') . $unique_message_id, 'MailParser');
				$this->storeMessageToSystem($pop3Account, $decoded[0], $unique_message_id);
			} else {
				$this->state->log('error', $this->state->lang->get('failedToDecodeMessage', $unique_message_id, $mimeparser->error), 'MailParser');
			}
			
			return true;
		} else {
			$this->state->log('error', $this->state->lang->get('failedToOpenMessage', $unique_message_id, $error), 'MailParser');
			return false;
		}
	}

	function getDecodedHeaderValue($val, $target_encoding = 'UTF-8') {
		if (is_array($val)) {
			if (isset($val['Value'])) {
				$ret = $val['Value'];
				if (isset($val['Encoding']) && strlen($val['Encoding']) && strlen($target_encoding) && 
					strtoupper($target_encoding) != strtoupper($val['Encoding'])) {
						$ret = $this->convertEncoding($ret, $val['Encoding'], $target_encoding);
				} else if (!isset($val['Encoding']) || !strlen($val['Encoding'])) {
					$ret = $this->convertEncoding($ret, 'ISO-8859-1', $target_encoding);
				}
				return $ret;
			} else {
				return $this->getDecodedHeaderValue($val[0]);
			}
		} else {
			return false;
		}
	}

	function getHeaderValue(&$data, $header_name, $target_encoding = 'UTF-8') {
		if (isset($data['Headers'])) {
			if (isset($data['Headers'][$header_name])) {

				if (isset($data['DecodedHeaders']) && isset($data['DecodedHeaders'][$header_name])) {
					return $this->getDecodedHeaderValue($data['DecodedHeaders'][$header_name], $target_encoding);
				} else {
					if (is_array($data['Headers'][$header_name])) {
						return $data['Headers'][$header_name];
					} else {
						return $this->convertEncoding($data['Headers'][$header_name], 'ISO-8859-1', 'UTF-8');
					}
				}
			}
		}
		return false;
	}
	
	function fillMailParameters($pop3Account, &$data, $unique_message_id) {
		$db = $this->state->get('db');
		$response =& $this->getByRef('response');

		$paramsMail = $this->createParamsObject();
		$paramsMail->setField('account_id', $pop3Account->account_id);
		$paramsMail->setField('unique_msg_id', $unique_message_id);
		
		
		//set body
		$attachments = $this->getAllAttachments($data);
		
		if (count($attachments) > 0 && file_exists($attachments[0]['BodyFile'])) {
			$content = $this->getAttachmentContent($attachments[0]);
			if (preg_match("/text\/plain/im", $attachments[0]['Headers']['content-type:']) || !isset($attachments[0]['Headers']['content-type:'])) {
				//avoid html code inclusion into body view
				$content = str_replace(array('<', '>'), array('&lt;', '&gt;'), $content);
				$paramsMail->setField('body', $content);
				if (file_exists($attachments[0]['BodyFile'])) {
				    unlink($attachments[0]['BodyFile']);
				}
				
				if (preg_match("/text\/html/im", $attachments[1]['Headers']['content-type:']) && 
						!preg_match("/name=/im", $attachments[1]['Headers']['content-type:'])) {
					$content_html = $this->getAttachmentContent($attachments[1]);
					$paramsMail->setField('body_html', $content_html);
					if (file_exists($attachments[1]['BodyFile'])) {
					    unlink($attachments[1]['BodyFile']);
					}
				} else {
					$paramsMail->setField('body_html', str_replace("\n", '<br />', $paramsMail->get('body')));
				}
			} else if (preg_match("/text\/html/im", $attachments[0]['Headers']['content-type:'])) {
				$paramsMail->setField('body_html', $content);
				$objTxt = QUnit::newObj('QUnit_Txt_Html2Text');
				$objTxt->html2text($content);
				$content = $objTxt->get_text();
				$paramsMail->setField('body', $content);
			}
		} else { //something else ??? this is error
			$paramsMail->setField('body', '');
			$paramsMail->setField('body_html', '');
			$this->state->log('error', $this->state->lang->get('failedToParseMailBody') . $this->getHeaderValue($data, 'message-id:') ,'MailParser');
		}
		
		
		//check if mail shouldn't be assigned to existing ticket by subject ticket ID 
		$objTickets = QUnit::newobj('App_Service_Tickets');
		$objTickets->state = $this->state;
		$objTickets->response = $response;
		
		if ($ticket_id = $objTickets->getTicketByMailSubject($this->getHeaderValue($data,'subject:'))) {
			$paramsMail->setField('ticket_id', $ticket_id);
			$this->updateTicketStatus($paramsMail, $data);
		}
		
		
		//zistit parent mail id a tym aj ticket_id ak ticket uz existoval
		if (!strlen($paramsMail->getField('ticket_id')) && ($this->getHeaderValue($data,'in-reply-to:') || $this->getHeaderValue($data,'references:'))) {
			//search in mails if such email is not already registered in system

			$references = array();
			
			if ($this->getHeaderValue($data, 'in-reply-to:')) {
				$references[] = $this->getHeaderValue($data, 'in-reply-to:');
			}
			if ($this->getHeaderValue($data, 'references:')) {
				//references moze obsahovat viacero zaznamov oddelenych cez \t
				$reference_id = $this->getHeaderValue($data, 'references:');
				$reference_id = explode("\t", $reference_id);
				$references = array_merge($reference_id, $references);
			}
				
			foreach ($references as $idx => $reference_id) {
				$paramsParentMail = $this->createParamsObject();
				$paramsParentMail->set('hdr_message_id', $reference_id);

				if (!$this->callService('Mails', 'getMailList', $paramsParentMail)) {
					$this->state->log('error', $this->state->lang->get('failedRequestEmails') . $response->error, 'MailParser');
					return false;
				} else {
					$res = & $response->getByRef('result');
					if ($res['count'] > 0) {
						$rows = $res['rs']->getRows($res['md']);
						$paramsMail->setField('parent_mail_id', $rows[0]['mail_id']);
						$paramsMail->setField('ticket_id', $rows[0]['ticket_id']);
						$paramsMail->setField('is_comment', $rows[0]['is_comment']);
						$this->updateTicketStatus($paramsMail, $data);
						break;
					}
				}
			}
		}

		//Thread-Index header parameter check
		if (!strlen($paramsMail->getField('ticket_id')) && $this->getHeaderValue($data, 'thread-index:')) {
			//zisti, ci index nieje vlastne existujuci ticket_id
			$paramsThread = $this->createParamsObject();
			$paramsThread->set('thread_id', $this->resizeThreadIndex($this->getHeaderValue($data,'thread-index:')));

			//toto povolit len ak je to v settingu povolene
			if ($this->state->config->get('divideSameMailToMoreTickets') == 'y') {
				$paramsThread->set('account_id', $pop3Account->account_id);
			}
			
			if (!$this->callService('Tickets', 'getTicketsRows', $paramsThread)) {
				$this->state->log('error', $this->state->lang->get('failedRequestTickets') . $response->error, 'MailParser');
				return false;
			} else {
				$res = & $response->getByRef('result');
				if ($res['count'] > 0) {
					$rows = $res['rs']->getRows($res['md']);
					$paramsMail->setField('ticket_id', $rows[0]['ticket_id']);
					$this->updateTicketStatus($paramsMail, $data);
				}
			}
		}
		
		//check bounced emails
		if (!strlen($paramsMail->getField('ticket_id')) && $this->isAutoSubmittedMail($data)) {
			//identify ticket_id and load ticket
			if ($ticket_id = $objTickets->getTicketByMailSubject(str_replace(array("\n", "\r"), ' ', $paramsMail->get('body')))) {
				$paramsMail->setField('ticket_id', $ticket_id);
				
				//change status of ticket to bounced			 
				//b - Bounced
				$this->updateTicketStatus($paramsMail, $data, TICKET_STATUS_BOUNCED);
			}	
		}
		

		$paramsMail->setField('is_answered', 'n');

		//set subject
		if ($this->getHeaderValue($data,'subject:')) {
			$paramsMail->setField('subject', 
				$objTickets->cleanSubjectPrefixes(
					$objTickets->removeTicketIdFromSubject(
						$this->getHeaderValue($data, 'subject:'), 
						$objTickets->getSubjectTicketId(
							$this->getHeaderValue($data, 'subject:')
						)
					)
				)
			);
		} else {
			$paramsMail->setField('subject', $this->state->lang->get('noSubjectSpecified'));
		}
		
		$paramsMail->setField('headers', serialize($data['Headers']));
		if($this->getHeaderValue($data, 'message-id:')){
			if (is_array($this->getHeaderValue($data, 'message-id:'))) {
				$msq_val = $this->getHeaderValue($data, 'message-id:');
				$paramsMail->setField('hdr_message_id', $msq_val[0]);
			} else {
				$paramsMail->setField('hdr_message_id', $this->getHeaderValue($data,'message-id:'));
			}
		}
		if (strlen(trim($this->getHeaderValue($data,'delivery-date:')))) {
			$paramsMail->setField('delivery_date', $db->getDateString(strtotime($this->getHeaderValue($data,'delivery-date:'))));
		} else {
			$paramsMail->setField('delivery_date', $db->getDateString());
		}
		$paramsMail->setField('delivery_status', 'd');
		
		return $paramsMail;
	}
	
	function resizeThreadIndex($threadIndex) {
        if(strlen($threadIndex) > 250) {
        	return md5($threadIndex);
        }
        return $threadIndex;
	}
	
	function updateTicketStatus($paramsMail, &$data, $new_status = '') {
		$db = $this->state->get('db');
		$response =& $this->getByRef('response');
		
		$paramsTicket = $this->createParamsObject();
		$paramsTicket->set('ticket_id', $paramsMail->getField('ticket_id'));
		$paramsTicket->setField('last_update', $db->getDateString());
		$paramsTicket->set('mail_body', $paramsMail->get('body'));
		
		//nastavit status podla toho ci tento mail poslal agent alebo customer na awaiting reply alebo na customer-reply
		if (strlen($new_status)) {
			$paramsTicket->setField('status', $new_status);
		}
		
		//reply-to address has higher priority as From address
		if (isset($data['Headers']['reply-to:'])) {
		    $customerData = $data['Headers']['reply-to:'];
		} else {
		    $customerData = $data['Headers']['from:'];
		}
		
		if (is_array($customerData)) {
		    $maxFromMailsCount = 1;
		    if (strlen($this->state->config->get('maxFromMailsCount'))) {
		        $maxFromMailsCount = $this->state->config->get('maxFromMailsCount');
		    }
			foreach ($customerData as $idx => $to_mail) {
				if ($idx < $maxFromMailsCount && strlen(QUnit_Net_Mail::getEmailAddress($customerData, $idx))) {
					$usersObj = QUnit::newObj('App_Service_Users');
					$usersObj->state = $this->state;
					$usersObj->response = $response;
					
					if (strlen($new_status)) {
						$paramsTicket->setField('status', $new_status);
					} else {
						if ($usersObj->isAgent(QUnit_Net_Mail::getEmailAddress($customerData, $idx))) {
							//check if ticket was not created by this agent, so he will be customer in that case

							$objTickets = QUnit::newObj('App_Service_Tickets');
							$objTickets->state = $this->state;
							$objTickets->response = $response;
							
							$paramsUser = $this->createParamsObject();
							$paramsUser->set('email', QUnit_Net_Mail::getEmailAddress($customerData, $idx));
							
							$usersObj->getUserByEmail($paramsUser);
							
			    			$res = & $response->getByRef('result');
			    			if ($res['count'] > 0) {
								$user = $res['rs']->getRows($res['md']);
			    				$user = $user[0];
			    			}
							
							if (($ticket = $objTickets->loadTicket($paramsTicket)) &&
							 strlen($ticket['customer_id']) && 
							 $ticket['customer_id'] == $user['user_id']) {
								//ticket was created by agent, so he bacomes to be customer
								$paramsTicket->setField('status', 'c');
							} else {
								$paramsTicket->setField('status', 'a');
							}
						} else {
							$paramsTicket->setField('status', 'c');
						}
					}
				}
			}
		}
		
		if (!$this->callService('Tickets', 'updateTicket', $paramsTicket)) {
			$this->state->log('error', $this->state->lang->get('failedUpdateTicket'), 'MailParser');
			return false;
		}					
		return true;		
	}
	
	function getAttachmentContent($fileMetadata, $target_encoding = 'UTF-8') {
		$ret = file_get_contents($fileMetadata['BodyFile']);
		if (strlen($target_encoding)) { 
			if (preg_match('/charset="*([^;"]*)/', $this->getHeaderValue($fileMetadata, 'content-type:'), $match)) {
				$source_encoding = strtoupper($match[1]);
			} else {
				$source_encoding = 'ISO-8859-1';
			}
			if (strlen($source_encoding) && $source_encoding != strtoupper($target_encoding)) {
				$ret = $this->convertEncoding($ret, $source_encoding, $target_encoding);
			}
			
		}
		return $ret;
	}
	
	function convertEncoding($str, $from, $to) {
		if (!is_string($str)) {
			return $str;
		}
		if (function_exists('iconv')) {
			return iconv($from, $to, $str);
		} else if (function_exists('mb_convert_encoding')) {
			return mb_convert_encoding($str, $to, $from);
		} else {
			return $str;
		}
	}
	
	
	function getAllAttachments($data) {
		$arr = array();
		if (isset($data['BodyFile'])) {
			$arr[] = $data;
		}

		if (is_array($data['Parts']) && count($data['Parts']) > 0) {
			foreach ($data['Parts'] as $part) {
				$arr = array_merge($arr, $this->getAllAttachments($part));
			}
		}
		
		return $arr;
	}
	
	
	
	/**
	 * 
	 * @param App_Mail_Pop3Account $pop3Account
	 * @param unknown_type $data
	 * @param unknown_type $paramsMail
	 * @return unknown
	 * 	 */
	function getNewTicketId($pop3Account, &$data, &$paramsMail, &$paramsTicket) {
		$response =& $this->getByRef('response');

		//identifikovat do ktorej Queue ma patrit novy ticket ... ak nieje vyplnene, da sa default queue
		$paramsQueues = $this->createParamsObject();

			
		if ($this->getHeaderValue($data, 'thread-index:')) {
            $paramsTicket->setField('thread_id', $this->resizeThreadIndex($this->getHeaderValue($data,'thread-index:')));
		}

		$to = QUnit_Net_Mail::getEmailAddress($this->getHeaderValue($data, 'envelope-to:'));
		if (!strlen($to)) {
			//if envelope is empty, select first to address
			$to = QUnit_Net_Mail::getEmailAddress($this->getHeaderValue($data, 'to:'), 0);
		}
		$paramsQueues->set('queue_email', $to);

		//if rule didn't defined queue, load queue by email
		if (!strlen($paramsTicket->getField('queue_id')) && strlen($to)) {
			if (!$this->callService('Queues', 'getQueueByEmail', $paramsQueues)) {
				$this->state->log('error', $this->state->lang->get('failedSelectQueue'),'MailParser');
				return false;
			} else {
				$result = & $response->getByRef('result');
				if ($result['count'] < 1) {
					$paramsTicket->setField('queue_id', '');
				} else {
					$rows = $result['rs']->getRows($result['md']);
					$paramsTicket->setField('queue_id', $rows[0]['queue_id']);
				}
			}
		}
					
		//set in new ticket agent owner from rule
		if (strlen($paramsMail->get('rule_assign_ticket_to_agent'))) {
			$paramsTicket->setField('agent_owner_id', $paramsMail->get('rule_assign_ticket_to_agent'));
		}
			
		//create new ticket
		$paramsTicket->setField('first_subject', $paramsMail->get('subject'));
		$paramsTicket->set('mail_body', $paramsMail->get('body'));
		$paramsTicket->set('hdr_message_id', $paramsMail->get('hdr_message_id'));
		
		//get customer_id
		//reply-to address has higher priority as From address
		if (isset($data['Headers']['reply-to:'])) {
		    $customerData = $data['Headers']['reply-to:'];
		} else {
		    $customerData = $data['Headers']['from:'];
		}
		if (is_array($customerData)) {
            $maxFromMailsCount = 1;
            if (strlen($this->state->config->get('maxFromMailsCount'))) {
                $maxFromMailsCount = $this->state->config->get('maxFromMailsCount');
            }
		    foreach ($customerData as $idx => $to_mail) {
				if ($idx < $maxFromMailsCount && strlen(QUnit_Net_Mail::getEmailAddress($customerData, $idx))) {
									
					$params = $this->createParamsObject();
					$params->set('email', QUnit_Net_Mail::getEmailAddress($customerData, $idx));
					if (!$this->callService('Users', 'getUserByEmail', $params)) {
						$this->state->log('error', 'Failed request if user ' . QUnit_Net_Mail::getEmailAddress($customerData, $idx) . ' exist with error: ' . $response->error,'MailParser');
						return false;
					}
					if($response->getResultVar('count') == 0) {
						//ak neexistuje, vytvor ho
						$paramsUser = $this->createParamsObject();
						$paramsUser->setField('name', QUnit_Net_Mail::getPersonalName($customerData, $idx));
						$paramsUser->setField('email', QUnit_Net_Mail::getEmailAddress($customerData, $idx));
		
						if (!$this->callService('Users', 'insertUser', $paramsUser)) {
							$this->state->log('error', 'Failed to create new user ' . QUnit_Net_Mail::getEmailAddress($customerData, $idx) . ' with error: ' . $response->error,'MailParser');
							return false;
						} else {
							$paramsTicket->setField('customer_id', $paramsUser->get('user_id'));
						}
					} else {
						//nasiel usera
						$result = $response->result;
						$rows = $result['rs']->getRows($result['md']);
						$paramsTicket->setField('customer_id', $rows[0]['user_id']);
					}
									
				}
			}
		}
		
		if (!$this->callService('Tickets', 'insertTicket', $paramsTicket)) {
			$this->state->log('error', $this->state->lang->get('failedInsertTicket') . $response->error,'MailParser');
			return false;
		} else {
			$paramsTicket->setField('ticket_id', $paramsTicket->get('ticket_id'));
			$this->state->log('debug', $this->state->lang->get('createdNewTicket') . $paramsTicket->get('ticket_id'),'MailParser');
			return $paramsTicket->get('ticket_id');
		}
	}

	function deleteAttachments($attachment) {
        //process subparts if there are any
        if (is_array($attachment['Parts']) && count($attachment['Parts']) > 0) {
            foreach ($attachment['Parts'] as $part) {
                if (!$this->deleteAttachments($part)) {
                    return false;
                }
            }
        }
        
        //if this part is not represented by file, skip it
        if (!isset($attachment['BodyFile']) || !strlen($attachment['BodyFile']) || !file_exists($attachment['BodyFile'])) {
            return true;
        }
        
        //delete attachment from disk
        if (file_exists($attachment['BodyFile'])) {
            @unlink($attachment['BodyFile']);
        }
        return true;
	}
	
	/**
	 * Save Attachment into database
	 *
	 * @param unknown_type $attachment
	 * @param unknown_type $mail_id
	 */
	function saveAttachment($attachment, $mail_id) {
		$response =& $this->getByRef('response');

		//process subparts if there are any
		if (is_array($attachment['Parts']) && count($attachment['Parts']) > 0) {
			foreach ($attachment['Parts'] as $part) {
				if (!$this->saveAttachment($part, $mail_id)) {
					return false;
				}
			}
		}
		
		//if this part is not represented by file, skip it
		if (!isset($attachment['BodyFile']) || !strlen($attachment['BodyFile']) || !file_exists($attachment['BodyFile'])) {
			return true;
		}
		
		$filename = '';
		//in case of attachments set header content-location as filename
		if ($this->getHeaderValue($attachment, 'content-type:') && preg_match('/((.*?)\/(.*?));\s*?name="(.*?)"/', $this->getHeaderValue($attachment, 'content-type:'), $matches)) {
			$extension = $matches[3];
			$file_type = $matches[1];
			$filename = $matches[4];
		} else if ($this->getHeaderValue($attachment, 'content-type:') && preg_match('/((.*?)\/(.*?));\s*?name=(.*)/', $this->getHeaderValue($attachment, 'content-type:'), $matches)) {
            $extension = $matches[3];
            $file_type = $matches[1];
            $filename = $matches[4];
		} else if ($this->getHeaderValue($attachment, 'content-type:') && preg_match('/((.*?)\/(.*?));/', $this->getHeaderValue($attachment, 'content-type:'), $matches)) {
			$extension = $matches[3];
			$file_type = $matches[1];
		} else if ($this->getHeaderValue($attachment, 'content-type:') && preg_match('/((.*?)\/(.*?))$/', trim($this->getHeaderValue($attachment, 'content-type:')), $matches)) {
			$extension = $matches[2];
			$file_type = $matches[1];
		}
		if (!strlen($extension)) {
			$extension = 'bin';
		}
		
		if (!$this->getHeaderValue($attachment, 'content-location:') || !strlen($this->getHeaderValue($attachment, 'content-location:'))) {
			if (!strlen($filename)) $filename = 'attachment' . rand(0, 10000) . '.' . $extension;
			$attachment['Headers']['content-location:'] = $filename;
		} else {
			$filename = $this->getHeaderValue($attachment, 'content-location:');
		}
		
		//call service for saving file
		$paramsAttachment = $this->createParamsObject();
		$paramsAttachment->setField('filesize', $attachment['BodyLength']);
		$paramsAttachment->setField('filename', trim($filename));
		$paramsAttachment->setField('filetype', trim($file_type));
		$paramsAttachment->setField('contentid', rtrim(ltrim($this->getHeaderValue($attachment, 'content-id:'), '<'), '>'));

		if (!$this->callService('Files', 'insertFile', $paramsAttachment)) {
			$this->state->log('error', 'Failed insert new file for email_id ' . $mail_id . 'into database with error: ' . $response->error,'MailParser');
			return false;
		}
		
		//call service for loading file into attachment table
		$paramAttContent = $this->createParamsObject();
		$paramAttContent->set('filename', $attachment['BodyFile']);
		$paramAttContent->setField('file_id', $paramsAttachment->get('file_id'));
		if (!$this->callService('Files', 'loadFileToDb', $paramAttContent)) {
			$this->state->log('error', 'Failed to load file to database for email_id ' . $mail_id . 'into database with error: ' . $response->error,'MailParser');
			return false;
		}

		//call service for assigning mail to file
		$paramMailAttachment = $this->createParamsObject();
		$paramMailAttachment->setField('mail_id', $mail_id);
		$paramMailAttachment->setField('file_id', $paramsAttachment->get('file_id'));
		if (!$this->callService('Attachments', 'insertMailAttachment', $paramMailAttachment)) {
			$this->state->log('error', 'Failed to assign file to mail for email_id ' . $mail_id . 'into database with error: ' . $response->error,'MailParser');
			return false;
		}
		
		//delete attachment from disk
		if (file_exists($attachment['BodyFile'])) {
		    unlink($attachment['BodyFile']);
		}
		return true;
	}
	
	/**
	 * iterate through all attachments in parsed email array and save them to database
	 */
	function saveAttachemnts(&$data, $mail_id) {
		
		$attachments = $this->getAllAttachments($data);
		
		foreach ($attachments as $attachment) {
			if (!$this->saveAttachment($attachment, $mail_id)) {
				return false;
			}
		}
		return true;
	}
	
	
	/**
	 * assign email to users from email
	 * if user doesn't exist, create it
	 */
	function assignEmailToUsers(&$data, $mail_id, $ticket_id) {
		$response =& $this->getByRef('response');

		$arrUsers = array();
	    $maxFromMailsCount = 1;
        if (strlen($this->state->config->get('maxFromMailsCount'))) {
            $maxFromMailsCount = $this->state->config->get('maxFromMailsCount');
        }		//zisti aky userovia su v maily a s akymi rolami
		if (isset($data['Headers']['from:'])) {
            foreach ($data['Headers']['from:'] as $idx => $to_mail) {
				if ($idx < $maxFromMailsCount && strlen(QUnit_Net_Mail::getEmailAddress($data['Headers']['from:'], $idx))) {
					$arrUsers[] = array('email'=> QUnit_Net_Mail::getEmailAddress($data['Headers']['from:'], $idx),
									'name' => QUnit_Net_Mail::getPersonalName($data['Headers']['from:'], $idx),
									'role' => 'from');
				}
			}
		}

		if (isset($data['Headers']['reply-to:'])) {
			foreach ($data['Headers']['reply-to:'] as $idx => $to_mail) {
				if ($idx < $maxFromMailsCount && strlen(QUnit_Net_Mail::getEmailAddress($data['Headers']['reply-to:'], $idx))) {
					$arrUsers[] = array('email'=> QUnit_Net_Mail::getEmailAddress($data['Headers']['reply-to:'], $idx),
									'name' => QUnit_Net_Mail::getPersonalName($data['Headers']['reply-to:'], $idx),
									'role' => 'reply-to');
				}
			}
		}
		
		
		if (isset($data['Headers']['cc:'])) {
			foreach ($data['Headers']['cc:'] as $idx => $to_mail) {
				if (strlen(QUnit_Net_Mail::getEmailAddress($data['Headers']['cc:'], $idx))) {
					$arrUsers[] = array('email'=> QUnit_Net_Mail::getEmailAddress($data['Headers']['cc:'], $idx),
									'name' => QUnit_Net_Mail::getPersonalName($data['Headers']['cc:'], $idx),
									'role' => 'cc');
				}
			}
		}
		
		if (isset($data['Headers']['to:'])) {
			foreach ($data['Headers']['to:'] as $idx => $to_mail) {
				if (strlen(QUnit_Net_Mail::getEmailAddress($data['Headers']['to:'], $idx))) {
					$arrUsers[] = array('email'=> QUnit_Net_Mail::getEmailAddress($data['Headers']['to:'], $idx),
									'name' => QUnit_Net_Mail::getPersonalName($data['Headers']['to:'], $idx),
									'role' => 'to');
				}
			}
		}
		
		if (isset($data['Headers']['envelope-to:'])) {
			foreach ($data['Headers']['envelope-to:'] as $idx => $to_mail) {
				if (strlen(QUnit_Net_Mail::getEmailAddress($data['Headers']['envelope-to:'], $idx))) {
					$arrUsers[] = array('email'=> QUnit_Net_Mail::getEmailAddress($data['Headers']['envelope-to:'], $idx),
									'name' => QUnit_Net_Mail::getPersonalName($data['Headers']['envelope-to:'], $idx),
									'role' => 'to');
				}
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
		$this->state->log('info', $this->state->lang->get('emailReceived', QUnit_Net_Mail::getEmailAddress($this->getHeaderValue($data, 'from:')), $this->getHeaderValue($data, 'subject:')), 'MailParser', $users);
		return true;
	}
	
	//sometimes is in from address more mail addresses
	function getFromAddressFromHeader(&$data) {
	    $from = $this->getHeaderValue($data, 'from:');
	    if (is_array($from)) {
	        return array_pop($from);
	    }
	    return $from;
	}
	
	/**
	 * Explode mail addresses to arrays
	 */
	function prepareEmailAddresses(&$data) {
		//explode mail parameters to arrays
		if (isset($data['Headers']['envelope-to:'])) {
			$data['Headers']['envelope-to:'] = QUnit_Net_Mail::prepareEmail($this->getHeaderValue($data,'envelope-to:'));
		}
		if (isset($data['Headers']['from:'])) {
			$data['Headers']['from:'] = QUnit_Net_Mail::prepareEmail($this->getFromAddressFromHeader($data));
		}
		if (isset($data['Headers']['reply-to:'])) {
			$data['Headers']['reply-to:'] = QUnit_Net_Mail::prepareEmail($this->getHeaderValue($data, 'reply-to:'));
		}
		if (isset($data['Headers']['to:'])) {
			$data['Headers']['to:'] = QUnit_Net_Mail::prepareEmail($this->getHeaderValue($data,'to:'));
		}
		if (isset($data['Headers']['cc:'])) {
			$data['Headers']['cc:'] = QUnit_Net_Mail::prepareEmail($this->getHeaderValue($data, 'cc:'));
		}
	}

	/**
	 * Save Message to Database from parsed mail array
	 *
	 * @param App_Mail_Pop3Account $pop3Account
	 * @param unknown_type $data
	 * @return unknown
	 */
	function storeMessageToSystem($pop3Account, &$data, $unique_message_id) {
		//create mail
		
		$db = $this->state->get('db');
		$response =& $this->getByRef('response');
		
		$this->prepareEmailAddresses($data);
		
		//load mail parameters
		if (!$paramsMail = $this->fillMailParameters($pop3Account, $data, $unique_message_id)) {
			return false;
		}

		//run rules on this mail
		if (!$this->callService('RulesRunner', 'run', $paramsMail)) {
			$this->state->log('error', 'Failed to execute rules runner with error: ' . $response->error, 'MailParser');
			return false;
		} else {
			//check if mail shouldn't be deleted without storing to system
			if (strlen($paramsMail->get('rule_delete_mail'))) {
				$this->state->log('info', 'Mail with subject ' .
				$paramsMail->get('subject') . ' deleted by rule ' .
				$paramsMail->get('rule_delete_mail'), 'MailParser');
				
				//TODO delete files related to this mail from tmp directory
				$attachments = $this->getAllAttachments($data);
        
                foreach ($attachments as $attachment) {
                    $this->deleteAttachments($attachment);
                }
				
				//finish with processing of email and continue with next email
				return true;
			}
		}
		
		
    	$paramsTicket = $this->createParamsObject();
    	$paramsTicket->set('mail_body', $paramsMail->get('body'));
    	QUnit::includeClass("App_Rule_Rule");
    	 
		//if it is new thread, create new ticket
		if (!strlen($paramsMail->get('ticket_id'))) {
			$is_new_ticket = true;
			//execute rules setup
			App_Rule_Rule::executeRuleSetup($paramsTicket, $paramsMail, $this);
			if ($ticket_id = $this->getNewTicketId($pop3Account, $data, $paramsMail, $paramsTicket)) {
    			$paramsTicket->set('ticket_id', $ticket_id);
				$paramsMail->setField('ticket_id', $ticket_id);
			} else {
				return false;
			}
		} else {
			$is_new_ticket = false;
			$paramsTicket->set('ticket_id', $paramsMail->get('ticket_id'));
			//execute rules setup
			if (App_Rule_Rule::executeRuleSetup($paramsTicket, $paramsMail, $this)) {
				//if something changed, run update
				//update ticket
				if (!$this->callService('Tickets', 'updateTicket', $paramsTicket)) {
					$this->state->log('error', $this->state->lang->get('ticketUpdateFailed') . $response->error, 'MailParser');
					return false;
				}
			}
				
		}

		
		//unset rule setup variables from email parameters
		App_Rule_Rule::cleanupRuleVariables($paramsMail);
		
		// insert mail
		if (!$this->callService('Mails', 'insertMail', $paramsMail)) {
			$this->state->log('error', 'Failed to insert mail into database with error: ' . $response->error, 'MailParser');
			return false;
		} 

		//create attachments
		if (!$this->saveAttachemnts($data, $paramsMail->get('mail_id'))) {
			$this->state->log('error', 'Failed to save attahcements.','MailParser');
			return false;
		}
		
		//assign email to users
		if (!$this->assignEmailToUsers($data, $paramsMail->get('mail_id'), $paramsMail->get('ticket_id'))) {
			$this->state->log('error', 'Failed to assign users to Mail.','MailParser');
			return false;
		}
		
		if ($is_new_ticket && !$this->isAutoSubmittedMail($data)) {
			if (!$this->callService('Queues', 'notifyUserAboutNewTicket', $paramsMail)) {
				$this->state->log('error', 'Failed to notify user about new ticket: ' . $response->error, 'MailParser');
				return false;
			}
		}
		
		$forwarder = QUnit::newObj('App_Service_MailGateway');
		$forwarder->state = $this->state;
		$forwarder->response = $response;
		$forwarder->forwardMail($paramsMail->get('ticket_id'), $paramsMail->get('mail_id'));
		
		return true;
	}

	/**
	 * Checks if email was not received from any type of autoresponder
	 * To emails generated by autoresponders shouldn't be sent next email automatically
	 */
	function isAutoSubmittedMail(&$data) {
		$autoresponded = trim($this->getHeaderValue($data,'auto-submitted:'));
		$xautoresponded = trim($this->getHeaderValue($data,'x-autorespond:'));
		$return_path = trim($this->getHeaderValue($data,'return-path:'));
		$precedence = trim($this->getHeaderValue($data,'precedence:'));
		//$is_precedece = $precedence == 'bulk' || $precedence == 'junk' || $precedence = 'list';
		$is_precedece = $precedence == 'bulk' || $precedence == 'junk';
		$vacation = trim($this->getHeaderValue($data,'x-mailer:')) == 'vacation'; 
		return strlen($xautoresponded) || (strlen($autoresponded) && $autoresponded != "no") || $return_path == '<>' || $is_precedece || $vacation;
	}
	
	/**
	 * Get part of email from server
	 * returns status if is reached end of data
	 */
	function getMessagePart(&$message_data) {
		if(($error=$this->pop3->GetMessage(65535, $message_data, $endOfMessage))=="") {
			$this->state->log('debug', 'Loaded data', 'MailParser');
			return $endOfMessage;
		} else {
			$this->state->log('error', 'Failed to get data from message with error message: ' . $error, 'MailParser');
			return 1;
		}
	}
	
	
	/**
	 * Delete defined message in pop3 account
	 *
	 * @param unknown_type $message
	 */
	function deleteMessage($message, $unique_message_id) {
		if(($error=$this->pop3->DeleteMessage($message))=="") {
			return true;
		} else {
			$this->state->log('error', 'Failed to mark message ' . $unique_message_id . ' as deleted with error message: ' . $error, 'MailParser');
			return false;
		}
	}
}
?>
