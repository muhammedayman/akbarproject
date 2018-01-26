<?php
/**
*   Handler class for Queues
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Queues extends QUnit_Rpc_Service {

	function initMethod($params) {
		$method = $params->get('method');

		switch($method) {
			case 'getQueueList':
				return true;
			case 'getPublicQueues':
			case 'getQueueList':
				return $this->callService('Users', 'authenticate', $params);
				break;
			case 'deleteQueue':
			case 'getQueue':
			case 'setDefaultQueue':
			case 'insertQueue':
			case 'updateQueue':
			case 'getQueueAgentsList':
				return $this->callService('Users', 'authenticateAdmin', $params);
				break;
			default:
				return false;
				break;
		}
	}
	
	function existQueue($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');
		
		$columns = "queue_id";
		$from = "queues";
		$where = "";

		if($id = $params->get('queue_id')) {
			$where .= "queue_id = '".$db->escapeString($id) . "'";
		}
		
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('where', $where);
		$params->set('table', 'queues');
		
		if ($this->callService('SqlTable', 'select', $params)) {
			$result = & $response->getByRef('result');
			if ($result['count'] > 0) {
				return true;
			}
		}
		return false;
	}
	
	function getPublicQueues($params){
		$params->set('public', 'y');
		return $this->getQueueList($params);
	}
	
	function getQueueList($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');
		
		$columns = "queue_id, name, queue_email, answer_time, is_default, public, opened_for_users, autorespond_nt, queue_signature";
		$from = "queues";
		$where = "1";

		if($id = $params->get('queue_id')) {
			$where .= " and queue_id = '".$db->escapeString($id) . "'";
		}
		if($params->get('is_default') == 'y') {
			$where .= " and is_default = 'y'";
		}
		if($params->get('public')== 'y') {
			$where .= " and public = 'y'";
		}
		if($params->get('public')== 'n') {
			$where .= " and public <> 'y'";
		}
		
		//user can see just queues opened for simple users and not logged in users
		if($params->get('opened_for_users')== 'y' || 
			($session->getVar('userType') != 'a' && 
			$session->getVar('userType') != 'g') ) {
			
				$where .= " and opened_for_users = 'y'";
				
		}
		
		
		
		//if it is not admin, display just public entries or queues where agent is assigned
		if ($session->getVar('userType') == 'g' && ($this->state->config->get('agentCanSeeJustOwnQueues') == 'y' || 
		$params->get('granted_righs') == 'y')) {
			$where .= " and (public = 'y' OR (queue_id IN (
						SELECT queue_id from queue_agents WHERE user_id='" . 
			$db->escapeString($session->getVar('userId')) . "')))";
		}

		if ($params->get('user_id')) {
			$where .= " and queue_id IN (SELECT queue_id from queue_agents WHERE user_id='" . 
						$db->escapeString($params->get('user_id')) . "')";
		}
		
		
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('where', $where);
		$params->set('table', 'queues');
		return $this->callService('SqlTable', 'select', $params);
	}
	
	function getQueueAgentsList($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');

		$columns = "user_id";
		$from = "queue_agents";
		$where = "1";

		if($id = $params->get('queue_id')) {
			$where .= " and queue_id = '".$db->escapeString($id)."'";
		}
			
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('where', $where);
		$params->set('table', $from);
		return $this->callService('SqlTable', 'select', $params);
	}
	
	function getQueue($params) {
		$response =& $this->getByRef('response');
		if(!$params->check(array('queue_id'))) {
			$response->set('error', $this->state->lang->get('noIdProvided'));
			return false;
		}
		
		//retrieve list of agents assignet to queue
		$agent_ids = '';
		if ($retAgents = $this->callService('Queues', 'getQueueAgentsList', $params)) {
			$result = & $response->getByRef('result');
			if ($result['count'] > 0) {
				$rows = $result['rs']->getRows($result['md']);
				foreach ($rows as $row) {
					$agent_ids .= (strlen($agent_ids) ? '|' : '') . $row['user_id'];
				}
	     	}			
		} else {
    		$response->set('error', $this->state->lang->get('failedSelectAssignedAgentsToQueue'));
    		return false;
		}
		
		$ret = $this->getQueueListAllFields($params);
		
		//add user_ids to result
		$rs = $response->getResultVar('rs');
		$md = $response->getResultVar('md');
		$rs->rows[0][] = $agent_ids;
		$md->addColumn('agent_ids', 'string');
		$response->setResultVar('rs', $rs);
		$response->setResultVar('md', $md);
		return $ret;
	}
	
	function getQueueListAllFields($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');

		$columns = "*";
		$from = "queues";
		$where = "1";

		if($id = $params->get('queue_id')) {
			$where .= " and queue_id = '".$db->escapeString($id)."'";
		}
		if($params->get('is_default') == 'y') {
			$where .= " and is_default = 'y'";
		}
		
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('where', $where);
		$params->set('table', 'queues');
		return $this->callService('SqlTable', 'select', $params);
	}
	
    /**
     * Returns default queue
     */
    function getDefaultQueue($params) {
    	$params->set('is_default', 'y');
    	return $this->getQueueListAllFields($params);
    }

    
    /**
     * Get queue by email
     * in parameters should be filled in queue_email parameter
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function getQueueByEmail($params) {
    	$db =& $this->state->getByRef('db');
    	$response =& $this->getByRef('response');

    	$columns = "*";
    	$from = "queues";
    	$where = "queue_email IS NOT NULL AND queue_email='" . $db->escapeString($params->get('queue_email')) . "'";

    	$params->set('columns', $columns);
    	$params->set('from', $from);
    	$params->set('where', $where);
    	$params->set('table', 'queues');
    	return $this->callService('SqlTable', 'select', $params);
    }
    
    /**
     * Delete queue
     *
     * @param QUnit_Rpc_Params $params
     */
    function deleteQueue($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');
    	
    	$ids = explode('|',$params->get('queue_id'));
    	
    	if ($this->isDemoMode('Queues', $ids)) {
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
    	
    	if(!$params->check(array('queue_id')) || !strlen($where_ids)) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}


    	//check if queue is not default queue
    	$sql = "SELECT * FROM queues WHERE is_default='y' AND queue_id IN (" . $where_ids . ")";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(!$this->_checkDbError($sth)) {
    		$response->set('error', $this->state->lang->get('failedCheckOfDefaultQueue') . $params->get('queue_id'));
    		$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Queue');
    		return false;
    	} else {
    		if ($sth->rowCount() > 0) {
    			$response->set('error', $this->state->lang->get('cantDeleteDefaultQueue'));
    			$this->state->log('error', $response->get('error'), 'Queue');
    			return false;
    		}
    	}
    	
    	//unsetnut queue_id in tickets
    	$sql = "UPDATE tickets SET queue_id=NULL where queue_id IN (" . $where_ids . ")";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(!$this->_checkDbError($sth)) {
    		$response->set('error', $this->state->lang->get('failedDeleteTicketQueueAssignment') . $params->get('queue_id'));
    		$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Queue');
    		return false;
    	}

    	$sql = "DELETE FROM queue_agents where queue_id IN (" . $where_ids . ")";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(!$this->_checkDbError($sth)) {
    		$response->set('error', $this->state->lang->get('failedDeleteAgentQueueAssignment') . $params->get('queue_id'));
    		$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Queue');
    		return false;
    	}

    	$sql = "DELETE FROM mail_templates where queue_id IN (" . $where_ids . ")";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(!$this->_checkDbError($sth)) {
    		$response->set('error', $sth->get('errorMessage'));
    		$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Queue');
    		return false;
    	}
    	
    	
    	$sql = "DELETE FROM signatures where queue_id IN (" . $where_ids . ")";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(!$this->_checkDbError($sth)) {
    		$response->set('error', $this->state->lang->get('failedDeleteAgentQueueAssignment') . $params->get('queue_id'));
    		$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Queue');
    		return false;
    	}
    	
    	$params->set('table', 'queues');
    	$params->set('where', "queue_id IN (" . $where_ids . ")");
    	if ($ret = $this->callService('SqlTable', 'delete', $params)) {
    		$this->state->log('info', 'Deleted queue ' . $params->get('name'), 'Queue');
    	} else {
    		$this->state->log('error', 'Failed to delete queue ' . $params->get('name'), 'Queue');
    	}
    	return $ret;

    }

    /**
     * setDefaultQueue
     *
     * @param QUnit_Rpc_Params $params
     */
    function setDefaultQueue($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');
    	
    	if ($this->isDemoMode()) {
    		$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));
    		return false;
    	}
    	
    	if(!$params->check(array('queue_id'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}

    	//unsetnut is_default
    	$sql = "UPDATE queues SET is_default='n' WHERE is_default='y'";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(!$this->_checkDbError($sth)) {
    		$response->set('error', $this->state->lang->get('failedUnsetDefaultQueue'));
    		$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Queue');
    		return false;
    	}
    	
    	$params->set('table', 'queues');
    	$params->setField('is_default', 'y');

    	$params->set('where', "queue_id = '" . $db->escapeString($params->get('queue_id')) . "'");
    	if ($ret = $this->callService('SqlTable', 'update', $params)) {
    		$this->state->log('info', 'Set default queue ' . $params->get('name'), 'Queue');
    	} else {
    		$this->state->log('error', 'Failed to setup default queue ' . $params->get('name'), 'Queue');
    	}
    	return $ret;
    }
    
    /**
     * Insert new queue
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function insertQueue(&$params) {
    	$db = $this->state->get('db');
    	$response =& $this->getByRef('response');

    	//check of mandatory fields
    	if(!$params->checkFields(array('name', 'answer_time'))) {
    		$response->set('error', $this->state->lang->get('queueMandatoryFields'));
    		return false;
    	}
    	 
    	 
    	$params->set('table', 'queues');

    	if (!strlen($params->getField('queue_id'))) {
    		$params->setField('queue_id', md5(uniqid(rand(), true)));
    	}

    	
    	if (!strlen($params->getField('is_default'))) {
    		$params->setField('is_default', 'n');
    	}
    	if (!strlen($params->getField('public'))) {
    		$params->setField('public', 'n');
    	}
    	if (!strlen($params->getField('opened_for_users'))) {
    		$params->setField('opened_for_users', 'n');
    	}
    	if (!strlen($params->getField('autorespond_nt'))) {
    		$params->setField('autorespond_nt', 'n');
    	}
    	
    	if (!strlen($params->getField('queue_signature'))) {
    		$params->setField('queue_signature', '');
    	}
    	
    	if ($params->getField('autorespond_nt') == 'y' && !strlen($params->getField('autorespond_nt_body'))) {
    			$response->set('error', $this->state->lang->get('mailBodyCanNotBeEmpty'));
    			return false;
    	}

    	if ($params->getField('is_default') == 'y') {
	    	if ($this->isDemoMode()) {
	    		$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));
	    		return false;
	    	}
    		//unsetnut is_default for other queues
    		$sql = "UPDATE queues SET is_default='n' WHERE is_default='y'";
    		$sth = $db->execute($sql);
    		$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    		if(!$this->_checkDbError($sth)) {
    			$response->set('error', $this->state->lang->get('failedUnsetDefaultQueue'));
    			$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Queue');
    			return false;
    		}
    	}
    	 
    	if ($ret = $this->callService('SqlTable', 'insert', $params)) {
    		//assign agents to Queue
    		$this->assignAgentsToQueue($params->get('queue_id'), $params->get('user_id'));
    	} else {
    		$this->state->log('error', 'Failed to insert queue ' . $params->get('name'), 'Queue');
    	}
    	return $ret;
    }

    function assignAgentsToQueue($queue_id, $user_ids) {
		$db = $this->state->get('db');
    	$response =& $this->getByRef('response');
		
    	//delete all past assignments
   		$sql = "DELETE FROM queue_agents WHERE queue_id='" . $db->escapeString($queue_id) . "'";
   		$sth = $db->execute($sql);
   		$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
   		if(!$this->_checkDbError($sth)) {
   			$response->set('error', $this->state->lang->get('failedDeleteOldAssignments'));
   			$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Queue');
   			return false;
   		}
    	
		$sql = "INSERT IGNORE INTO queue_agents (queue_id, user_id)
				VALUES ";
		$values = '';
		if (is_array($user_ids)) {
			foreach ($user_ids as $user_id) {
				$user_id = addslashes(trim($user_id));
				$values .= (strlen($values) ? ',' : '') . "('" . addslashes($queue_id) . "', '" . $user_id . "')";
			}
		} elseif (strlen($user_ids)) {
			$user_id = addslashes(trim($user_ids));
			$values = "('" . addslashes($queue_id) . "', '" . $user_id . "')";
		} else {
			return true;
		}
		if (strlen(trim($values))) {
			$sql .= $values;

			$sth = $db->execute($sql);
			$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
			if(!$this->_checkDbError($sth)) {
	   			$response->set('error', $this->state->lang->get('failedCreateNewAssignments'));
   				$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Queue');
				return false;
			}
		}
		return true;
	}
    
    
    
    /**
     * Update Queue
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function updateQueue($params) {
    	$response =& $this->getByRef('response');
    	$queue_id = $params->get('queue_id');
    	
    	if ($this->isDemoMode('Queues', $queue_id)) {
        	$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));     	
			return false;
		}
    	
    	
    	if(!$params->check(array('queue_id'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
    	if(!$params->checkFields(array('name', 'answer_time'))) {
    		$response->set('error', $this->state->lang->get('queueMandatoryFields'));
    		return false;
    	}
    	$db =& $this->state->getByRef('db');

    	//TODO treba pridat check, aby sa nedalo updatom zrusit default queue !!!
    	
    	if (!strlen($params->getField('is_default'))) {
    		$params->setField('is_default', 'n');
    	}
    	if (!strlen($params->getField('autorespond_nt'))) {
    		$params->setField('autorespond_nt', 'n');
    	}
    	if (!strlen($params->getField('public'))) {
    		$params->setField('public', 'n');
    	}
    	if (!strlen($params->getField('opened_for_users'))) {
    		$params->setField('opened_for_users', 'n');
    	}
    	if ($params->getField('autorespond_nt') == 'y' && !strlen($params->getField('autorespond_nt_body'))) {
    			$response->set('error', $this->state->lang->get('mailBodyCanNotBeEmpty'));
    			return false;
    	}
    	
    	if ($params->getField('is_default') == 'y') {
	    	if ($this->isDemoMode()) {
	    		$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));
	    		return false;
	    	}
    		//unsetnut is_default for other queues
    		$sql = "UPDATE queues SET is_default='n' WHERE is_default='y'";
    		$sth = $db->execute($sql);
    		$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    		if(!$this->_checkDbError($sth)) {
    			$response->set('error', $this->state->lang->get('failedUnsetDefaultQueue'));
    			$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Queue');
    			return false;
    		}
    	}
    	
    	$params->set('table', 'queues');
    	$params->set('where', "queue_id = '".$db->escapeString($params->get('queue_id'))."'");
    	if ($ret = $this->callService('SqlTable', 'update', $params)) {
    		//assign agents to Queue
    		$this->assignAgentsToQueue($queue_id, $params->get('user_id'));
    	}
    	return $ret;
    }
    
    function notifyUserAboutNewTicket($params) {
    	$response =& $this->getByRef('response');
    	if (!$params->check(array('ticket_id'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
    	 
    	//load ticket
    	$paramTicket = $this->createParamsObject();
		$paramTicket->set('ticket_id', $params->get('ticket_id'));
		if (!($ticket = $this->callService('Tickets', 'loadTicket', $paramTicket))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
		}
   
		//load queue
		$paramQueue = $this->createParamsObject();
		$paramQueue->set('queue_id', $ticket['queue_id']);
		if (!($queue = $this->callService('Queues', 'loadQueue', $paramQueue))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
		}

		if ($queue['autorespond_nt'] == 'y') {
		
	    	//load user
			$paramUser = $this->createParamsObject();
			$paramUser->set('user_id', $ticket['customer_id']);
			$paramUser->set('hash', true);
			if (!($user = $this->callService('Users', 'loadUser', $paramUser))) {
	    		$response->set('error', $this->state->lang->get('noIdProvided'));
	    		return false;
			}
	
			//check if email quality is ok
			if ($user['email_quality'] < 2 && $user['email_quality'] != 0) {
	    		$response->set('error', $this->state->lang->get('emailNotPassedEmailQuality'));
				return false;
			}
			
	    	//load user
			if (strlen($ticket['agent_owner_id'])) {
				$paramUser = $this->createParamsObject();
				$paramUser->set('user_id', $ticket['agent_owner_id']);
				if (!($owner = $this->callService('Users', 'loadUser', $paramUser))) {
				}
			} else {
				$owner = array('name' => '');
			}

			//send email
			$paramNotification = $this->createParamsObject();
			$paramNotification->set('queue_email', $queue['queue_email']);
			$paramNotification->set('template_text', $queue['autorespond_nt_body']);
			$paramNotification->set('ticket_id', $ticket['subject_ticket_id']);
			$paramNotification->set('t_id', $ticket['ticket_id']);
			$paramNotification->set('ticket_subject', $ticket['first_subject']);
			$paramNotification->set('Thread-Index', $ticket['thread_id']);
			$paramNotification->set('Message-ID', md5(uniqid(rand(), true)));
			$paramNotification->set('customer_name', $user['name']);
			$paramNotification->set('customer_email', $user['email']);
			$paramNotification->set('ticket_owner', $owner['name']);
			$paramNotification->set('answer_time', $queue['answer_time']);
			$paramNotification->set('queue_id', $ticket['queue_id']);
			
			//nahrat mailbody mailu
			$paramNotification->set('mail_body', str_replace("\n", '<br/>', $params->get('body')));
			
			//just user can be logged in automatically
			$hashlogin = ($user['user_type'] == 'u' ? ('&hash='. $user['hash']) : '');
			
			
			if (strpos($queue['autorespond_nt_body'], '${knowledge_suggestions}') !== false && 
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
			$paramNotification->set('ticket_url', $this->state->config->get('applicationURL') . 'client/index.php#tid=' . $ticket['subject_ticket_id'] . $hashlogin);
			$paramNotification->set('ticket_link', '<a href="' . $paramNotification->get('ticket_url') . '">' . $ticket['subject_ticket_id'] . '</a>');
			$paramNotification->set('Auto-Submitted', 'auto-replied');
			
			$paramNotification->set('to', $user['email']);
			
			if ($this->state->config->get('hideTicketIdFromSubject') == 'y') {
				$paramNotification->set('subject', $queue['autorespond_nt_subject']);
			} else {
				$paramNotification->set('subject', '[' . $queue['ticket_id_prefix'] . '#' . $ticket['subject_ticket_id'] . '] '  . $queue['autorespond_nt_subject']);
			}
			
			if (!($ret = $this->callService('SendMail', 'send', $paramNotification))) {
	    		return false;
			}
		}		
		return true;
    }
    
    /**
     * Load ticket row and store it into array 
     */
    function loadQueue($params) {
    	$response =& $this->getByRef('response');
   	   	$db = $this->state->get('db'); 
        $queue = false;
    	//load ticket
    	if ($params->check(array('queue_id'))) {
    		$paramsQueue = $this->createParamsObject(); 
    		$paramsQueue->set('queue_id', $params->get('queue_id'));
    		if ($ret = $this->callService('Queues', 'getQueueListAllFields', $paramsQueue)) {
    			$res = & $response->getByRef('result');
    			if ($res['count'] > 0) {
					$queue = $res['rs']->getRows($res['md']);
    				$queue = $queue[0];
    			}
    		}
    	}
    	return $queue;
    }
    
    
}
?>