<?php

QUnit::includeClass('QUnit_Object');

class QUnit_Rpc_Request extends QUnit_Object {

    function _init($data) {
        parent::_init();
        $this->attrAccessor('service');
        $this->attrAccessor('method');
        $this->attrAccessor('params');
        $this->attrAccessor('id');
        $this->attrAccessor('push');
        
        $this->method = $data->method;
        $this->params = $data->params;
        $this->id = $data->id;
        $this->push = $data->push;
    }

    function getParam($key) {
        if(is_array($this->params)) {
            return $this->params[$key];
        }
        if(is_object($this->params)) {
            return $this->params->$key;
        }
        return false;
    }

}

?>