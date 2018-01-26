<?php
/**
 *   Handler class for Products
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_Products extends QUnit_Rpc_Service {

    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
            case 'deleteProduct':
            case 'insertProduct':
            case 'updateProduct':
            case 'getProduct':
            case 'getProductsList':
                return $this->callService('Users', 'authenticateAdmin', $params);
                break;
            default:
                return false;
                break;
        }
    }

    function getProductsList($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $columns = "*";
        $from = "products";
        $where = "1";

        if(strlen($id = $params->get('productid'))) {
            $where .= " and productid = '".$db->escapeString($id) . "'";
        }
        if(strlen($id = $params->get('product_code'))) {
            $where .= " and product_code = '".$db->escapeString($id) . "'";
        }
        
        if(strlen($id = $params->get('product_name'))) {
            $where .= " and name LIKE '%".$db->escapeString($id) . "%'";
        }
        if(strlen($id = $params->get('is_enabled'))) {
            $where .= " and is_enabled = '".$db->escapeString($id) . "'";
        }
        if(strlen($id = $params->get('subtitle'))) {
            $where .= " and subtitle LIKE '%".$db->escapeString($id) . "%'";
        }
        if($id = $params->get('created_from')) {
            $where .= " AND created >= '" . $db->escapeString($id) . "'";
        }
        if($id = $params->get('created_to')) {
            $where .= " AND created <= '" . $db->escapeString($id) . "'";
        }
        
        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        if (!$params->get('order')) {
            $params->set('order', 'product_code');
        }
        $params->set('table', $from);
        return $this->callService('SqlTable', 'select', $params);
    }

    function getProduct($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        if(!$params->check(array('productid'))) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }

        $columns = "p.*, 
                    GROUP_CONCAT(bundled_productid SEPARATOR '|') as sub_products";
        $from = "products p LEFT JOIN product_bundles pb ON (p.productid = pb.productid)";
        $where = "1";

        if(strlen($id = $params->get('productid'))) {
            $where .= " and p.productid = '".$db->escapeString($id) . "'";
        }

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('group', 'p.productid');
        if (!$params->get('order')) {
            $params->set('order', 'product_code');
        }
        $params->set('table', "products");
        return $this->callService('SqlTable', 'select', $params);
    }

    function deleteProduct($params) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
         
        $ids = explode('|',$params->get('productid'));
         
        $where_ids = '';
        foreach ($ids as $id) {
            if (strlen(trim($id))) {
                $where_ids .= (strlen($where_ids) ? ', ': '');
                $where_ids .= "'" . $db->escapeString(trim($id)) . "'";
            }
        }

        if(!$params->check(array('productid')) || !strlen($where_ids)) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }
         
        //TODO dorobit ochranu, aby sa nedal zmazat product s objednavkami
         
        $sql = "DELETE FROM product_files WHERE productid IN (" . $where_ids . ")";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $sth->get('errorMessage'));
            $this->state->log('error', $sth->get('errorMessage'), 'Fields');
            return false;
        }
         
        $params->set('table', 'products');
        $params->set('where', "productid IN (" . $where_ids . ")");
        return $this->callService('SqlTable', 'delete', $params);
    }
     
    /**
     * Insert mail priority
     *
     * @param QUnit_Rpc_Params $params
     */
    function insertProduct($params) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
         
        if(!$params->checkFields(array('product_code', 'name'))) {
            $response->set('error', $this->state->lang->get('missingMandatoryFields'));
            return false;
        }

        if (!strlen($params->getField('productid'))) {
            $params->setField('productid', md5(uniqid(rand(), true)));
        }
        if (!strlen($params->getField('created'))) {
            $params->setField('created', $db->getDateString());
        }
        if ($params->getField('is_enabled') != 'y') {
            $params->setField('is_enabled', 'n');
        }
        if ($params->getField('send_notification') != 'y') {
            $params->setField('send_notification', 'n');
        }
        if (!strlen($params->getField('tree_path'))) {
            $params->setField('tree_path', '0|');
        }
        if (!strlen($params->getField('max_downloads'))) {
            $params->setField('max_downloads', '0');
        }
        if (!strlen($params->getField('valid_days'))) {
            $params->setField('valid_days', '0');
        }
        
        $params->set('table', 'products');
        if ($res = $this->callService('SqlTable', 'insert', $params)) {
            //attach files to kb item
            $ids = $params->get('attachments');
            if (is_array($ids)) {
                foreach($ids as $id) {
                    $attachmentParams = $this->createParamsObject();
                    $attachmentParams->setField('productid', $params->get('productid'));
                    $attachmentParams->setField('file_id', $id);
                    if (!$this->callService('Attachments', 'insertDmAttachment', $attachmentParams)) {
                        $this->state->log('error', 'Failed to attach file with error: ' . $response->error, 'DownloadManager');
                        return false;
                    }
                }
            }
        }
         
        return $res;
    }
     
    function updateProduct($params) {
        $response =& $this->getByRef('response');
        $db =& $this->state->getByRef('db');
         
        if(!$params->check(array('productid'))) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }

        if(!$params->checkFields(array('name', 'product_code'))) {
            $response->set('error', $this->state->lang->get('missingMandatoryFields'));
            return false;
        }
         
        $params->set('table', 'products');
        $params->set('where', "productid = '".$db->escapeString($params->get('productid')) . "'");
        if ($this->callService('SqlTable', 'update', $params)) {
            $this->updateAttachments($params);
            $this->updateSubProducts($params);
        }
        return true;
    }

    function updateAttachments($params) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
        //attach files to product
        //delete existing attachments
        $sql = "delete from product_files where productid = '" . $db->escapeString($params->get('productid')) . "'";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $sth->get('errorMessage'));
            $this->state->log('error', $sth->get('errorMessage'), 'DownloadManager');
            return false;
        }
        $ids = $params->get('attachments');

        if (is_array($ids)) {
            foreach($ids as $id) {
                $attachmentParams = $this->createParamsObject();
                $attachmentParams->setField('productid', $params->get('productid'));
                $attachmentParams->setField('file_id', $id);
                if (!$this->callService('Attachments', 'insertDmAttachment', $attachmentParams)) {
                    $this->state->log('error', 'Failed to attach file with error: ' . $response->error, 'DownloadManager');
                    return false;
                }
            }
        }
        return true;
    }

    function updateSubProducts($params) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');

         
        //delete existing assignments
        $sql = "delete from product_bundles where productid = '" . $db->escapeString($params->get('productid')) . "'";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $sth->get('errorMessage'));
            $this->state->log('error', $sth->get('errorMessage'), 'DownloadManager');
            return false;
        }
        $ids = explode('|', $params->get('sub_products'));

        if (is_array($ids)) {
            foreach($ids as $id) {
                if (strlen($id) && $id != $params->get('productid')) {
                    $bundleParams = $this->createParamsObject();
                    $bundleParams->setField('productid', $params->get('productid'));
                    $bundleParams->setField('bundled_productid', $id);
                    if (!$this->callService('ProductBundles', 'insertBundle', $bundleParams)) {
                        $this->state->log('error', 'Failed to assign product with error: ' . $response->error, 'DownloadManager');
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Load Product row and store it into array
     */
    function loadProduct($params) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
        $session = QUnit::newObj('QUnit_Session');
        $product = false;
        //load ticket
        if ($params->check(array('productid')) || $params->check(array('product_code'))) {
            $paramsProduct = $this->createParamsObject();
            $paramsProduct->set('productid', $params->get('productid'));
            $paramsProduct->set('product_code', $params->get('product_code'));
            if ($ret = $this->callService('Products', 'getProductsList', $paramsProduct)) {
                $res = & $response->getByRef('result');
                if ($res['count'] > 0) {
                    $product = $res['rs']->getRows($res['md']);
                    $product = $product[0];
                }
            }
        }
        return $product;
    }

}
?>