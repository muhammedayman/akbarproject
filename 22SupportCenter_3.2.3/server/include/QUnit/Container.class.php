<?php
/**
*   Container class
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package QUnit
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

QUnit::includeClass('QUnit_Object');
class QUnit_Container extends QUnit_Object {
	var $current;
	
    function _init($data = array()) {
        $this->attrAccessor('data');
        $this->attrAccessor('current');
        $this->fill($data);
    }

    function fill($data) {
        if(!empty($data)) {
            foreach($data as $name => $value) {
                $this->data[$name] = $value;
            }
        }
    }

    function get($key) {
    	if (isset($this->data) && isset($this->data[$key])) {
        	return $this->data[$key];
    	} else {
    		return null;
    	}
    }

    function set($key, $value) {
        $this->data[$key] = $value;
        return true;
    }

    function unsetParam($key) {
    	unset($this->data[$key]);
    }
    
    function &getByRef($key) {
        return $this->data[$key];
    }

    function setByRef($key, &$value) {
        $this->data[$key] =& $value;
    }

    function getNext() {
        list($key, $value) = each($this->data);
        $this->current++;
        if(!$value) {
            reset($this->data);
        }
        return $value;
    }
}

?>