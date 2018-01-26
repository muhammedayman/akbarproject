<?php
/**
 * 	Handler class for Knowledge Base
 *
 * 	@author Martin Vicen
 * 	@copyright Copyright (c) Quality Unit s.r.o.
 * 	@package SupportCenter
 */

QUnit::includeClass('QUnit_Rpc_Service');

define('KB_ACCESS_MODE_PUBLIC', 'a');
define('KB_ACCESS_MODE_PRIVATE', 'p');
define('KB_ACCESS_MODE_USER', 'u');
define('KB_ACCESS_MODE_INTERNAL', 'g');

class App_Service_KBItems extends QUnit_Rpc_Service {

    var $table = "kb_items";

    function initMethod($params) {
        $method = $params->get('method');
        switch($method) {
            case 'getItemsList':
            case 'getItem':
                return $this->callService('Users', 'authenticate', $params);
                break;
            case 'insertItem':
            case 'deleteItem':
            case 'updateItem':
            case 'forceRefresh':
            case 'moveItem':
                return $this->callService('Users', 'authenticateAgent', $params);
                break;
            case 'searchItems':
                return true;
            default:
                return false;
                break;
        }
    }

    function getItemsList($params) {

        if (strlen($params->get('query'))) {
            return $this->getItemsListForTicket($params);
        }

        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');


        $from = "kb_items";
        $columns = "item_id, tree_path, subject, item_id as iid, created, access_mode, item_order";
        if ($params->get('all_columns')) {
            $columns .= ", body, url, metadescription, created";
        }
        $where = "1";

        if($treePath = $params->get('tree_path')) {
            $columns .= " ,(SELECT count(*) FROM $from WHERE";
            $columns .= " tree_path = CONCAT('".$db->escapeString($treePath)."', iid, '|')";
             
            if ($params->get('show_levels') != 'all') {
                $where .= " AND tree_path = '".$db->escapeString($treePath)."'";
            } else {
                $where .= " AND tree_path LIKE '".$db->escapeString($treePath)."%'";
            }
        }

        if (strlen($fulltext = $params->get('fulltext'))) {
            $where .= " and MATCH (subject, metadescription, body) AGAINST ('".$db->escapeString($fulltext)."' IN BOOLEAN MODE)";
        }

        $userType = $session->getVar('userType');
        $userId = $session->getVar('userId');

        $tmp = '';
        switch ($userType) {
            case 'a':   //admin can see all entries
                $tmp = '';
                break;
            case 'g':   //agent can see internal, public, private, user
                $tmp = " AND (
                            access_mode IN ('" . KB_ACCESS_MODE_PUBLIC . "', '" . KB_ACCESS_MODE_INTERNAL . "', '" . KB_ACCESS_MODE_USER . "') 
                            OR (access_mode = '" . KB_ACCESS_MODE_PRIVATE . "' AND user_id = '$userId'))";
                break;
            case 'u':   //user can see public, and user items
                $tmp = " AND access_mode IN ('" . KB_ACCESS_MODE_PUBLIC . "', '" . KB_ACCESS_MODE_USER . "') ";
                break;
            default:    //all other can see just public items
                $tmp = " AND access_mode = '" . KB_ACCESS_MODE_PUBLIC . "'";
        }
        $where .= $tmp;
        $columns .= $tmp;

