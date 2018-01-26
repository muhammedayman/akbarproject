<?php
/**
 *   Handler class for Products
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_ProductOrders extends QUnit_Rpc_Service {

    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
            case 'getProductOrder':
            case 'getProductOrdersList':
                return $this->callService('Users', 'authenticate', $params);
                break;
            case 'deleteOrder':
            case 'insertOrder':
            case 'updateOrder':
            case 'getOrder':
            case 'getOrdersList':
                return $this->callService('Users', 'authenticateAdmin', $params);
                break;
            default:
                return false;
                break;
        }
    }

    function getProductOrdersList($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');

        $params->set('count_columns', 'po.orderid');
        $columns = "po.orderid as orderid,
                    p.name as name, 
                    p.product_code as product_code, 
                    p.subtitle as product_subtitle, 
                    p.productid as productid, 
                    p.productid as pid, 
                    p.tree_path as ptp,
                    p.tree_path";

        $from = "product_orders po
                 INNER JOIN products p ON (p.productid = po.productid)";

        $treePath = $db->escapeString($params->get('tree_path'));
        $columns .= " ,(
                        SELECT count(*) 
                        FROM products p1 
                        INNER JOIN product_orders po1 ON (p1.productid = po1.productid)
                        WHERE p1.is_enabled='y' AND tree_path = CONCAT(CONCAT(ptp, pid), '|')";
        $where = "p.is_enabled='y'";
        $where .= " AND p.tree_path = '".$treePath."'";

        $whereOrders = '';
        if (strlen($session->getVar('groupId'))) {
            $whereOrders = " AND (po.groupid = '" . $session->getVar('groupId') . "' OR po.user_id = '" . $session->getVar('userId') . "')";
        } else {
            $whereOrders = " AND po.user_id = '" . $session->getVar('userId') . "'";
        }

        $columns .= $whereOrders;
        $where .= $whereOrders;

        $columns .= ") as children_count";


        $params->set('count_group_by', 'no');
        $params->set('distinct', true);
        $params->set('count_columns', 'po.orderid');
        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('group', "po.orderid");
        if (!$params->get('order')) {
            $params->set('order', 'p.name');
        }
        $params->set('table', 'product_orders');
        return $this->callService('SqlTable', 'select', $params);
    }

    function getOrdersList($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $params->set('count_columns', 'po.orderid');
        $columns = "po.orderid as orderid,
                    po.created as created, 
                    p.name as product_name, 
                    p.subtitle as product_subtitle, 
                    p.product_code as product_code, 
                    g.groupid as groupid, 
                    g.group_name as group_name,
                    u.email as customer_email, 
                    u.name as customer_name, 
                    po.licenseid as licenseid, 
                    po.valid_from as valid_from, 
                    po.valid_until as valid_until, 
                    count(pd.ip) as downloads,
                    po.max_downloads as max_downloads";
        $from = "product_orders po
                 INNER JOIN products p ON (p.productid = po.productid)
                 LEFT JOIN users u ON (u.user_id = po.user_id)
                 LEFT JOIN groups g ON (g.groupid = po.groupid)
                 LEFT JOIN product_downloads pd ON (po.orderid = pd.orderid)";
        $where = "1";


        if(strlen($id = $params->get('product_name'))) {
            $where .= " and p.name LIKE '%".$db->escapeString($id) . "%'";
        }
        if(strlen($id = $params->get('subtitle'))) {
            $where .= " and p.subtitle LIKE '%".$db->escapeString($id) . "%'";
        }
        if($id = $params->get('created_from')) {
            $where .= " AND po.created >= '" . $db->escapeString($id) . "'";
        }
        if($id = $params->get('created_to')) {
            $where .= " AND po.created <= '" . $db->escapeString($id) . "'";
        }

        if($id = $params->get('groupid')) {
            $where .= " AND po.groupid = '" . $db->escapeString($id) . "'";
        }
        if($id = $params->get('email')) {
            $where .= " AND u.email = '" . $db->escapeString($id) . "'";
        }


        $params->set('count_group_by', 'no');
        $params->set('distinct', true);
        $params->set('count_columns', 'po.orderid');
        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('group', "po.orderid");
        if (!$params->get('order')) {
            $params->set('order', 'created');
        }
        $params->set('table', 'product_orders');
        return $this->callService('SqlTable', 'select', $params);
    }

    function getOrdersListAllFields($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $columns = "po.*, u.email as email";
        $from = "product_orders po
                 LEFT JOIN users u ON (u.user_id = po.user_id)";
        $where = "1";

        if(strlen($id = $params->get('orderid'))) {
            $where .= " and orderid = '".$db->escapeString($id) . "'";
        }

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        if (!$params->get('order')) {
            $params->set('order', 'created');
        }
        $params->set('table', 'product_orders');
        return $this->callService('SqlTable', 'select', $params);
    }

    function getProductOrder($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');

        if(!($params->check(array('orderid')) || $params->check(array('licenseid')))) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }

        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $columns = "p.name, p.description, product_code, p.subtitle as product_subtitle,
                    po.orderid, po.valid_from, po.valid_until, 
                    po.max_downloads, po.licenseid, po.order_number, po.order_info, po.price, po.created,
                    count(pd.ip) as downloads, 
                    u.email as email";
        $from = "product_orders po
                 INNER JOIN products p ON (po.productid = p.productid)
                 LEFT JOIN product_downloads pd ON (pd.orderid = po.orderid AND pd.user_id='" . $db->escapeString($session->getVar('userId')) . "')
                 LEFT JOIN users u ON (u.user_id = po.user_id)";

        $where = "p.is_enabled='y'";
        if (strlen($params->get('orderid'))) {
            $where .= " AND po.orderid = '".$db->escapeString($params->get('orderid')) . "'";
        }

        if (strlen($params->get('licenseid'))) {
            $where .= " AND po.licenseid = '".$db->escapeString($params->get('licenseid')) . "'";
        }
        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('group', "po.orderid");
        $params->set('table', "product_orders");
        return $this->callService('SqlTable', 'select', $params);
    }

    function getOrder($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        if(!$params->check(array('orderid'))) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }

        return $this->getOrdersListAllFields($params);
    }

    function deleteOrder($params) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
         
        $ids = explode('|',$params->get('orderid'));
         
        $where_ids = '';
        foreach ($ids as $id) {
            if (strlen(trim($id))) {
                $where_ids .= (strlen($where_ids) ? ', ': '');
                $where_ids .= "'" . $db->escapeString(trim($id)) . "'";
            }
        }

        if(!$params->check(array('orderid')) || !strlen($where_ids)) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }
         
        $sql = "DELETE FROM product_downloads WHERE orderid IN (" . $where_ids . ")";
        $sth = $db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(!$this->_checkDbError($sth)) {
            $response->set('error', $sth->get('errorMessage'));
            $this->state->log('error', $sth->get('errorMessage'), 'Fields');
            return false;
        }
         
        $params->set('table', 'product_orders');
        $params->set('where', "orderid IN (" . $where_ids . ")");
        return $this->callService('SqlTable', 'delete', $params);
    }
     
    /**
     * Insert mail priority
     *
     * @param QUnit_Rpc_Params $params
     */
    function insertOrder($params) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
         
        //init valid from if not defined
        if (!strlen($params->getField('valid_from'))) {
            $valid_from_time = time();
            $params->setField('valid_from', $db->getDateString($valid_from_time));
        }

        if (strlen($params->get('productid'))) {
            $products = QUnit::newObj('App_Service_Products');
            $products->state = $this->state;
            $products->response = $response;
            if ($product = $products->loadProduct($params)) {
                if ($product['valid_days'] > 0) {
                    $params->setField('valid_until', $db->getDateString($valid_from_time + $product['valid_days'] * 24 * 3600));
                }
            } else {
                $response->set('error', $this->state->lang->get('ProductDoesNotExist'));
                return false;
            }
        }

        if (strlen($params->get('email'))) {
            $users = QUnit::newObj('App_Service_Users');
            $users->state = $this->state;
            $users->response = $response;
            if ($user = $users->loadUser($params)) {
                $params->setField('user_id', $user['user_id']);
            } else {
                $response->set('error', $this->state->lang->get('failedToLoadUser'));
                return false;
            }
        }
        if (!strlen($params->getField('licenseid'))) {
            $params->setField('licenseid', $this->generateLicenseId(8));
        }
        if (!strlen($params->getField('orderid'))) {
            $params->setField('orderid', md5(uniqid(rand(), true)));
        }
        if (!strlen($params->getField('max_downloads'))) {
            $params->setField('max_downloads', 0);
        }
        if (!strlen($params->getField('created'))) {
            $params->setField('created', $db->getDateString());
        }
        if (!strlen($params->getField('price'))) {
            $params->setField('price', '0');
        }

        $params->set('table', 'product_orders');
        if ($res = $this->callService('SqlTable', 'insert', $params)) {
            //notify customer about order
            $this->sendOrderNotification($params);

            //assign also bundles
            $paramsBundles = $this->createParamsObject();
            $paramsBundles->set('productid', $params->get('productid'));
            if ($res = $this->callService('ProductBundles', 'getBundlesList', $paramsBundles)) {

                $result = & $response->getByRef('result');
                if ($result['count'] > 0) {
                    $bundles = $result['rs']->getRows($result['md']);
                    foreach ($bundles as $bundle) {
                        $paramsBundle = $this->createParamsObject();
                        $paramsBundle->setField('productid', $bundle['bundled_productid']);
                        $paramsBundle->setField('groupid', $params->get('groupid'));
                        $paramsBundle->setField('user_id', $params->get('user_id'));
                        $paramsBundle->setField('order_number', $params->get('order_number'));
                        $paramsBundle->setField('order_info', $params->get('order_info'));
                        $paramsBundle->setField('created', $params->get('created'));
                        $paramsBundle->setField('valid_from', $params->get('valid_from'));
                        $paramsBundle->setField('valid_until', $params->get('valid_until'));

                        if (!($res = $this->callService('ProductOrders', 'insertOrder', $paramsBundle))) {
                            return $res;
                        }
                    }
                }


            }

        }
        return $res;
    }
     

    function sendOrderNotification($params) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');

        //load product and exit if notification mail not defined
        $objProducts = QUnit::newObj('App_Service_Products');
        $objProducts->state = $this->state;
        $objProducts->response = $response;
        if ($product = $objProducts->loadProduct($params)) {
            if ($product['send_notification'] != 'y') {
                return true;
            }
        } else {
            return false;
        }

        $recipients = array();

        //load users
        if (strlen($params->get('groupid')) || strlen($params->get('user_id'))) {
            $sql = "SELECT email, name, hash FROM users WHERE disable_dm_notifications <> 'y' AND (false ";
            if (strlen($id = $params->get('groupid'))) {
                $sql .= " OR groupid = '" . $db->escapeString($id) . "'";
            }
            if (strlen($id = $params->get('user_id'))) {
                $sql .= " OR user_id = '" . $db->escapeString($id) . "'";
            }
            $sql .= ')';

            $sth = $db->execute($sql);
            $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
            if(!$this->_checkDbError($sth)) {
                $response->set('error', $sth->get('errorMessage'));
                $this->state->log('error', $sth->get('errorMessage') , 'Order');
                return false;
            }
            $recipients = $sth->fetchAllRowsAsoc();
        }

        //send notification mail to all users assigned to order
        foreach ($recipients as $recipient) {
            $notifParams = $this->createParamsObject();
            $notifParams->set('template_text', $product['notification_body']);
            $notifParams->set('subject', $product['notification_subject']);
            $notifParams->set('to', $recipient['email']);

            $notifParams->set('product_code', $product['product_code']);
            $notifParams->set('product_name', $product['name']);
            $notifParams->set('product_subtitle', $product['subtitle']);
            $notifParams->set('product_description', $product['description']);

            if ($params->get('max_downloads') == 0) {
                $notifParams->set('max_downloads', $this->state->lang->get('unlimited'));
            } else {
                $notifParams->set('max_downloads', $params->get('max_downloads'));
            }
            $notifParams->set('valid_from', $params->get('valid_from'));

            if (strlen($params->get('valid_until'))) {
                $notifParams->set('valid_until', $params->get('valid_until'));
            } else {
                $notifParams->set('valid_until', $this->state->lang->get('unlimited'));
            }
            $notifParams->set('license_id', $params->get('licenseid'));

            $notifParams->set('login_url',
            $this->state->config->get('applicationURL') .
                'client/index.php?hash=' . $recipient['hash'] . '#downloads');

            $notifParams->set('customer_email', $recipient['email']);
            $notifParams->set('customer_name', $recipient['name']);

            if (!($ret = $this->callService('SendMail', 'send', $notifParams))) {
                return false;
            }
        }
    }

    function generateLicenseId($length = 5) {
        $password_string = "qwertyuiplkjhgfdsazxcvbnm987654321";
        return substr(str_shuffle($password_string), 0, $length);
    }


    function updateOrder($params) {
        $response =& $this->getByRef('response');
        $db =& $this->state->getByRef('db');
         
        if(!$params->check(array('orderid'))) {
            $response->set('error', $this->state->lang->get('noIdProvided'));
            return false;
        }

        $params->set('table', 'product_orders');
        $params->set('where', "orderid = '".$db->escapeString($params->get('orderid')) . "'");
        return $this->callService('SqlTable', 'update', $params);
    }
}
?>