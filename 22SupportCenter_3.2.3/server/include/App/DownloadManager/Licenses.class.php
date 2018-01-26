<?php
/**
 *   Handler class for License methods
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("QUnit_Rpc_Service");
class App_DownloadManager_Licenses extends QUnit_Rpc_Service {


    function createLicense($domain, $licenseId) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        $session = QUnit::newObj('QUnit_Session');

        $poParams = $this->createParamsObject();
        $poParams->set('licenseid', $licenseId);
        if ($this->callService('ProductOrders', 'getProductOrder', $poParams)) {
            $res = & $response->getByRef('result');
            if ($res['count'] > 0) {
                $order = $res['rs']->getRows($res['md']);
                $order = $order[0];
                
                QUnit::includeClass("QUnit_Gate");
                $license = QUnit_Gate::encodeLicense(date('Y-m-d H:i:s', strtotime("-1 day")), '', $domain, $licenseId);

                $lParams = $this->createParamsObject();
                $lParams->setField('license', $license);
                $lParams->setField('orderid', $order['orderid']);
                $this->callService('ProductInstallations', 'insertInstallation', $lParams);
                return $license;
            }
        }

        return false;
    }

    function requestLicense($domain, $licenseId) {
        if (!strlen($domain) || !strlen($licenseId)) {
            return false;
        }
        return $this->createLicense($domain, $licenseId);
    }

    function execute() {
        if ($license = $this->requestLicense($_REQUEST['d'], $_REQUEST['c'])) {
            echo $license;
            return true;
        }
        echo 'FAILED';
        return false;
    }
}
?>