        if($treePath = $params->get('tree_path')) {
            $columns .= " ) as children_count";
        }
        $columns .= " ,(SELECT count(*) FROM kb_item_files f WHERE kb_items.item_id = f.item_id GROUP BY item_id) as attachments_count";

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'kb_items');
        $params->set('order', 'tree_path, item_order, subject');
        return $this->callService('SqlTable', 'select', $params);
    }

    function getItemsListForTicket($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');

        if ($this->state->config->get('knowledgeBaseModule') != 'y') {
            $response->set('error', $this->state->lang->get('KnowledgeBaseNotActivated'));
            $response->set('result', false);
            return false;
        }


        if(!$params->check(array('query'))) {
            $params->set('query', '');
        }

        $objKeyWords = QUnit::newObj('App_Service_KeyWords');
        list($count, $arrWords) = $objKeyWords->getWords($params->get('query'));

        if ($count > 0) {

            $inValue = '';
            foreach ($arrWords as $word => $count) {
                if (strlen($word)) {
                    $inValue .= (strlen($inValue) ? ',':'') . "'" . $db->escapeString(md5($word)) . "'";
                }
            }
            $columns = "i.item_id, i.tree_path, i.subject, i.access_mode, i.created, item_order, url, rank";
            $columns .= " ,(SELECT count(*) FROM kb_items kbi WHERE kbi.tree_path = CONCAT(i.tree_path, i.item_id, '|')) as children_count";
             
            $from = "kb_items i
					INNER JOIN (
						SELECT iw.item_id, SUM(w.ranking * iw.word_ranking) as rank
						FROM words w
						INNER JOIN kb_item_words iw ON (w.word_id=iw.word_id)
						WHERE w.word_id IN(" . $inValue . ")
						GROUP BY iw.item_id
					) r ON (i.item_id = r.item_id)";
             
             
            $userType = $session->getVar('userType');
            $userId = $session->getVar('userId');
             
            $where = "1";

            switch ($userType) {
                case 'a':   //admin can see all entries
                    break;
                case 'g':   //agent can see internal, public, private, user
                    $where .= " AND (
                            access_mode IN ('" . KB_ACCESS_MODE_PUBLIC . "', '" . KB_ACCESS_MODE_INTERNAL . "', '" . KB_ACCESS_MODE_USER . "') 
                            OR (access_mode = '" . KB_ACCESS_MODE_PRIVATE . "' AND user_id = '$userId'))";
                    break;
                case 'u':   //user can see public, and user items
                    $where .= " AND access_mode IN ('" . KB_ACCESS_MODE_PUBLIC . "', '" . KB_ACCESS_MODE_USER . "') ";
                    break;
                default:    //all other can see just public items
                    $where .= " AND access_mode = '" . KB_ACCESS_MODE_PUBLIC . "'";
            }

            $params->set('columns', $columns);
            $params->set('from', $from);
            $params->set('where', $where);
            $params->set('table', 'kb_items');
            $params->set('limit', 20);
            $params->set('order', 'rank DESC');
            return $this->callService('SqlTable', 'select', $params);
        } else {
            $response->set('error', $this->state->lang->get('InvalidSearchQuery'));
            $response->set('result', false);
            return false;
        }

    }

    function getItem($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');

        if(!$params->check('item_id')) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }

        $where = 'item_id = '.$db->escapeString($params->get('item_id'));

        $userType = $session->getVar('userType');
        $userId = $session->getVar('userId');

        
        
        
        switch ($userType) {
            case 'a':   //admin can see all entries
                break;
            case 'g':   //agent can see internal, public, private, user
                $where .= " AND (
                        access_mode IN ('" . KB_ACCESS_MODE_PUBLIC . "', '" . KB_ACCESS_MODE_INTERNAL . "', '" . KB_ACCESS_MODE_USER . "') 
                        OR (access_mode = '" . KB_ACCESS_MODE_PRIVATE . "' AND user_id = '$userId'))";
                break;
            case 'u':   //user can see public, and user items
                $where .= " AND access_mode IN ('" . KB_ACCESS_MODE_PUBLIC . "', '" . KB_ACCESS_MODE_USER . "') ";
                break;
            default:    //all other can see just public items
                $where .= " AND access_mode = '" . KB_ACCESS_MODE_PUBLIC . "'";
        }
        
        $columns = "*";

        $params->set('columns', $columns);
        $params->set('from', $this->table);
        $params->set('where', $where);
        $params->set('table', 'kb_items');
        return $this->callService('SqlTable', 'select', $params);
    }

    function insertItem($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');

        if(!$params->checkFields('tree_path')) {
            $response->set('error', $this->_getInsertFailedMessage('TreePathIsMandatory'));
            return false;
        }

        if(!$params->checkFields('access_mode')) {
            $response->set('error', $this->_getInsertFailedMessage('AccessModeIsMandatory'));
            return false;
        } else {
            /*
             * 'a' = public knowledge - fields required: subject, url, metadescription, body, tree_path
             * 'g' = internal knowledge - fields required: subject, body, tree_path
             * 'p' = private knowledge - fields required: --''--
             */
            $accessMode = $params->getField('access_mode');
            $requiredFields = array('subject', 'body');
            if($accessMode == 'a') array_push($requiredFields, 'url');
            if(!$params->checkFields($requiredFields)) {
                $response->set('error', $this->_getInsertFailedMessage('missingMandatoryFields'));
                return false;
            }
        }

        if(!$params->checkFields('user_id')) {
            $params->setField('user_id', $session->getVar('userId'));
        }
        if(!$params->checkFields('created')) {
            $params->setField('created', $db->getDateString());
        }
        $params->setField('is_indexed', 'n');

        $params->set('table', 'kb_items');
        $params->setField('item_id', 0);
        if(!strlen($params->getField('user_id'))) {
            $params->setField('user_id', $session->getVar('userId'));
        }

        if ($this->callService('SqlTable', 'insert', $params)) {
            $params->setField('item_id', $response->result);
             
            //attach files to kb item
            $ids = $params->get('attachments');
            if (is_array($ids)) {
                foreach($ids as $id) {
                    $attachmentParams = $this->createParamsObject();
                    $attachmentParams->setField('item_id', $params->get('item_id'));
                    $attachmentParams->setField('file_id', $id);
                    if (!$this->callService('Attachments', 'insertKBAttachment', $attachmentParams)) {
                        $this->state->log('error', 'Failed to attach file with error: ' . $response->error, 'Ticket');
                        return false;
                    }
                }
            }
             
             
            return $this->forceRefresh();;
        } else {
            return false;
        }
    }

    function _getInsertFailedMessage($str) {
        $result = $this->state->lang->get('FailedToCreateKnowledgeBaseItem');
        $result.= $this->state->lang->get($str);
        return $result;
    }

    function deleteItem($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');

        if(!$params->check('item_id')) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }

        $ids = explode('|', $params->get('item_id'));
        $where_ids = '';
        foreach ($ids as $id) {
            $where_ids .= (strlen($where_ids) ? ',' : '') . "'" . $db->escapeString($id) . "'";
        }


        $where_tree = '0=1';

        $tree_paths = explode('#', $params->get('tree_path'));
        foreach ($tree_paths as $id => $value) {
            $tree_paths[$id] = $value . $ids[$id] . '|';
            $where_tree .= " OR tree_path LIKE '" . $db->escapeString($tree_paths[$id]) . "%'";
        }
        //Load mail_ids
        $sql = "SELECT item_id FROM kb_items WHERE " . $where_tree;
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $sth->get('errorMessage'));
            $this->state->log('error', $sth->get('errorMessage') , 'Ticket');
            return false;
        }
        $rows = $sth->fetchAllRows();
        foreach ($rows as $row) {
            if (strlen($row[0])) {
                $where_ids .= (strlen($where_ids) ? ',' : '') . "'" . $db->escapeString($row[0]) . "'";
            }
        }

        //delete comments
        $sql = "delete from kb_comments where item_id IN (" . $where_ids . ")";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $sth->get('errorMessage'));
            $this->state->log('error', $sth->get('errorMessage'), 'User');
            return false;
        }

        //delete attachments
        $sql = "delete from kb_item_files where item_id IN (" . $where_ids . ")";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $sth->get('errorMessage'));
            $this->state->log('error', $sth->get('errorMessage'), 'User');
            return false;
        }

        //delete words
        $sql = "delete from kb_item_words where item_id IN (" . $where_ids . ")";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $sth->get('errorMessage'));
            $this->state->log('error', $sth->get('errorMessage'), 'User');
            return false;
        }

        $deleteParams = $this->createParamsObject();
        $deleteParams->set('table', 'kb_items');
        $deleteParams->set('where', 'item_id IN (' . $where_ids . ')');

        if ($this->callService('SqlTable', 'delete', $deleteParams)) {
            return $this->forceRefresh();
        } else {
            return false;
        }
    }

    function updateItem($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');

        if(!$params->check('item_id')) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }
        $params->setField('is_indexed', 'n');

        $where = "item_id = '".$db->escapeString($params->get('item_id'))."'";

        $userType = $session->getVar('userType');
        $userId = $session->getVar('userId');
        if($userType != 'a') {
            $where .= " AND (access_mode = 'a'";
            if ($userType == 'g') {
                $where .= " OR access_mode = 'g' OR access_mode = 'u' OR (access_mode = 'p' AND user_id = '$userId')";
            }
            $where .= ')';
        }

        $params->set('table', $this->table);
        $params->set('where', $where);

        if ($this->callService('SqlTable', 'update', $params)) {
            //attach files to kb item
            //delete existing attachments
            $sql = "delete from kb_item_files where item_id = '" . $db->escapeString($params->get('item_id')) . "'";
            $sth = $db->execute($sql);
            $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
            if(!$this->_checkDbError($sth)) {
                $response->set('error', $sth->get('errorMessage'));
                $this->state->log('error', $sth->get('errorMessage'), 'User');
                return false;
            }
            $ids = $params->get('attachments');

            if (is_array($ids)) {
                foreach($ids as $id) {
                    $attachmentParams = $this->createParamsObject();
                    $attachmentParams->setField('item_id', $params->get('item_id'));
                    $attachmentParams->setField('file_id', $id);
                    if (!$this->callService('Attachments', 'insertKBAttachment', $attachmentParams)) {
                        $this->state->log('error', 'Failed to attach file with error: ' . $response->error, 'Ticket');
                        return false;
                    }
                }
            }
             
            return $this->forceRefresh();;
        } else {
            return false;
        }
    }

    function forceRefresh($manual = false) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');

        if ($manual && $this->state->config->get('knowledgeBaseModule') != 'y') {
            $response->set('error', $this->state->lang->get('KnowledgeBaseNotActivated'));
            $response->set('result', false);
            return false;
        }

        if (!$manual && $this->state->config->get('knowledgeRefresh') == 'm') {
            //automatic refresh of KB is disabled
            return true;
        }

        $sql = "delete from settings where setting_key = 'lastKBUpdate'";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $sth->get('errorMessage'));
            $this->state->log('error', $sth->get('errorMessage'), 'User');
            return false;
        }
        
        return true;
    }


    function moveItem($paramsRequest) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');

        if(!$paramsRequest->check(array('item_id', 'new_tree_path', 'old_tree_path'))) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }

        $params = $this->createParamsObject();

        $params->setField('tree_path', $paramsRequest->get('new_tree_path'));
        $params->set('table', $this->table);
        $params->set('where', "item_id='" . $db->escapeString($paramsRequest->get('item_id')) . "'");
        if ($this->callService('SqlTable', 'update', $params)) {
             
            $sql = "UPDATE kb_items SET tree_path=CONCAT('" .
            $db->escapeString($paramsRequest->get('new_tree_path')) . $db->escapeString($paramsRequest->get('item_id')) . '|' .
			"',
				SUBSTRING(tree_path, " . (strlen($paramsRequest->get('old_tree_path'))+1) . ") 
			)
			WHERE tree_path LIKE '" . $db->escapeString($paramsRequest->get('old_tree_path')) . "'";
            $sth = $db->execute($sql);
            $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
            if(!$this->_checkDbError($sth)) {
                $response->set('error', $sth->get('errorMessage'));
                $this->state->log('error', $sth->get('errorMessage'), 'User');
                return false;
            }
            if ($this->forceRefresh()) {
                $response->set('result', true);
                return true;
            }
        }
        return false;
    }

    function loadSearchItems($query) {
        $response =& $this->getByRef('response');
   	   	$db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');

        $params = $this->createParamsObject();
        $params->set('query', $query);
        $params->set('limit', 10);

        $items = array();
        //load ticket
        if ($ret = $this->searchItems($params)) {
            $res = & $this->response->getByRef('result');
            if ($res['count'] > 0) {
                $items = $res['rs']->getRows($res['md']);
            }
        }
        return $items;

    }

    function getSimilarItemsToItem($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        if ($this->state->config->get('knowledgeBaseModule') != 'y') {
            $response->set('error', $this->state->lang->get('KnowledgeBaseNotActivated'));
            $response->set('result', false);
            return false;
        }

        if(!$params->check(array('item_id'))) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }

        $columns = "i.item_id, i.tree_path, i.subject, i.created, i.url, r.rank";
        $from = "kb_items i
				INNER JOIN (
					SELECT DISTINCT(iw2.item_id), SUM( w.ranking * iw1.word_ranking * iw2.word_ranking ) AS rank
					FROM kb_item_words iw2
					INNER JOIN kb_item_words iw1 ON ( iw1.word_id = iw2.word_id
					AND iw1.item_id <> iw2.item_id )
					INNER JOIN words w ON ( iw1.word_id = w.word_id )
					WHERE iw1.item_id ='" . $db->escapeString($params->get('item_id')) . "'
					GROUP BY iw2.item_id
				)r ON ( r.item_id = i.item_id )";

        $where = "i.access_mode = 'a'";
        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'kb_items');
        $params->set('order', 'rank DESC');
        if ($ret = $this->callService('SqlTable', 'select', $params)) {
            $rs = $response->getResultVar('rs');
            foreach ($rs->rows as $id=>$row) {
                list($url, $parents_subject) = $this->getFullUrl($rs->rows[$id][1], $rs->rows[$id][4], $rs->rows[$id][2]);
                $rs->rows[$id][] = $url;
                $rs->rows[$id][] = $parents_subject;
            }
            $response->setResultVar('rs', $rs);

            $md = $response->getResultVar('md');
            $md->addColumn('full_path', 'string');
            $md->addColumn('full_parent_subject', 'string');
            $response->setResultVar('md', $md);
        }
        return $ret;
    }

    function searchItems($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        if ($this->state->config->get('knowledgeBaseModule') != 'y') {
            $response->set('error', $this->state->lang->get('KnowledgeBaseNotActivated'));
            $response->set('result', false);
            return false;
        }


        if(!$params->check(array('query'))) {
            $params->set('query', '');
        }

        $objKeyWords = QUnit::newObj('App_Service_KeyWords');
        list($count, $arrWords) = $objKeyWords->getWords($params->get('query'));

        if ($count > 0) {

            $inValue = '';
            foreach ($arrWords as $word => $count) {
                if (strlen($word)) {
                    $inValue .= (strlen($inValue) ? ',':'') . "'" . $db->escapeString(md5($word)) . "'";
                }
            }
             
            $columns = "i.item_id, i.tree_path, i.subject, i.created, url, rank";
             
            $from = "kb_items i
					INNER JOIN (
						SELECT iw.item_id, SUM(w.ranking * iw.word_ranking) as rank
						FROM words w
						INNER JOIN kb_item_words iw ON (w.word_id=iw.word_id)
						WHERE w.word_id IN(" . $inValue . ")
						GROUP BY iw.item_id
					) r ON (i.item_id = r.item_id)";
             
            $where = "i.access_mode = 'a'";
            $params->set('columns', $columns);
            $params->set('from', $from);
            $params->set('where', $where);
            $params->set('table', 'kb_items');
            $params->set('order', 'rank DESC');
            if ($ret = $this->callService('SqlTable', 'select', $params)) {

                $rs = $response->getResultVar('rs');
                foreach ($rs->rows as $id=>$row) {
                    list($url, $parents_subject) = $this->getFullUrl($rs->rows[$id][1], $rs->rows[$id][4], $rs->rows[$id][2]);
                    $rs->rows[$id][] = $url;
                    $rs->rows[$id][] = $parents_subject;
                }
                $response->setResultVar('rs', $rs);

                $md = $response->getResultVar('md');
                $md->addColumn('full_path', 'string');
                $md->addColumn('full_parent_subject', 'string');
                $response->setResultVar('md', $md);
            }
            return $ret;
        } else {
            $response->set('error', $this->state->lang->get('InvalidSearchQuery'));
            $response->set('result', false);
            return false;
        }
    }

    function siteMapItems($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        if ($this->state->config->get('knowledgeBaseModule') != 'y') {
            $response->set('error', $this->state->lang->get('KnowledgeBaseNotActivated'));
            $response->set('result', false);
            return false;
        }


        $columns = "i.item_id, i.tree_path, i.subject, i.created, url";
         
        $from = "kb_items i";
         
        $where = "i.access_mode = 'a'";
        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', 'kb_items');
        $params->set('limit', 50000);
        if ($ret = $this->callService('SqlTable', 'select', $params)) {
            $rs = $response->getResultVar('rs');
            foreach ($rs->rows as $id=>$row) {
                list($url, $parents_subject) = $this->getFullUrl($rs->rows[$id][1], $rs->rows[$id][4], $rs->rows[$id][2]);
                $rs->rows[$id][] = $url;
                $rs->rows[$id][] = $parents_subject;
            }
            $response->setResultVar('rs', $rs);

            $md = $response->getResultVar('md');
            $md->addColumn('full_path', 'string');
            $md->addColumn('full_parent_subject', 'string');
            $response->setResultVar('md', $md);
        }
        return $ret;
    }
    
    
    function getFullUrl($tree_path, $current_item_url) {
        static $cache;
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $arrItems = explode('|', $tree_path);

        $items_to_load = array();
        foreach ($arrItems as $parent_item) {
            if (strlen($parent_item) && $parent_item != '0') {
                if (!isset($cache[$parent_item])) {
                    $items_to_load[] = $parent_item;
                }
            }
        }

        if (!empty($items_to_load)) {
            $sql = "SELECT item_id, url, subject FROM kb_items WHERE item_id IN ('" . implode("','", $items_to_load) . "')";
            $sth = $db->execute($sql);
            $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
            if(!$this->_checkDbError($sth)) {
                $response->set('error', $sth->get('errorMessage'));
                $this->state->log('error', $sth->get('errorMessage'), 'User');
                return false;
            }
            while($row = $sth->fetchArray()) {
                $cache[$row['item_id']] = array($row['url'], $row['subject']);
            }
        }

        $url = '';
        $title = '';
        foreach ($arrItems as $parent_item) {
            if (strlen($parent_item) && $parent_item != '0') {
                if (isset($cache[$parent_item])) {
                    $url .= (strlen($url) ? '/':'') . $cache[$parent_item][0];
                    $title .= (strlen($title) ? ' / ':'') . $cache[$parent_item][1];
                }
            }
        }

        $url .= (strlen($url) ? '/':'') . $current_item_url . '.html';


        return array($url, $title);
    }
}
?>