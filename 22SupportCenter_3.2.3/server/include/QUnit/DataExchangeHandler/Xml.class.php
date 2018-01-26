<?php

// NOTICE: quick draft only; works only when request and response data exchange
// handlers are Xml both

QUnit::includeClass("QUnit_Interface_DataExchangeHandler");

class QUnit_DataExchangeHandler_Xml implements QUnit_Interface_DataExchangeHandler {
	
	public function encode($data) {
		return $data->asXML(); 
	}
	
	public function decode($data) {
		$arr = new SimpleXMLElement($data);
		return $arr;
	}
}