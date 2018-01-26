<?php
/**
*   Handler class for Mail Accounts
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_MailAccounts extends QUnit_Rpc_Service {

    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
			case 'deleteMailAccount':
			case 'insertMailAccount':
			case 'updateMailAccount':
			case 'setAsDefaultMailAccount':
			case 'getMailAccount':
				return $this->callService('Users', 'authenticateAdmin', $params);
				break;
			case 'getMailAccountsList':
				return $this->callService('Users', 'authenticateAgent', $params);
				break;
			default:
                return false;
                break;
        }
    }
    
    function getMailAccountsList($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $columns = "account_id, account_name, account_email, from_name, from_name_format, is_default, public, last_processing";
        $from = "mail_accounts";
        $where = "1";
        if($id = $params->get('account_id')) {
            $where .= " and account_id = '".$db->escapeString($id)."'";
        }
        
        if($id = $params->get('is_default')) {
            $where .= " and is_default = '".$db->escapeString($id)."'";
        }
        
		if ($params->get('user_id')) {
			$where .= " and (public='y' OR account_id IN (SELECT account_id from agent_accounts WHERE user_id='" . 
						$db->escapeString($params->get('user_id')) . "'))";
		}
        
        
        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'mail_accounts');
        return $this->callService('SqlTable', 'select', $params);
    }

    function getMailAccountsMailsArray() {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $columns = "account_id, account_email";
        $from = "mail_accounts";
        $where = "1";
        
        $params = $this->createParamsObject();
        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'mail_accounts');
        if ($this->callService('SqlTable', 'select', $params)) {
			$res = & $response->getByRef('result');
			if ($res['count'] > 0) {
				return $res['rs']->getRows($res['md']);
			}
        }
        return array();
    }
    
    
    /**
     * Returns default account
     */
    function getDefaultMailAccount($params) {
    	$params->set('is_default', 'y');
    	return $this->getMailAccountsListAllFields($params);
    }

	function getAccountAgentsList($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');

		$columns = "user_id";
		$from = "agent_accounts";
		$where = "1";

		if($id = $params->get('account_id')) {
			$where .= " and account_id = '".$db->escapeString($id)."'";
		}
			
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('where', $where);
		$params->set('table', $from);
		return $this->callService('SqlTable', 'select', $params);
	}
    
    function getMailAccount($params) {
    	$db =& $this->state->getByRef('db');
    	$response =& $this->getByRef('response');
    	 
    	if(!$params->check(array('account_id'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
    	
		//retrieve list of agents assignet to queue
		$agent_ids = '';
		if ($retAgents = $this->callService('MailAccounts', 'getAccountAgentsList', $params)) {
			$result = & $response->getByRef('result');
			if ($result['count'] > 0) {
				$rows = $result['rs']->getRows($result['md']);
				foreach ($rows as $row) {
					$agent_ids .= (strlen($agent_ids) ? '|' : '') . $row['user_id'];
				}
	     	}			
		} else {
    		$response->set('error', $this->state->lang->get('failedSelectAssignedAgentsToAccount'));
    		return false;
		}
    	
    	$columns = "account_id, account_name, account_email, from_name, from_name_format,
    				pop3_server, pop3_port, pop3_ssl, smtp_tls, pop3_username,
    				use_smtp, smtp_server, smtp_port, smtp_ssl, smtp_require_auth, smtp_username,
    				delete_messages, last_unique_msg_id, is_default, last_msg_received, public";
    	if ($params->get('password')) {
    		$columns .= ', pop3_password, smtp_password';
    	}
    	$from = "mail_accounts";
    	$where = "1";
    	if($id = $params->get('account_id')) {
    		$where .= " and account_id = '".$db->escapeString($id)."'";
    	}
    	if($id = $params->get('is_default')) {
    		$where .= " and is_default = '".$db->escapeString($id)."'";
    	}

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'mail_accounts');
        $ret = $this->callService('SqlTable', 'select', $params);
        
		//add user_ids to result
		$rs = $response->getResultVar('rs');
		$md = $response->getResultVar('md');
		$rs->rows[0][] = $agent_ids;
		$md->addColumn('agent_ids', 'string');
		$response->setResultVar('rs', $rs);
		$response->setResultVar('md', $md);
		
		return $ret;
        
    }
    
    
    
    function getMailAccountsListAllFields($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $columns = "*";
        $from = "mail_accounts";
        $where = "1";
        if($id = $params->get('account_id')) {
            $where .= " and account_id = '".$db->escapeString($id)."'";
        }
        if($id = $params->get('account_email')) {
        	if (strlen($id)) {
            	$where .= " and account_email = '".$db->escapeString($id)."'";
        	}
        }
        if($id = $params->get('is_default')) {
            $where .= " and is_default = '".$db->escapeString($id)."'";
        }
        
        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'mail_accounts');
        return $this->callService('SqlTable', 'select', $params);
    }
    
    
    function setLastParsedMessage($params) {
        $db =& $this->state->getByRef('db');
    	$response =& $this->getByRef('response');

        if(!$params->check(array('account_id'))) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }
        if(!$params->check(array('unique_msg_id'))) {
        	$response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }
        
        $params->setField('last_unique_msg_id', $params->get('unique_msg_id'));
        $params->setField('last_msg_received', $db->getDateString());
        
        $params->set('table', 'mail_accounts');
        $params->set('where', "account_id = '".$db->escapeString($params->get('account_id'))."'");
        return $this->callService('SqlTable', 'update', $params);
    }

    /**
     *  deleteMailAccount
     *
     *  @param string table
     *  @param string id id of task
     *  @return boolean
     */
    function deleteMailAccount($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');		
    	
    	$ids = explode('|',$params->get('account_id'));
    	
    	if ($this->isDemoMode('MailAccounts', $ids)) {
        	$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));     	
			return false;
		}
    	
    	$where_ids = '';
    	foreach ($ids as $id) {
    		if (strlen(trim($id))) {
    			$where_ids .= (strlen($where_ids) ? ', ': '');
    			$where_ids .= "'" . $db->escapeString(trim($id)) . "'";
    		}
    	}
    	 
    	if(!$params->check(array('account_id')) || !strlen($where_ids)) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
    	
    	//check if queue is not default account
    	$sql = "SELECT * FROM mail_accounts WHERE is_default='y' AND account_id IN (" . $where_ids . ")";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(!$this->_checkDbError($sth)) {
    		$response->set('error', $this->state->lang->get('failedCheckOfDefaultMailAccount') . $params->get('account_id'));
    		$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'MailAccount');
    		return false;
    	} else {
    		if ($sth->rowCount() > 0) {
    			$response->set('error', $this->state->lang->get('cantDeleteDefaultMailAccount'));
    			$this->state->log('error', $response->get('error'), 'MailAccount');
    			return false;
    		}
    	}
    	
    	//unsetnut account_id in mails
    	$sql = "UPDATE mails SET account_id=NULL where account_id IN (" . $where_ids . ")";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(!$this->_checkDbError($sth)) {
    		$response->set('error', $this->state->lang->get('failedUnsetOfMailAccount'));
    		$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Queue');
    		return false;
    	}
    	
    	//unsetnut account_id in notifications
    	$sql = "UPDATE notifications SET account_id=NULL where account_id IN (" . $where_ids . ")";
    	$sth = $db->execute($sql);
    	$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    	if(!$this->_checkDbError($sth)) {
    		$response->set('error', $this->state->lang->get('failedUnsetOfMailAccount'));
    		$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Queue');
    		return false;
    	}
    	
    	
    	$params->set('table', 'mail_accounts');
    	$params->set('where', "account_id IN (" . $where_ids . ")");
    	if ($ret = $this->callService('SqlTable', 'delete', $params)) {
    		$this->state->log('info', $this->state->lang->get('deletedAccounts') . $params->get('account_name'), 'MailAccount');
    	} else {
    		$this->state->log('error', $this->state->lang->get('deleteAccountsFailed') . $params->get('account_name'), 'MailAccount');
    	}
	}    
    	
	/**
	 * Insert mail account
	 *
	 * @param QUnit_Rpc_Params $params
	 */
    function insertMailAccount($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');
    	
        if(!$params->checkFields(array('account_name', 'account_email'))) {
        	$response->set('error', $this->state->lang->get('failedInsertMailAcountMissingMandatoryFields'));
        	return false;
        }
    	
        $concurentMailAccounts = 0;
        if ($concurentMailAccounts > 0) {
        	$paramsCount = $this->createParamsObject();
        	if ($this->getMailAccountsList($paramsCount)) {
				$res = & $response->getByRef('result');
		    	if ($res['count'] >= $concurentMailAccounts) {
		        	$response->set('error', "Your version of SupportCenter doesn't allow to have more than $concurentMailAccounts Mail Accounts. Please upgrade your license.");
		        	return false;
		    	}
        	}
        }
        
    	$params->set('table', 'mail_accounts');

    	if (!strlen($params->getField(account_id))) {
    		$params->setField('account_id', md5(uniqid(rand(), true)));
    	}
     	
    	if (!strlen($params->getField('delete_messages'))) {
    		$params->setField('delete_messages', 'n');
    	}

    	if (!strlen($params->getField('from_name_format'))) {
    		$params->setField('from_name_format', 'a');
    	}
    	
    	if ($params->getField('smtp_tls') != 'y') {
    		$params->setField('smtp_tls', 'n');
    	}
    	
    	if (!strlen($params->getField('pop3_port'))) {
    		$params->setField('pop3_port', '110');
    	}
    	if (!strlen($params->getField('smtp_port'))) {
    		$params->setField('smtp_port', '25');
    	}
    	 
    	if (!strlen($params->getField('use_smtp'))) {
    		$params->setField('use_smtp', 'n');
    	}
    	
    	if (!strlen($params->getField('public'))) {
    		$params->setField('public', 'n');
    	}

    	$params->setField('last_msg_received', '0000-00-00 00:00:00');
    	
    	if ($params->getField('is_default') == 'y') {
	    	if ($this->isDemoMode()) {
	    		$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));
	    		return false;
	    	}
    		//unsetnut is_default for other queues
    		$sql = "UPDATE mail_accounts SET is_default='n' WHERE is_default='y'";
    		$sth = $db->execute($sql);
    		$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    		if(!$this->_checkDbError($sth)) {
    			$response->set('error', $this->state->lang->get('failedUnsetDefaultMailAccount'));
    			$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'MailAccount');
    			return false;
    		}
    	} else {
    		$params->setField('is_default', 'n');
    		//check how many default accounts we have
        	$paramsCount = $this->createParamsObject();
    		$paramsCount->set('is_default', 'y');
        	if ($this->getMailAccountsList($paramsCount)) {
				$res = & $response->getByRef('result');
		    	if ($res['count'] == 0) {
    				$params->setField('is_default', 'y');
		    	}
        	}
    		
    	}
    	
    	if ($ret = $this->callService('SqlTable', 'insert', $params)) {
    		$this->state->log('notice', $this->state->lang->get('createdMailAccount') . $params->get('account_name'), 'MailAccount');
   			//assign agents to account
    		$this->assignAgentsToAccount($params->get('account_id'), $params->get('user_id'));
    	} else {
    		$this->state->log('error', $this->state->lang->get('createMailAccountFailed') . $params->get('account_name'), 'MailAccount');
    	}
    	return $ret;
    }

    
    function assignAgentsToAccount($account_id, $user_ids) {
		$db = $this->state->get('db');
    	$response =& $this->getByRef('response');
		
    	//delete all past assignments
   		$sql = "DELETE FROM agent_accounts WHERE account_id='" . $db->escapeString($account_id) . "'";
   		$sth = $db->execute($sql);
   		$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
   		if(!$this->_checkDbError($sth)) {
   			$response->set('error', $this->state->lang->get('failedDeleteOldAssignments'));
   			$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'MailAccount');
   			return false;
   		}
    	
		$sql = "INSERT IGNORE INTO agent_accounts (account_id, user_id)
				VALUES ";
		$values = '';
		if (is_array($user_ids)) {
			foreach ($user_ids as $user_id) {
				$user_id = addslashes(trim($user_id));
				$values .= (strlen($values) ? ',' : '') . "('" . addslashes($account_id) . "', '" . $user_id . "')";
			}
		} elseif (strlen($user_ids)) {
			$user_id = addslashes(trim($user_ids));
			$values = "('" . addslashes($account_id) . "', '" . $user_id . "')";
		} else {
			return true;
		}
		if (strlen(trim($values))) {
			$sql .= $values;

			$sth = $db->execute($sql);
			$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
			if(!$this->_checkDbError($sth)) {
	   			$response->set('error', $this->state->lang->get('failedCreateNewAssignments'));
   				$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'MailAccount');
				return false;
			}
		}
		return true;
	}
    
    
    /**
     * Update Mail Account
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function updateMailAccount($params) {
    	$response =& $this->getByRef('response');
    	$db =& $this->state->getByRef('db');
    	
    	if(!$params->check(array('account_id'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}

    	if ($this->isDemoMode('MailAccounts', $params->get('account_id'))) {
    		$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));
    		return false;
    	}
    	 
    	if(!$params->checkFields(array('account_name', 'account_email'))) {
    		$response->set('error', $this->state->lang->get('failedInsertMailAccountMissingEmail'));
        	return false;
        }
    	if (!strlen($params->getField('delete_messages'))) {
    		$params->setField('delete_messages', 'n');
    	}
    	
    	if (!strlen($params->getField('use_smtp'))) {
    		$params->setField('use_smtp', 'n');
    	}
    	if (!strlen($params->getField('public'))) {
    		$params->setField('public', 'n');
    	}
    	
    	
    	if ($params->getField('is_default') == 'y') {
	    	if ($this->isDemoMode()) {
	    		$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));
	    		return false;
	    	}
    		
    		//unsetnut is_default for other mail accounts
    		$sql = "UPDATE mail_accounts SET is_default='n' WHERE is_default='y'";
    		$sth = $db->execute($sql);
    		$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    		if(!$this->_checkDbError($sth)) {
    			$response->set('error', $this->state->lang->get('failedUnsetDefaultMailAccount'));
    			$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'MailAccount');
    			return false;
    		}
    	} else {
    		$params->setField('is_default', 'n');
    	}
    	
    	if (!strlen($params->getField('pop3_password'))) {
    		$params->unsetField('pop3_password');
    	}
    	if (!strlen($params->getField('smtp_password'))) {
    		$params->unsetField('smtp_password');
    	}
    	
    	
    	$params->set('table', 'mail_accounts');
    	$params->set('where', "account_id = '".$db->escapeString($params->get('account_id'))."'");
    	if ($ret = $this->callService('SqlTable', 'update', $params)) {
    		//assign agents to account
    		$this->assignAgentsToAccount($params->get('account_id'), $params->get('user_id'));
    	}
    	return $ret;
    }


    function setAsDefaultMailAccount($params) {
    	$response =& $this->getByRef('response');
    	$db =& $this->state->getByRef('db');
    	if ($this->isDemoMode()) {
    		$response->set('error', $this->state->lang->get('notAlloweInDemoMode'));
    		return false;
    	}
    	
    	if(!$params->check(array('account_id'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
    	
    	if ($params->getField('is_default') == 'y') {
    		//unsetnut is_default for other accounts
    		$sql = "UPDATE mail_accounts SET is_default='n' WHERE is_default='y'";
    		$sth = $db->execute($sql);
    		$this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
    		if(!$this->_checkDbError($sth)) {
    			$response->set('error', $this->state->lang->get('failedUnsetDefaultMailAccount'));
    			$this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'MailAccount');
    			return false;
    		}
    	} else {
    		$params->setField('is_default', 'n');
    	}
    	 
    	 
    	$params->set('table', 'mail_accounts');
    	$params->set('where', "account_id = '".$db->escapeString($params->get('account_id'))."'");
    	return $this->callService('SqlTable', 'update', $params);
    }

    /**
     * Release mail account
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function releaseMailAccount($params) {
    	$response =& $this->getByRef('response');
    	$db =& $this->state->getByRef('db');
    	 
    	if(!$params->check(array('account_id'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
    	
    	$params->setField('last_msg_received', '0000-00-00 00:00:00');
    	$params->setField('last_processing', $db->getDateString());
    	
    	$params->set('table', 'mail_accounts');
    	$params->set('where', "account_id = '".$db->escapeString($params->get('account_id'))."'");
    	return $this->callService('SqlTable', 'update', $params);
    }
    
    
}
?>