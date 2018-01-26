<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package Arb
*   @since Version 0.1
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

QUnit::includeClass('QUnit_Object');

class QUnit_Db_Column extends QUnit_Object {

    function _init($name, $type, $length, $needed = false) {
        $this->attrAccessor('name');
        $this->attrAccessor('type');
        $this->attrAccessor('length');
        $this->attrAccessor('needed');
        $this->attrAccessor('value');

        $this->set('name', $name);
        $this->set('type', $type);
        $this->set('length', $length);
        $this->set('needed', $needed);
        $this->set('value', null);
    }

    function _setType($value) {
        switch(strtolower($value)) {
            case 'varchar':
            case 'char':
            case 'text':
            case 'string':
                $this->type = 'String';
                break;
            case 'int':
            case 'integer':
            case 'number':
                $this->type = 'Number';
                break;
            case 'datetime':
            case 'date':
            case 'time':
                $this->type = 'Date';
                break;
        }
    }

    function check() {
        if($this->needed && $this->isEmpty()) {
            return QUnit_Object::getErrorObj($this->name." cannot be empty");
        }
        return true;
    }

    function isEmpty() {
        return $this->value === null;
    }

    function getQuotedValue() {
    	if (!isset($this->type)) $this->type = '';
    	
        switch($this->type) {
        	case 'Number':
        	    if (!strlen($this->value) && !$this->needed) {
        	        return 'null';
        	    }
                return addslashes($this->value);
                break;
            default:
                if (!is_array($this->value)) {
                	if (strtolower($this->value) == 'null') {
                		return 'null';
                	} else {
                		return "'".addslashes($this->value)."'";
                	}
                } else {
                	return "'" . addslashes(implode(' ', $this->value)) . "'";
                }
                break;
        }
    }

}

?>