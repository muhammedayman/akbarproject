<?php

QUnit::includeClass('QUnit_Container');

class QUnit_Rpc_Params extends QUnit_Container {

    function check($paramNames) {
        if(! is_array($paramNames)) {
        	$paramNames = array($paramNames);
        }
    	foreach($paramNames as $name) {
            if(!strlen($this->get($name))) {
                return false;
            }
        }
        return true;
    }

    function checkFields($paramNames) {
        $fields = $this->get('fields');
        
    	if(! is_array($paramNames)) {
        	$paramNames = array($paramNames);
        }
        
        foreach($paramNames as $name) {
            if(!strlen($fields->$name)) {
                return false;
            }
        }
        return true;
    }
    
    function get($paramName) {
    	$ret = QUnit_Container::get($paramName);
    	if (!is_array($ret) && !is_object($ret) && !strlen($ret)) {
    		$ret = $this->getField($paramName);
    	}
    	return $ret;
    }
    
    function getField($fieldName) {
    	$fields = QUnit_Container::get('fields');
    	if (is_object($fields) && isset($fields->$fieldName)) {
    		return $fields->$fieldName;
    	} else {
    		return false;
    	}
    }
    
    function setField($fieldName, $value) {
    	$fields = QUnit_Container::get('fields');
    	if (!is_object($fields)) $fields = new stdClass();
    	$fields->$fieldName = $value;
    	return $this->set('fields', $fields);
   	}
   	
   	function unsetField($fieldName) {
    	$fields = QUnit_Container::get('fields');
    	if (is_array($fieldName)) {
    		foreach($fieldName as $fldName) {
    			if (isset($fields->$fieldName)) unset($fields->$fieldName);
    		}
    	} else {
    		unset($fields->$fieldName);
    	}
    	return $this->set('fields', $fields);
   	}
   	
   	function getFieldNames() {
    	$fields = QUnit_Container::get('fields');
   		if (is_array($fields)) {
   			return array_keys($fields);
   		}
   		return array();
   	}
}

?>