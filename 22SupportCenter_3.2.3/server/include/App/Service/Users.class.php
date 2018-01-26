<?php
/**
 *   Handler class for Users
 *
 *   @author Juraj Sujan
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SeHistory
 */


define('USERTYPE_ADMIN', 'a');
define('USERTYPE_AGENT', 'g');
define('USERTYPE_USER', 'u');

define('ONLINE_STATUS_TIMEOUT', 3);


//TODO pri updatoch alebo insertoch treba zistit, ci user/agent/admin ma pravo na spravu daneho typu usera (napr agent nesmie menit admina a ineho agenta ... len usera)

QUnit::includeClass("QUnit_Rpc_Service");
QUnit::includeClass("App_Template");

class App_Service_Users extends QUnit_Rpc_Service {
    var $authMethods = array('SupportCenter');


    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
            case 'logout':
            case 'getLoggedInUser':
            case 'updateLoggedInUser':
            case 'getOnlineAgents':
            case 'getCustomFieldValues':
                return $this->callService('Users', 'authenticate', $params);
                break;

            case 'deleteUser':
            case 'updateUser':
            case 'getUser':
            case 'getUsersList':
            case 'getUserEmailsList':
            case 'getAgents':
            case 'updateLastAction':
                return $this->callService('Users', 'authenticateAgent', $params);
                break;

            case 'nieco pre admina':
                return $this->callService('Users', 'authenticateAdmin', $params);
                break;
                 
