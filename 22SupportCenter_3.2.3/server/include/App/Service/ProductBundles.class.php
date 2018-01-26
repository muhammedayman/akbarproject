<?php
/**
 *   Handler class for Product Bundles
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_ProductBundles extends QUnit_Rpc_Service {

    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
            default:
                return false;
                break;
        }
    }

    /**
     * Insert bundle
     *
     * @param QUnit_Rpc_Params $params
     */
    function insertBundle($params) {
        $response =& $this->getByRef('response');
        $db = $this->state->get('db');
         
        if(!$params->checkFields(array('productid', 'bundled_productid'))) {
            $response->set('error', $this->state->lang->get('missingMandatoryFields'));
            return false;
        }

        $params->set('table', 'product_bundles');
        return $this->callService('SqlTable', 'insert', $params);
    }
    
    function getBundlesList($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        $columns = "bundled_productid";
        $from = "product_bundles";
        $where = "productid='" . $db->escapeString($params->get('productid')) . "'";

        $params->set('columns', $columns);
        $params->set('from', $from);
        $params->set('where', $where);
        $params->set('table', $from);
        return $this->callService('SqlTable', 'select', $params);
    }
    
    
}
?>