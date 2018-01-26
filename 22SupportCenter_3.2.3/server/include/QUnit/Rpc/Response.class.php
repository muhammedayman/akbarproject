<?php

QUnit::includeClass('QUnit_Object');

class QUnit_Rpc_Response extends QUnit_Object {

    function _init() {
        parent::_init();
        $this->attrAccessor('result');
        $this->attrAccessor('id');
        $this->attrAccessor('error');
        $this->attrAccessor('execTime');
        $this->attrAccessor('push');
        
        $this->error = null;
        $this->result = null;
    }

    function setResultVar($key, $value) {
        if(!is_array($this->result)) {
            $this->result = array();
        }
        $this->result[$key] = $value;
    }

    function getResultVar($key) {
        return $this->result[$key];
    }

    function isError() {
        return $this->error != null;
    }

    function getResponseVars() {
        return array(
                'result' => $this->result,
                'error' => $this->error,
                'id' => $this->id,
                'push' => $this->push,
                'execTime' => $this->execTime
                );
    }

    function serialize() {
        return serialize($this->getResponseVars());
    }

    function _setError($msg) {
        $this->error = $msg;
        $this->result = null;
    }
}
?>