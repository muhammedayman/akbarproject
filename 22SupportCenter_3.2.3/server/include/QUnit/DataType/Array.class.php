<?php
/**
*   Array object
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package
*   @since Version 0.1
*/

class QUnit_DataType_Array {
	var $array;

	function __construct($array = '') {
        if(!is_array($array)) {
            $array = array();
        }
        $this->setArray($array);
	}

    function __get($key) {
        $value = $this->array[$key];
        if(is_array($value)) {
            $value = new QUnit_DataType_Array($value);
        }
        return $value;
    }

    function __set($key, $value) {
        $this->array[$key] = $value;
    }

    function setArray($array) {
        if(is_array($array)) {
            $this->array = $array;
        }
    }

    function getArrayCopy() {
        return $this->array;
    }

    function count() {
        return count($this->array);
    }
}
?>