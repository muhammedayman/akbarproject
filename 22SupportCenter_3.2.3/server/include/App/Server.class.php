<?php

QUnit::includeClass("QUnit_Object");

class App_Server extends QUnit_Object {

    function _init() {
        parent::_init();
        $this->attrAccessor('state');
        $this->attrAccessor('response');
    }

	function execute($service, $method, $params) {
        if(!$this->initMethod($service, $method, $params)) {
            return false;
        }
        $oService = QUnit::newObj('QUnit_Rpc_Service');
        $oService->setByRef('state', $this->state);
        $oService->setByRef('response', $this->response);
        $oService->callService($service, $method, $params);
	}

    function initMethod($service, $method, $params) {
        $oService = QUnit::newObj('QUnit_Rpc_Service');
        $oService->setByRef('state', $this->state);
        $oService->setByRef('response', $this->response);

        $params->set('method', $method);
        return $oService->callService($service, 'initMethod', $params);
    }

}
?>