            case 'login':
            case 'hashLogin':
            case 'authenticate':
            case 'registerNewUser':
            case 'requestNewPassword':
            case 'getFirstAgent':
                return true;
                break;
            default:
                return false;
                break;
        }
    }

    function auth($params) {
        $response =& $this->getByRef('response');

        $methods = $this->state->config->get('authMethods');
        if (empty($methods) || !is_array($methods)) {
            $methods = $this->authMethods;
        }

        foreach ($methods as $method) {
            $class_name = 'App_Auth_' . $method;
            $authObj = QUnit::newObj($class_name);
            $authObj->state = $this->state;
            $authObj->response = $response;
            if ($ret = $authObj->auth($params)) {
                $this->response = $authObj->response;
                return $ret;
            }
        }
        return false;
    }

    function getOnlineAgents($params) {
        //refresh last access time of currenlty logged in user
        $this->updateLoginLog();
        //return online agents list
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');

        $columns = "u.user_id, u.name, u.email,
					UNIX_TIMESTAMP(l.last_request)-UNIX_TIMESTAMP(l.login) as login_time,
					l.last_action, l.ticket_id, t.subject_ticket_id";

        $from = "logins l
				 INNER JOIN users u ON l.user_id = u.user_id
				 LEFT JOIN tickets t ON l.ticket_id = t.ticket_id";

        $where = "logout IS NULL AND
				(u.user_type='" . USERTYPE_ADMIN . "' OR u.user_type = '" . USERTYPE_AGENT . "') AND 
				l.last_request > ('" . $db->getDateString() . "' - INTERVAL 900 SECOND)";

        if ($this->state->config->get('agentSeeJustAgentsFromHisQueues')=='y' && $session->getVar('userType') == 'g') {
            $where .= " AND ( 	u.user_id = '" . $db->escapeString($session->getVar('userId')) . "'
								OR
								(SELECT COUNT(*) FROM queues WHERE public='y') > 0
								OR
								(u.user_id IN (
										SELECT DISTINCT qa2.user_id 
										FROM queue_agents qa1 
										INNER JOIN queue_agents qa2 ON (qa1.queue_id = qa2.queue_id) 
										WHERE qa1.user_id = '" . $db->escapeString($session->getVar('userId')) . "' 
									)
								)
							)";
        }

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'users');
        return $this->callService('SqlTable', 'select', $params);
    }

    function getUserEmailsList($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $columns = "email";
        $from = "users";
        $where = "1";
        if($id = $params->get('email')) {
            $where .= " and email LIKE '".$db->escapeString($id)."%'";
        }

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'users');
        return $this->callService('SqlTable', 'select', $params);
    }

    function getUserByEmail($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $columns = "*";
        $from = "users";
        $where = "1";
        if($id = $params->get('email')) {
            $where .= " and email = '".$db->escapeString($id)."'";
        }

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'users');
        return $this->callService('SqlTable', 'select', $params);
    }

    /**
     *  login
     *
     *  @param string username
     *  @param string password
     *  @return object returns session id (sid)
     */
    function login($params) {
        $response =& $this->getByRef('response');

        if(!$params->check(array('email', 'password')) && !$params->check(array('email', 'password_md5'))) {
            $response->set('error', $this->state->lang->get('noUsernameOrPassword'));
            $this->state->log('error', $response->get('error'), 'Login');
            return false;
        }

        $db =& $this->state->getByRef('db');

        if ($this->auth($params)) {
            if($response->getResultVar('count') == '1') {
                $session = QUnit::newObj('QUnit_Session');

                $result = & $response->getByRef('result');
                $rows = $result['rs']->getRows($result['md']);
                $rs = $response->getResultVar('rs');

                $session->setVar('loginId', md5(uniqid(md5(rand()), true)));
                $session->setVar('userId', $rows[0]['user_id']);
                $session->setVar('groupId', $rows[0]['groupid']);
                $session->setVar('username', $rows[0]['email']);
                $session->setVar('name', $rows[0]['name']);
                $session->setVar('userType', $rows[0]['user_type']);
                $session->setVar('accessTime', time());

                $rs->rows[0][] = $session->getId();
                $rs->rows[0][] = round(date("Z")/60) * (-1);
                 
                $response->setResultVar('rs', $rs);
                 
                $md = $response->getResultVar('md');
                $md->addColumn('sid', 'string');
                $md->addColumn('timezone_offset', 'number');
                 
                $response->setResultVar('md', $md);
                 
                $this->state->log('info', 'Logged in user ' . $session->getVar('username'), 'Login');
                 
                //log login time
                return $this->logLogin();
            }
        }
        $response->set('result', null);
        $response->set('error', $this->state->lang->get('unableToLogin') . $params->get('email'));
        $this->state->log('error', $response->get('error'), 'Login', '', $params->get('email'));
        return false;
    }

    /**
     *  provides login functionality for hash stored at client computer in a cookie,
     *  which is useful for "Remember me on this computer"
     *
     * 	@param hash
     * 	@return object (the same as login)
     */

    function hashLogin($params) {
        $response =& $this->getByRef('response');

        if(!$params->check(array('hash'))) {
            $response->set('error', $this->state->lang->get('missingRequiredParameters'));
            return false;
        }

        $db =& $this->state->getByRef('db');

        $columns = "user_id, email, password, name, user_type, signature, groupid, hide_suggestions";
        $from = "users";
        $where = "hash = '".$db->escapeString($params->get('hash'))."'";

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'users');
        if($this->callService('SqlTable', 'select', $params)) {
            if($response->getResultVar('count') == '1') {
                $rs = $response->getResultVar('rs');
                $loginParams = $this->createParamsObject();
                $loginParams->set("email", $rs->rows[0][1]);
                $loginParams->set("password_md5", $rs->rows[0][2]);
                $this->callService('Users', 'login', $loginParams);
                return $response;
            }
        }
        $response->set('result', null);
        $response->set('error', $this->state->lang->get('unableToLogin'));
        $this->state->log('error', $response->get('error'), 'Login');
        return false;
    }

    /**
     *  authenticate
     *
     *  @param string sid (optional)
     *  @return object returns session id (sid)
     */
    function authenticate($params) {
        if($this->state->get('debug') === true) {
            return true;
        }

        $response =& $this->getByRef('response');

        $session = QUnit::newObj('QUnit_Session');

        if(strlen($params->get('sid'))) {
            $session->setId($params->get('sid'));
        }
        $session->start();
         
        if($session->existsVar('accessTime')) {
            $session->setVar('accessTime', time());
            $response->setResultVar('sid', $session->getId());
            return true;
        }
        $response->set('error', $this->state->lang->get('sessionNotActive'));
        $this->state->log('debug', $response->get('error'), 'auth');
        return false;
    }


    /**
     *  authenticateAgent
     *
     *  @param string sid (optional)
     *  @return object returns session id (sid)
     */
    function authenticateAgent($params) {
        if($this->state->get('debug') === true) {
            return true;
        }

        if(!$this->authenticate($params)) {
            return false;
        }

        $response =& $this->getByRef('response');

        $session = QUnit::newObj('QUnit_Session');
        if($session->getVar('userType') != 'a' && $session->getVar('userType') != 'g') {
            $response->set('error', $this->state->lang->get('failedAgentAuthentification'));
            $this->state->log('error', $response->get('error'), 'auth');
            return false;
        }

        return true;
    }


    /**
     *  authenticateAdmin
     *
     *  @param string sid (optional)
     *  @return object returns session id (sid)
     */
    function authenticateAdmin($params) {
        if($this->state->get('debug') === true) {
            return true;
        }

        if(!$this->authenticate($params)) {
            return false;
        }

        $response =& $this->getByRef('response');

        $session = QUnit::newObj('QUnit_Session');
        if($session->getVar('userType') != 'a') {
            $response->set('error', $this->state->lang->get('failedAdminAuthentification'));
            $this->state->log('error', $response->get('error'), 'auth');
            return false;
        }

        return true;
    }

    /**
     *  logout
     *
     *  @param string sid (optional)
     *  @return boolean
     */
    function logout($params) {
        $response =& $this->getByRef('response');

        $session = QUnit::newObj('QUnit_Session');

        //log logout of user
        $this->logLogout();

        if(strlen($params->get('sid'))) {
            $session->setId($sid);
        }

        $this->state->log('info', 'Logged out user ' . $session->getVar('username'), 'auth');

        $session->start();
        $session->destroy();
         
        $response->set('result', 'true');
        return false;
    }

    function getAgents($params) {
        $userTypes = array('g');
        if ($this->state->config->get('agentCanNotSeeAdmins')!='y') {
            $userTypes[] = 'a';
        }
        $params->set('user_type', $userTypes);
        $params->set('order', 'name');
        $params->set('getAgents', true);
        return $this->getUsersList($params);
    }

    function getUsersList($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');
         
        $columns = "user_id, email, name, user_type, created, email_quality, users.groupid, group_name";
        if ($params->get('hash')) {
            $columns .= ', hash';
        }
        $from = "users LEFT JOIN groups g ON (g.groupid = users.groupid)";
        $where = "1";
        if($id = $params->get('user_id')) {
            $where .= " and user_id = '".$db->escapeString($id)."'";
        }
         
        if($id = $params->get('user_type')) {
            if (is_array($id)) {
                $ids = '';
                foreach ($id as $filter_value) {
                    if (strlen($filter_value)) {
                        $ids .= (strlen($ids) ? ',' : '') . "'" . $db->escapeString($filter_value) . "'";
                    }
                }
                if (strlen($ids)) {
                    $where .= " and user_type IN (".$ids.")";
                }
            } else {
                $where .= " and user_type = '".$db->escapeString($id)."'";
            }
        }
         
        if($id = $params->get('email_quality')) {
            if (is_array($id)) {
                $ids = '';
                foreach ($id as $filter_value) {
                    if (strlen($filter_value)) {
                        $ids .= (strlen($ids) ? ',' : '') . "'" . $db->escapeString($filter_value) . "'";
                    }
                }
                if (strlen($ids)) {
                    $where .= " and email_quality IN (".$ids.")";
                }
            } else {
                $where .= " and email_quality = '".$db->escapeString($id)."'";
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
                    $where .= " and users.groupid IN (".$ids.")";
                }
            } else {
                $where .= " and users.groupid = '".$db->escapeString($id)."'";
            }
        }

        if($id = $params->get('name_search')) {
            $where .= " and name like '%".$db->escapeString($id)."%' or email like '%".$db->escapeString($id)."%'";
        }

        if($id = $params->get('name')) {
            $where .= " and name like '%".$db->escapeString($id)."%'";
        }

        if($id = $params->get('email')) {
            if ($params->get('exact_email')) {
                $where .= " and email = '".$db->escapeString($id)."'";
            } else {
                $where .= " and email like '%".$db->escapeString($id)."%'";
            }
        }
         
        if($id = $params->get('created_from')) {
            $where .= " AND created > '" . $db->escapeString($id) . "'";
        }
        if($id = $params->get('created_to')) {
            $where .= " AND created < '" . $db->escapeString($id) . "'";
        }
         
        if ($params->get('updateLoginsAction')) {
            $this->updateLoginLog('users_list', 'null');
        }
         
        if (!$params->get('getAgents') && $session->getVar('userType') == 'g') {
            switch ($this->state->config->get('agentUserAccess')) {
                //complete access Users, Agents, Admins
                case '':
                case '0':
                    break;

                case '1':		//users and agents
                    $where .= " AND user_type IN ('u', 'g')";
                    break;
                case '2':		//only users
                    $where .= " AND user_type='u'";
                    break;
                case '3':		//no access, return null
                    $where .= " AND 1=2";
                    break;
            }
        }

        if ($this->state->config->get('agentSeeJustAgentsFromHisQueues')=='y' && $session->getVar('userType') == 'g') {
            $where .= " AND ( 	user_type IN ('u', 'a') OR
								user_id = '" . $db->escapeString($session->getVar('userId')) . "' OR
								(SELECT COUNT(*) FROM queues WHERE public='y') > 0
								OR
								(user_id IN (
										SELECT DISTINCT qa2.user_id 
										FROM queue_agents qa1 
										INNER JOIN queue_agents qa2 ON (qa1.queue_id = qa2.queue_id) 
										WHERE qa1.user_id = '" . $db->escapeString($session->getVar('userId')) . "' 
									)
								)
							)";
        }
         

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'users');
        return $this->callService('SqlTable', 'select', $params);
    }

    function getLoggedInUser($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');
         
        $columns = "user_id, groupid, email, name, user_type, created, email_quality, signature, hash, hide_suggestions, disable_dm_notifications";
        $from = "users";
        $where = "";
        if($id = $session->getVar('userId')) {
            $where = "user_id = '".$db->escapeString($id)."'";
        }

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'users');
        return $this->callService('SqlTable', 'select', $params);
    }

    function getUser($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        if(!$params->check('user_id')) {
            $response->set('error', 'no user_id');
            return false;
        }
         
        $columns = "user_id, email, name, user_type, created, email_quality, signature, groupid, hide_suggestions, disable_dm_notifications ";
        $from = "users";
        $where = "user_id = '".$db->escapeString($params->get('user_id'))."'";

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'users');
        return $this->callService('SqlTable', 'select', $params);
    }

    function existUser($params) {
        $response =& $this->getByRef('response');
        if ($this->getUsersList($params)) {
            $result = & $response->getByRef('result');
            if ($result['count'] > 0) {
                return true;
            }
        }
        return false;
    }

    function updateLoggedInUser($params) {
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');

        $params->set('user_id', $session->getVar('userId'));

        //unset fields, which can't be changed with this method
        $params->unsetField(array('hash', 'user_type', 'created'));
         
        if ($ret = $this->updateUser($params)) {
            if (strlen($params->getField('name'))) {
                $session->setVar('name', $params->getField('name'));
            }
            if (strlen($params->getField('email'))) {
                $session->setVar('username', $params->getField('email'));
            }
        }
        return $ret;
    }

    function updateUser($params) {
        $response =& $this->getByRef('response');
        $db =& $this->state->getByRef('db');
        $session = QUnit::newObj('QUnit_Session');
         
        if ($params->checkFields(array('user_type')) && !$this->callService('Users', 'authenticateAdmin', $params)) {
            $response->set('error', $this->state->lang->get('justAdminCanChangeUserType'));
            return false;
        }

        $ids = $params->get('user_id');
        $where_ids = '';
        if (!is_array($ids)) $ids = array($ids);

        if ($this->isDemoMode('Users', $ids)) {
            $response->set('error', $this->state->lang->get('notAlloweInDemoMode'));
            return false;
        }

        //security check
         
        switch($session->getVar('userType')) {
            case 'a':
                break;
            case 'u':
                if ($session->getVar('userId') <> $params->get('user_id')) {
                    $response->set('error', $this->state->lang->get('permissionDenied'));
                    return false;
                }
                break;
            case 'g':
                if ($session->getVar('userId') <> $params->get('user_id')) {

                    //check if user is not a agent of admin
                    $usr = $this->loadUser($params);
                     
                    if ($usr['user_type'] == 'a' || $usr['user_type'] == 'g') {
                        $response->set('error', $this->state->lang->get('permissionDenied'));
                        return false;
                    }
                }
                break;
            default:
                break;
        }
         
        if ($params->get('user_type') == 'g' && is_array($params->get('queue_ids')) && !is_array($params->get('user_id'))) {
            //delete all current assignments of agent to queue

            $sql = "DELETE FROM queue_agents WHERE user_id='" . $db->escapeString($ids[0]) . "'";
            $sth = $db->execute($sql);
            $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
            if(!$this->_checkDbError($sth)) {
                $response->set('error', $this->state->lang->get('failedDeleteOldAssignments'));
                $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Queue');
                return false;
            }

            //insert new assignments of agent to queue


            $sql = "INSERT IGNORE INTO queue_agents (queue_id, user_id)
					VALUES ";
            $values = '';
            if (is_array($params->get('queue_ids'))) {
                foreach ($params->get('queue_ids') as $queue_id) {
                    $queue_id = addslashes(trim($queue_id));
                    $values .= (strlen($values) ? ',' : '') . "('" . addslashes($queue_id) . "', '" . $ids[0] . "')";
                }
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
        }

         
        foreach ($ids as $id) {
            if (strlen(trim($id))) {
                $where_ids .= (strlen($where_ids) ? ', ': '');
                $where_ids .= "'" . $db->escapeString(trim($id)) . "'";
            }
        }
         
        if(!$params->check(array('user_id')) || !strlen($where_ids)) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }

        //if empty password, don't change password
        if (!$params->checkFields(array('password')) || $params->getField('password') == md5('')) {
            $params->unsetField('password');
        } else {
            if (!$this->checkPasswordQuality($params)) {
                return false;
            }
        }

        if (strlen($params->getField('email'))) {
            $params->setField('email_quality', $this->getEmailQuality($params->getField('email')));
            if ($this->state->config->get('checkEmailQuality')=='y' && $params->getField('email_quality') < 2) {
                $response->set('error', $this->state->lang->get('incorrectEmail'));
                return false;
            }
        }

        //for security reason don't allow autologin after change of password
        if($params->check(array('password'))) {
            $params->setField('hash', md5(rand()));
        }

         
        if ($params->getField('groupid') !== false && !strlen($params->getField('groupid'))) {
            $params->setField('groupid', 'NULL');
        }

        $params->set('table', 'users');
        $params->set('where', "user_id IN (" . $where_ids . ")");
        if ($this->callService('SqlTable', 'update', $params)) {
            $this->updateCustomFields($params);
            return true;
        } else {
            return false;
        }
    }

    function updateCustomFields($params) {
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
                    $fldParams->setField('user_id', $params->get('user_id'));
                    $fldParams->setField('field_value', $fld);
                    if (!$this->callService('Fields', 'insertFieldValue', $fldParams)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }


    function logLogin() {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');
        if (strlen($session->getId()) && strlen($session->getVar('loginId')) &&
        ($session->getVar('userType') == 'a' || $session->getVar('userType') == 'g')) {
             
            $table = QUnit::newObj('QUnit_Db_Table', $db, 'logins');
            $table->setByRef('state', $this->state);

            $fields = array();
            $fields['login_id'] = $session->getVar('loginId');
            $fields['user_id'] = $session->getVar('userId');
            $fields['login'] = $db->getDateString();
            $fields['ip'] = $_SERVER['REMOTE_ADDR'];

            $table->fill($fields);
            $sth = $table->insert();
            if(QUnit_Object::isError($sth)) {
                $response->set('error', $this->state->lang->get('insertFailed').$sth->get('errorMessage'));
                return false;
            }
        }
        return true;
    }

    function logLogout() {
        $db =& $this->state->getByRef('db');
        $table = QUnit::newObj('QUnit_Db_Table', $db, 'logins');
        $table->state = $this->state;

        $session = QUnit::newObj('QUnit_Session');

        if (strlen($session->getId()) && strlen($session->getVar('loginId')) &&
        ($session->getVar('userType') == 'a' || $session->getVar('userType') == 'g')) {

            $where = "login_id = '".$db->escapeString($session->getVar('loginId'))."'";

            $fields = array();
            $fields['logout'] = $db->getDateString();
            $fields['last_action'] = 'null';
            $fields['ticket_id'] = 'null';

            $table->fill($fields);
            return $table->update($where);
        }
        return true;
    }

    function updateLoginLog($action = '', $ticket_id = '') {
        $db =& $this->state->getByRef('db');
        $table = QUnit::newObj('QUnit_Db_Table', $db, 'logins');
        $table->state = $this->state;

        $session = QUnit::newObj('QUnit_Session');

        if (strlen($session->getId()) && strlen($session->getVar('loginId')) &&
        ($session->getVar('userType') == 'a' || $session->getVar('userType') == 'g')) {

            $where = "login_id = '".$db->escapeString($session->getVar('loginId'))."'";

            $fields = array();
            $fields['last_request'] = $db->getDateString();
            $fields['logout'] = 'null';
            if ($action != '') {
                $fields['last_action'] = $action;
            }
            if ($ticket_id != '') {
                $fields['ticket_id'] = $ticket_id;
            }

            $table->fill($fields);
            return $table->update($where);
        }
        return true;
    }

    function updateLastAction($params) {
        $response =& $this->getByRef('response');
        if ($this->updateLoginLog($params->get('action'), $params->get('ticket_id'))) {
            //check if anybody alse is not replying ticket
            if ($params->get('action') == 'replying') return $this->isTicketFreeForReplying($params->get('ticket_id'));
        }
        return true;
    }

    function isTicketFreeForReplying($ticket_id) {
        if ($ticket_id != 'null' || !strlen($ticket_id)) {
            $db =& $this->state->getByRef('db');
            $session = QUnit::newObj('QUnit_Session');
            $response =& $this->getByRef('response');

            $sql = "SELECT u.name, u.email
					FROM logins l INNER JOIN users u ON u.user_id=l.user_id 
					WHERE l.logout IS NULL AND 
					l.last_request > ('" . $db->getDateString() . "' - INTERVAL 120 SECOND) AND
					l.last_action='replying' AND l.login_id <> '" . 
            $session->getVar('loginId') . "' AND l.ticket_id=" . $ticket_id;
            $sth = $db->execute($sql);
            $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
            if(QUnit_Object::isError($sth)) {
                $response->set('result', null);
                $response->set('error', $this->state->lang->get('dbError').$sth->get('errorMessage'));
                return false;
            }
            $rs = QUnit::newObj('QUnit_Rpc_ResultSet');
            $rows = $sth->fetchAllRows();
            $users = "";
            foreach ($rows as $row) {
                if (strlen($row[0])) {
                    $users .= (strlen($users) ? ', ' : '') . $row[0] . " (" . $row[1] . ")";
                } else {
                    $users .= (strlen($users) ? ', ' : '') . $row[1];
                }
            }
             
            if (strlen($users)) {
                $response->set('error', $this->state->lang->get('anotherUsersReplying', $users));
                return false;
            }
        } else {
            return true;
        }
    }

    function _checkDbError($sth) {
        $response =& $this->getByRef('response');

        if(QUnit_Object::isError($sth)) {
            $response->set('error', $this->state->lang->get('dbError').$sth->get('errorMessage'));
            return false;
        }
        return true;
    }

    /**
     * Generates random password
     *
     * @return unknown
     */
    function generatePassword($length = 5) {
        $password_string = "qwertyuiplkjhgfdsazxcvbnm987654321!?*";
        return substr(str_shuffle($password_string), 0, $length);
    }

    function getEmailQuality($email) {
        if ($this->state->config->get('checkEmailQuality') == 'y') {

            $this->state->log('debug', 'Validate quality of email: ' . $email, 'User');
            //check email quality
            $emailValidator = QUnit::newobj('QUnit_Net_EmailValidator');
            $ret = $emailValidator->returnValidatedEmailStatus($email);
            $this->state->log('debug', 'Quality of email: ' . $email . ' is ' . $ret, 'User');
            return $ret;
        } else {
            //not checked
            return 0;
        }
    }

    /**
     * Password has to be longer as 3 characters and smaller as 10 characters
     */
    function checkPasswordQuality(&$params) {
        $response =& $this->getByRef('response');
        if (strlen($params->getField('password')) < 32) {
            $response->set('error', $this->state->lang->get('passwordTooShort'));
            return false;
        }
        if (strlen($params->getField('password')) > 32) {
            $response->set('error', $this->state->lang->get('passwordTooLong'));
            return false;
        }
        if (md5($params->getField('name')) == $params->getField('password')) {
            $response->set('error', $this->state->lang->get('passwordSameAsName'));
            return false;
        }
        if (md5($params->getField('email')) == $params->getField('password')) {
            $response->set('error', $this->state->lang->get('passwordSameAsEmail'));
            return false;
        }
        return true;
    }

    /**
     * Register new user to database
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function registerNewUser(&$params) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
         
        if (!$this->checkPasswordQuality($params)) {
            return false;
        }
         
        $params->setField('email_quality', $this->getEmailQuality($params->getField('email')));
         
        if ($this->state->config->get('checkEmailQuality')=='y' && $params->getField('email_quality') < 2) {
            $response->set('error', $this->state->lang->get('incorrectEmail'));
            return false;
        }
        //user or agent can create just simple user !
        if ($session->getVar('userType') != 'a' || !strlen($params->getField('user_type'))) {
            $params->setField('user_type', USERTYPE_USER);
        }

        if ($ret = $this->callService('Users', 'insertUser', $params)) {
            $this->state->log('notice', $this->state->lang->get('manualUserRegistrationSuccessful'), 'User', $params->get('user_id'));
        } else {
            $this->state->log('notice', $this->state->lang->get('manualUserRegistrationFailed'), 'User');
            $response->set('error', $this->state->lang->get('manualUserRegistrationFailed'));
            return false;
        }
        return true;
    }

    /**
     * Insert new user to database
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function insertUser(&$params) {
        $db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
        $response =& $this->getByRef('response');
        $params->set('table', 'users');
        $params->setField('hash', md5(uniqid(rand(), true)));

        if (!strlen($params->getField('user_id'))) {
            $params->setField('user_id', md5(uniqid(rand(), true)));
        }

        //set created date
        $params->setField('created', $db->getDateString());

        //set password
        if (!$params->check('plain_password')) {
            $params->set('plain_password', $this->generatePassword());
        }
        $params->setField('password', md5($params->get('plain_password')));
         
        //set email quality
        if (!strlen($params->getField('email_quality')) || $params->getField('email_quality') == 0) {
            $params->setField('email_quality', $this->getEmailQuality($params->getField('email')));
        }
         
        if (!strlen($params->getField('signature'))) {
            $params->setField('signature', '&nbsp;');
        }

        if ($params->getField('hide_suggestions') != 'y') {
            $params->setField('hide_suggestions', 'n');
        }
        
        if ($params->getField('disable_dm_notifications') != 'y') {
            $params->setField('disable_dm_notifications', 'n');
        }
        
        if (!strlen($params->getField('groupid'))) {
            $params->setField('groupid', 'NULL');
        }
         
        //by default create user if is no type selected or not a admin user
        if (!strlen($params->getField('user_type')) || $session->getVar('userType') != 'a') {
            $params->setField('user_type', USERTYPE_USER);
        }

        if ($ret = $this->callService('SqlTable', 'insert', $params)) {
            $this->state->log('notice', $this->state->lang->get('userCreated', $params->getField('email')), 'User', $params->getField('user_id'));
            if ($params->getField('email_quality') == 0 ||
            $params->getField('email_quality') >= 2 ||
            !$this->state->config->get('checkEmailQuality')=='y') {
                //notify user about his new password by email

                $paramNotification = $this->createParamsObject();
                 
                $templates = QUnit::newObj('App_Service_MailTemplates');
                $templates->state = $this->state;
                $templates->response = $response;
                $registrationTemplate = $templates->loadTemplate('RegistrationMail');
                 
                $paramNotification->set('to', $params->getField('email'));
                $paramNotification->set('username', $params->getField('email'));
                $paramNotification->set('name', $params->getField('name'));
                $paramNotification->set('password', $params->get('plain_password'));
                $paramNotification->set('appUrl', $this->state->config->get('applicationURL'));
                $paramNotification->set('Auto-Submitted', 'auto-generated');
                $paramNotification->set('subject', App_Template::evaluateTemplate($paramNotification, $registrationTemplate['subject']));
                $paramNotification->set('template_text', $registrationTemplate['body_html']);
                 
                if ($this->state->config->get('sendRegistrationEmail') == 'y') {
                    $ret = $this->callService('SendMail', 'send', $paramNotification);
                }
            }
        } else {
            $this->state->log('error', $this->state->lang->get('insertUserFailed', $params->getField('email')), 'User');
        }
        return $ret;
    }

    /**
     *  deleteUser
     *
     *  @param string table
     *  @param string id id of task
     *  @return boolean
     */
    function deleteUser($params) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
         
        if (is_array($params->get('user_id'))) {
            $ids = $params->get('user_id');
        } else {
            $ids = explode('|',$params->get('user_id'));
        }
         
        if ($this->isDemoMode('Users', $ids)) {
            $response->set('error', $this->state->lang->get('notAlloweInDemoMode'));
            return false;
        }
         
         
        $session = QUnit::newObj('QUnit_Session');
         
        // user can't delete himself
        if(array_search($session->getVar('userId'), $ids) != false) {
            $response->set('error', $this->state->lang->get('cantDeleteYourself'));
            return false;
        }
         
        $where_ids = '';
        foreach ($ids as $id) {
            if (strlen(trim($id))) {
                $where_ids .= (strlen($where_ids) ? ', ': '');
                $where_ids .= "'" . $db->escapeString(trim($id)) . "'";
            }
        }
         
        if(!$params->check(array('user_id')) || !strlen($where_ids)) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }

        //agent shouldn't be able to delete other agents or admins
        if ($session->getVar('userType') != 'a') {
            $sql = "SELECT * FROM users WHERE user_type <> 'u' AND user_id IN (" . $where_ids . ")";
            $sth = $db->execute($sql);
            $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
            if(!$this->_checkDbError($sth)) {
                $response->set('error', $this->state->lang->get('failedToCheckUserType'));
                $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'User');
                return false;
            } else {
                if ($sth->rowCount() > 0) {
                    $response->set('error', $this->state->lang->get('agentCanNotDeleteAgentOrAdmin'));
                    $this->state->log('error', $response->get('error'), 'User');
                    return false;
                }
            }
        }

        $sql = "delete from signatures where user_id IN (" . $where_ids . ")";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $this->state->lang->get('failedDeleteLogUsers') . $params->get('email'));
            $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'User');
            return false;
        }
         
         
        //zmazat custom fieldy usera
        $sql = "delete from custom_values where user_id IN (" . $where_ids . ")";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $this->state->lang->get('failedDeleteLogUsers') . $params->get('email'));
            $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'User');
            return false;
        }
         
        //zmazat priradenia logov userom
        $sql = "delete from log_users where user_id IN (" . $where_ids . ")";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $this->state->lang->get('failedDeleteLogUsers') . $params->get('email'));
            $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'User');
            return false;
        }

        //zmazat status read userov
        $sql = "delete from displayed_tickets where user_id IN (" . $where_ids . ")";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $this->state->lang->get('failedDeleteUserNotifications') . $params->get('email'));
            $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'User');
            return false;
        }

         
        //zmazat notifikacne nastavenia userov
        $sql = "delete from notifications where user_id IN (" . $where_ids . ")";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $this->state->lang->get('failedDeleteUserNotifications') . $params->get('email'));
            $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'User');
            return false;
        }
         
        //zmazat filtre userov
        $sql = "delete from filters where user_id IN (" . $where_ids . ")";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $this->state->lang->get('failedDeleteFilters') . $params->get('email'));
            $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'User');
            return false;
        }
         
        //zmazat priradenia emailov userom
        $sql = "delete from mail_users where user_id IN (" . $where_ids . ")";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $this->state->lang->get('failedDeleteMailUsers') . $params->get('email'));
            $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'User');
            return false;
        }
         
        //zmazat priradenia userov do queues
        $sql = "delete from queue_agents where user_id IN (" . $where_ids . ")";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $this->state->lang->get('failedDeleteQueueAgents') . $params->get('email'));
            $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'User');
            return false;
        }
         
        //unsetnut customer_id v ticketoch, kde su useri priradeni
        $sql = "UPDATE tickets SET customer_id=NULL where customer_id IN (" . $where_ids . ")";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $this->state->lang->get('failedDeleteTicketOwner') . $params->get('email'));
            $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'User');
            return false;
        }
         
        //unset agent_owner_id pri ticketoch kde je zmazany agent
        $sql = "UPDATE tickets SET agent_owner_id=NULL where agent_owner_id IN (" . $where_ids . ")";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $this->state->lang->get('failedDeleteTicketAgentOwner') . $params->get('email'));
            $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'User');
            return false;
        }
         
        $params->set('table', 'users');
        $params->set('where', "user_id IN (" . $where_ids . ")");
        if ($ret = $this->callService('SqlTable', 'delete', $params)) {
            $this->state->log('info', 'Deleted user ' . $params->get('email'), 'User');
        } else {
            $this->state->log('error', 'Failed to delete user ' . $params->get('email'), 'User');
        }
        return $ret;
    }

    /**
     * check if email represents agent (agent or admin) or simple user
     */
    function isAgent($email) {
        $db =& $this->state->getByRef('db');
         
        $sql = "SELECT *
				FROM users 
				WHERE user_type IN ('a', 'g') and email='" . $db->escapeString($email) . "'
				LIMIT 0,1";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $this->state->log('error', $response->get('error')  . " -> " . $sth->get('errorMessage'), 'Queue');
            return false;
        }
        $arr = $sth->fetchAllRows();
        if (count($arr) == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * request new password functionality
     * @param email - email address of user, which requests new password
     */
    function requestNewPassword($params) {
        $response =& $this->getByRef('response');
         
        if ($this->state->config->get('demoMode')) {
            $response->set('error', $this->state->lang->get('notAlloweInDemoMode'));
            return false;
        }
         
        if (!strlen(trim($params->get('email')))) {
            $response->set('error', $this->state->lang->get('userNotInDatabase'));
            return false;
        }
        //zisti ci user existuje
        $paramsUser = $this->createParamsObject();
        $paramsUser->set('email', $params->get('email'));
        if (!$this->callService('Users', 'getUserByEmail', $paramsUser)) {
            $this->state->log('error', 'Failed request if user ' . $paramsUser->get('email') . ' exist with error: ' . $response->error,'User');
            return false;
        }
        if($response->getResultVar('count') == 0) {
            $response->set('error', $this->state->lang->get('userNotInDatabase'));
            return false;
        } else {
            //nasiel usera
            $result = $response->result;
            $rows = $result['rs']->getRows($result['md']);
            $user = $rows[0];
        }

        $paramsUser = $this->createParamsObject();

        //check quality of email (avoid sending new password to clearly wrong email - better they should contact administrator)
        if ($this->state->config->get('checkEmailQuality')=='y' && $user['email_quality'] < 2) {
            //try to revalidate email address
            $email_quality = $this->getEmailQuality($params->get('email'));
            if ($email_quality < 2) {
                $response->set('error', $this->state->lang->get('incorrectEmail'));
                return false;
            } else {
                //update new email quality to user
                $paramsUser->setField('email_quality', $email_quality);
            }
        }

        //generate new password
        $paramsUser->set('user_id', $user['user_id']);
        $paramsUser->set('plain_password', $this->generatePassword());
        $paramsUser->setField('password', md5($paramsUser->get('plain_password')));

        if (!$this->callService('Users', 'updateUser', $paramsUser)) {
            $this->state->log('error', 'Failed update user ' . $paramsUser->get('email') . ' with error: ' . $response->error,'User');
            return false;
        }

        //send email with new password
        $paramNotification = $this->createParamsObject();


        $templates = QUnit::newObj('App_Service_MailTemplates');
        $templates->state = $this->state;
        $templates->response = $response;
        $requestPWTemplate = $templates->loadTemplate('RequestNewPasswordMail');

        $paramNotification->set('to', $user['email']);
        $paramNotification->set('username', $user['email']);
        $paramNotification->set('name', $user['name']);
        $paramNotification->set('password', $paramsUser->get('plain_password'));
        $paramNotification->set('appUrl', $this->state->config->get('applicationURL'));
        $paramNotification->set('Auto-Submitted', 'auto-generated');

        $paramNotification->set('subject', App_Template::evaluateTemplate($paramNotification, $requestPWTemplate['subject']));
        $paramNotification->set('template_text', $requestPWTemplate['body_html']);

        if (!($ret = $this->callService('SendMail', 'send', $paramNotification))) {
            return false;
        }
        $response->set('result', 'Done'); 
        return true;
    }

    /**
     * Load user row and store it into array
     */
    function loadUser($params) {
        $response =& $this->getByRef('response');
   	   	$db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
        $user = false;
        //load ticket
        if ($params->check(array('user_id')) || $params->check(array('email'))) {
            $paramsUser = $this->createParamsObject();
            if (strlen($params->get('user_id'))) {
                $paramsUser->set('user_id', $params->get('user_id'));
            }
            if (strlen($params->get('email'))) {
                $paramsUser->set('email', $params->get('email'));
            }
            //load always all users
            $paramsUser->set('getAgents', true);
            
            if ($params->get('hash')) {
                $paramsUser->set('hash', true);
            }
            if ($ret = $this->callService('Users', 'getUsersList', $paramsUser)) {
                $res = & $response->getByRef('result');
                if ($res['count'] > 0) {
                    $user = $res['rs']->getRows($res['md']);
                    $user = $user[0];
                }
            }
        }
        return $user;
    }


    function recomputeNotValidatedEmails() {
        //check if validation of email quality is allowed
        //load all users with email quality 0
        //evalueate user
        //update his mail quality
    }

    function getFirstAgent($params) {
        $params->set('columns', "MIN(created) as first_time");
        $params->set('from', 'users');
        $params->set('where', "created > '2006-1-1'");
        $params->set('table', 'users');
        return $this->callService('SqlTable', 'select', $params);
    }

    function getCustomFieldValues($params) {
   	   	$db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');

        $where = "related_to='u'";

        if ($session->getVar('userType') == 'u') {
            $user_id = $session->getVar('userId');
            $where .= " AND cf.user_access='u'";
        } else {
            $user_id = $params->get('user_id');
        }


        $params->set('columns', "*");
        $params->set('from', "custom_fields cf
							  LEFT JOIN custom_values cv ON (cf.field_id=cv.field_id AND cv.user_id='" . $db->escapeString($user_id) . "')");
        $params->set('where', $where);
        $params->set('order', 'cf.order_value, cf.field_title');
        $params->set('table', 'custom_fields');
        return $this->callService('SqlTable', 'select', $params);
    }

    function getUserGroup($email) {
        $response =& $this->getByRef('response');
   	   	$db = $this->state->get('db');
        //load ticket
        $paramsUser = $this->createParamsObject();
        $paramsUser->set('email', $email);
        $paramsUser->set('exact_email', true);
        if ($ret = $this->callService('Users', 'getUsersList', $paramsUser)) {
            $res = & $response->getByRef('result');
            if ($res['count'] > 0) {
                $user = $res['rs']->getRows($res['md']);
                $user = $user[0];
                return $user['groupid'];
            }
        }
        return '';
    }

    function getUserGroupByUserId($userid) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
        //load ticket
        $paramsUser = $this->createParamsObject();
        $paramsUser->set('user_id', $email);
        if ($ret = $this->callService('Users', 'getUsersList', $paramsUser)) {
            $res = & $response->getByRef('result');
            if ($res['count'] > 0) {
                $user = $res['rs']->getRows($res['md']);
                $user = $user[0];
                return $user['groupid'];
            }
        }
        return '';
    }
}
?>
