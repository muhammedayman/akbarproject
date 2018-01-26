<?php

QUnit::includeClass("QUnit_Object");

class QUnit_Rpc_Server extends QUnit_Object {

    var $is_export = false;

    function _init() {
        parent::_init();
        $this->attrAccessor('state');
        $this->requestDataHandler = null;
    }

    function createRequestDataHandler($type) {
        $handler = QUnit::newObj('QUnit_DataExchangeHandler_'.$type);
        $this->requestDataHandler =& $handler;
    }

    function &getRequestDataHandler() {
        if(!is_object($this->requestDataHandler)) {
            $this->requestDataHandler = QUnit::newObj('QUnit_DataExchangeHandler_Plain');
        }
        return $this->requestDataHandler;
    }

    function decodeRequest($requestData) {
        $dataHandler =& $this->getRequestDataHandler();
        return $dataHandler->decode($requestData);
    }

    function encodeResponse($responseData) {
        if ($this->is_export) {
            $dataHandler = QUnit::newObj('QUnit_DataExchangeHandler_Csv');
        } else {
            $dataHandler =& $this->getRequestDataHandler();
        }
        return $dataHandler->encode($responseData);
    }

    function getRequstString($data) {
        if(get_magic_quotes_gpc()) {
            return stripslashes($data);
        }
        return $data;
    }

    function execute($requestString) {
        global $arr_start;
        if (empty($arr_start)) $arr_start = @gettimeofday();
        $request = QUnit::newObj('QUnit_Rpc_Request', $this->decodeRequest($this->getRequstString($requestString)));
        $push = $request->get('push');

        $response = QUnit::newObj('QUnit_Rpc_Response');
        $response->set('id', $request->get('id'));

        $server = QUnit::newObj('App_Server');
        $server->setByRef('state', $this->state);
        $server->setByRef('response', $response);
        $params = $request->get('params');
        $params = QUnit::newObj('QUnit_Rpc_Params', $params[0]);
        if ($params->get('service_is_export') == 'y') {
            $this->is_export = true;
        }
        $db = $this->state->getByRef('db');
        if (!strlen(trim($requestString))) {
            $response->set('error', "Received empty request.");
        } else {
            if ($db->handle) {
                $server->execute($_GET['service'], $request->get('method'), $params);
            } else {
                $response->set('error', "Database connection failed.");
            }
        }

        if (!empty($push) && is_array($push)) {
            $pushResults = array();
            foreach ($push as $pushRequest) {
                $pushResponse = QUnit::newObj('QUnit_Rpc_Response');
                $server = QUnit::newObj('App_Server');
                $server->setByRef('state', $this->state);
                $server->setByRef('response', $pushResponse);
                $params = $pushRequest->params;
                $params = QUnit::newObj('QUnit_Rpc_Params', $params);

                $server->execute($pushRequest->service, $pushRequest->method, $params);
                	
                $pushResults[$pushRequest->id] = $pushResponse->getByRef('result');
            }
            if (!empty($pushResults)) {
                $response->set('push', $pushResults);
            }
        }

        $arr_end = @gettimeofday();

        $delay_sec = $arr_end['sec'] - $arr_start['sec'];
        $delay_usec = $arr_end['usec'] - $arr_start['usec'];
        if ($delay_usec < 0) {
            $delay_sec--;
            $delay_usec = 1000000 + $delay_usec;
        }
        $delay_usec = str_repeat('0', 6 - strlen($delay_usec)) . $delay_usec;
        $response->set('execTime', $delay_sec + $delay_usec/1000000);

        return $this->encodeResponse($response->getResponseVars());
    }

}
?>