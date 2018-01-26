<?php
/**
 *   Handler class for Integration methods
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("QUnit_Rpc_Service");
class App_DownloadManager_Integrator extends QUnit_Rpc_Service {

    function loadProduct($productCode) {
        $response =& $this->getByRef('response');
        $prodObj = QUnit::newObj('App_Service_Products');
        $prodObj->state = $this->state;
        $prodObj->response = $response;
        $params = $this->createParamsObject();
        $params->set('product_code', $productCode);
        $product = $prodObj->loadProduct($params);
        return $product;
    }

    function loadUser($email, $name, $first_load = true) {
        $response =& $this->getByRef('response');
        $userObj = QUnit::newObj('App_Service_Users');
        $userObj->state = $this->state;
        $userObj->response = $response;
        $params = $this->createParamsObject();
        $params->set('email', $email);
        if ($user = $userObj->loadUser($params)) {
            return $user;
        } else {
            if ($first_load) {
                //try to insert user
                $params->setField('name', $name);
                $params->setField('email', $email);
                if ($userObj->insertUser($params)) {
                    return $this->loadUser($email, $name, false);
                }
            }
        }
        return false;
    }

    function assignProductToUser($product, $user, $request) {
        $response =& $this->getByRef('response');

        $params = $this->createParamsObject();
        $params->setField('user_id', $user['user_id']);
        $params->setField('productid', $product['productid']);
        $params->setField('order_number', $request['o_no']);
        $params->setField('price', $request['pp']);
        $params->setField('order_info', var_export($request, true));

        return $this->callService('ProductOrders', 'insertOrder', $params);
    }

    function execute() {
        //Overwrite this method
        return true;
    }
}
?>