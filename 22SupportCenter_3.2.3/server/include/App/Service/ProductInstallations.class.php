<?php
/**
 *   Handler class for Product Installations
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_ProductInstallations extends QUnit_Rpc_Service {

    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
            default:
                return false;
                break;
        }
    }
     
    /**
     * @param QUnit_Rpc_Params $params
     */
    function insertInstallation($params) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
         
        if (!strlen($params->getField('installation_id'))) {
            $params->setField('installation_id', md5(uniqid(rand(), true)));
        }

        if (!strlen($params->getField('created'))) {
            $params->setField('created', $db->getDateString());
        }

        if (!strlen($params->getField('ip'))) {
            $params->setField('ip', $_SERVER['REMOTE_ADDR']);
        }

        $params->set('table', 'product_installations');
        return $this->callService('SqlTable', 'insert', $params);
    }
}
?>