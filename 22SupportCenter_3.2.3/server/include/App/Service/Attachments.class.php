<?php
/**
 *   Handler class for Mail Attachments
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Attachments extends QUnit_Rpc_Service {

    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
            case 'getDmAttachmentsList':
            case 'getKBAttachmentsList':
                return $this->callService('Users', 'authenticate', $params);
            default:
                return false;
                break;
        }
    }

    function insertMailAttachment($params) {
        $db = $this->state->get('db');
        $response =& $this->getByRef('response');
         
        if (!$params->checkFields(array('mail_id', 'file_id'))) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }
         
        $params->set('table', 'mail_attachments');
        return $this->callService('SqlTable', 'insert', $params);
    }

    function getMailAttachmentsList($params) {
        $db =& $this->state->getByRef('db');

        $params->set('columns', "*");
        $params->set('from', "mail_attachments");
        $where = "1";
        if($id = $params->get('file_id')) {
            $where .= " and file_id = '".$db->escapeString($id)."'";
        }
        if($id = $params->get('mail_id')) {
            $where .= " and mail_id = '".$db->escapeString($id)."'";
        }
        $params->set('where', $where);
        $params->set('table', 'mail_attachments');
        return $this->callService('SqlTable', 'select', $params);
    }

    function checkMailAttachmentRights($file_id) {
        $session = QUnit::newObj('QUnit_Session');
        $db = $this->state->get('db');
        $response =& $this->getByRef('response');
         
        switch ($session->getVar('userType')) {
            case 'a':
            case 'g':
                return true;
                break;
            case 'u':
                 
                $sql = "SELECT count(*) as access_test
                        FROM mail_attachments ma
                        INNER JOIN mails ON (mails.mail_id = ma.mail_id)
                        INNER JOIN tickets ON (mails.ticket_id = tickets.ticket_id)
                        INNER JOIN users ON (tickets.customer_id = users.user_id)
                        WHERE ma.file_id = '" . $db->escapeString($file_id) . "'";
                if (strlen($session->getVar('groupId'))) {
                    $sql.=  " AND (tickets.customer_id = '" . $db->escapeString($session->getVar('userId')) . "' OR
                                    (users.groupid = '" . $db->escapeString($session->getVar('groupId')) . "'))";
                } else {
                    $sql.=  " AND tickets.customer_id = '" . $db->escapeString($session->getVar('userId')) . "'";
                }
                $sth = $db->execute($sql);
                $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
                if(QUnit_Object::isError($sth)) {
                    $this->state->log('error', $this->state->lang->get('dbError').$sth->get('errorMessage'), 'Attachment');
                    return false;
                }
                $rs = QUnit::newObj('QUnit_Rpc_ResultSet');
                $rs->setRows($sth->fetchAllRows());
                $md = QUnit::newObj('QUnit_Rpc_MetaData', 'mail_attachments');
                $md->setColumnNames($sth->getNames());
                $md->setColumnTypes($sth->getTypes());
                $response->setResultVar('md', $md);
                $response->setResultVar('rs', $rs);
                $rows = $rs->getRows($md);

                if (count($rows) > 0 && $rows[0]['access_test'] > 0) {
                    return true;
                }

                 
                break;
            default:   //not logged in user is not able to download files
                return false;
                break;
        }
        return false;
    }


    function insertKBAttachment($params) {
        $db = $this->state->get('db');
        $response =& $this->getByRef('response');
         
        if (!$params->checkFields(array('item_id', 'file_id'))) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }
        $params->set('table', 'kb_item_files');
        return $this->callService('SqlTable', 'insert', $params);
    }

    function getKBAttachmentsList($params) {
        $db =& $this->state->getByRef('db');

        $params->set('columns', "*");
        $params->set('from', "kb_item_files kbf INNER JOIN files f ON (f.file_id = kbf.file_id)");
        $where = "1";
        if($id = $params->get('file_id')) {
            $where .= " and kbf.file_id = '".$db->escapeString($id)."'";
        }
        if($id = $params->get('item_id')) {
            $where .= " and kbf.item_id = '".$db->escapeString($id)."'";
        }
        $params->set('where', $where);
        $params->set('table', 'kb_item_files');
        return $this->callService('SqlTable', 'select', $params);
    }

    function insertDmAttachment($params) {
        $db = $this->state->get('db');
        $response =& $this->getByRef('response');

        if (!$params->checkFields(array('productid', 'file_id'))) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }
        $params->set('table', 'product_files');
        return $this->callService('SqlTable', 'insert', $params);
    }


    function getDmAttachmentsList($params) {
        $session = QUnit::newObj('QUnit_Session');
        $db = $this->state->get('db');
        $response =& $this->getByRef('response');

        $params->set('columns', "*");
        $params->set('from', "product_files pf INNER JOIN files f ON (f.file_id = pf.file_id)");
        $where = "1";
        if($id = $params->get('file_id')) {
            $where .= " and pf.file_id = '".$db->escapeString($id)."'";
        }
        if($id = $params->get('productid')) {
            $where .= " and pf.productid = '".$db->escapeString($id)."'";
        }
        $params->set('where', $where);
        $params->set('table', 'product_files');
        return $this->callService('SqlTable', 'select', $params);
    }


    function isKBFile($fileId) {
        $session = QUnit::newObj('QUnit_Session');
        $db = $this->state->get('db');
        $response =& $this->getByRef('response');
        $sql = "SELECT count(*) as access_test
                FROM kb_item_files
                WHERE file_id = '" . $db->escapeString($fileId) . "'";

        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(QUnit_Object::isError($sth)) {
            $this->state->log('error', $this->state->lang->get('dbError').$sth->get('errorMessage'), 'Attachment');
            return false;
        }
        $rs = QUnit::newObj('QUnit_Rpc_ResultSet');
        $rs->setRows($sth->fetchAllRows());
        $md = QUnit::newObj('QUnit_Rpc_MetaData', 'mail_attachments');
        $md->setColumnNames($sth->getNames());
        $md->setColumnTypes($sth->getTypes());
        $response->setResultVar('md', $md);
        $response->setResultVar('rs', $rs);
        $rows = $rs->getRows($md);

        if (count($rows) > 0 && $rows[0]['access_test'] > 0) {
            return true;
        }
        return false;
    }

    function isMailAttachmentFile($fileId) {
        $session = QUnit::newObj('QUnit_Session');
        $db = $this->state->get('db');
        $response =& $this->getByRef('response');
        $sql = "SELECT count(*) as access_test
                FROM mail_attachments
                WHERE file_id = '" . $db->escapeString($fileId) . "'";

        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(QUnit_Object::isError($sth)) {
            $this->state->log('error', $this->state->lang->get('dbError').$sth->get('errorMessage'), 'Attachment');
            return false;
        }
        $rs = QUnit::newObj('QUnit_Rpc_ResultSet');
        $rs->setRows($sth->fetchAllRows());
        $md = QUnit::newObj('QUnit_Rpc_MetaData', 'mail_attachments');
        $md->setColumnNames($sth->getNames());
        $md->setColumnTypes($sth->getTypes());
        $response->setResultVar('md', $md);
        $response->setResultVar('rs', $rs);
        $rows = $rs->getRows($md);

        if (count($rows) > 0 && $rows[0]['access_test'] > 0) {
            return true;
        }
        return false;
    }

    function isDownloadManagerFile() {
        return isset($_REQUEST['orderid']);
    }

    function checkDownloadManagerRights($file_id) {
        $session = QUnit::newObj('QUnit_Session');
        $db = $this->state->get('db');
        $response =& $this->getByRef('response');

        $session = QUnit::newObj('QUnit_Session');
        switch ($session->getVar('userType')) {
            case 'a':   //admin
            case 'g':   //agent
                return true;    //admin and agent can download all files
            case 'u':
                break;
            default:
                return false; //not logged in users are not allowed to download files
        }

        $sql = 'SELECT count(*) as access_test
                FROM product_orders po
                INNER JOIN products p ON (p.productid = po.productid)
                WHERE p.is_enabled = \'y\'';

        if (strlen($session->getVar('groupId'))) {
            $sql .= " AND (po.user_id = '" . $db->escapeString($session->getVar('userId')) .
                "' OR po.groupid='" . $db->escapeString($session->getVar('groupId')) . "')";
        } else {
            $sql .= " AND po.user_id = '" . $db->escapeString($session->getVar('userId')) . "'";
        }

        $sql .= " AND valid_from <= '" . $db->getDateString() . "'";
        $sql .= " AND (valid_until is null OR valid_until >= '" . $db->getDateString() . "')";
        $sql .= " AND (po.max_downloads = 0 OR po.max_downloads >= (SELECT count(*) FROM product_downloads pd WHERE pd.orderid=po.orderid))";
        $sql .= " AND po.orderid='" . $db->escapeString($_REQUEST['orderid']) . "'";

        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(QUnit_Object::isError($sth)) {
            $this->state->log('error', $this->state->lang->get('dbError').$sth->get('errorMessage'), 'Attachment');
            return false;
        }
        $rs = QUnit::newObj('QUnit_Rpc_ResultSet');
        $rs->setRows($sth->fetchAllRows());
        $md = QUnit::newObj('QUnit_Rpc_MetaData', 'mail_attachments');
        $md->setColumnNames($sth->getNames());
        $md->setColumnTypes($sth->getTypes());
        $response->setResultVar('md', $md);
        $response->setResultVar('rs', $rs);
        $rows = $rs->getRows($md);

        if (count($rows) > 0 && $rows[0]['access_test'] > 0) {
            return true;
        }

        return false;
    }

    function logDownload($file_id) {
        $session = QUnit::newObj('QUnit_Session');
        $db = $this->state->get('db');
        $response =& $this->getByRef('response');

        $orderId = $db->escapeString($_REQUEST['orderid']);
        $file_id = $db->escapeString($file_id);
        $ip = $db->escapeString($_SERVER['REMOTE_ADDR']);


        $sql = "INSERT INTO product_downloads (file_id, productid, user_id, orderid, created, ip)
        SELECT '$file_id', productid, '" . $session->getVar('userId') . "', '$orderId', NOW(), '$ip' FROM product_orders WHERE orderid='$orderId'";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(QUnit_Object::isError($sth)) {
            $this->state->log('error', $this->state->lang->get('dbError').$sth->get('errorMessage'), 'Attachment');
            return false;
        }
        return true;
    }

    function downloadFile($file_id, $download_type = 'attachment') {
        $session = QUnit::newObj('QUnit_Session');
        
        //admin and agent can see all files
        if ($session->getVar('userType') == 'a' || $session->getVar('userType') == 'g') {
            return $this->startDownloading($file_id, $download_type);
        }
        
        
        if ($this->isDownloadManagerFile()) {
            if ($this->checkDownloadManagerRights($file_id)) {
                if ($this->logDownload($file_id)) {
                    return $this->startDownloading($file_id, $download_type);
                }
            }
        } else if ($this->isKBFile($file_id)) {
            return $this->startDownloading($file_id, $download_type);
        } else if ($this->isMailAttachmentFile($file_id)) {
            if ($this->checkMailAttachmentRights($file_id)) {
                return $this->startDownloading($file_id, $download_type);
            }
        }

        $this->state->log('error', $this->state->lang->get('noRightsForAttachmentDownload'), 'Attachment');
        echo $this->state->lang->get('noRightsForAttachmentDownload');
        return false;
    }

    function startDownloading($file_id, $download_type = 'attachment') {
        $objFile = QUnit::newObj('App_Service_Files');
        $objFile->state = $this->state;
        $objFile->response = $this->response;
        return $objFile->downloadFile($file_id, $download_type);
    }

}
?>