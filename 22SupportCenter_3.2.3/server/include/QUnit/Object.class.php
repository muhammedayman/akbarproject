<?php
/**
*   Base object class
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @since Version 0.1
*   @package QUnit
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

QUnit::includeClass('QUnit_Error');

class QUnit_Object {

    /**
    * Php Constructor
    *
    * @access private
    */
    function QUnit_Object() {
        $this->attrReaders = array();
        $this->attrWriters = array();
        $this->observers = array();
        $args= func_get_args();
        call_user_func_array(array(&$this, '_init'), $args);
    }

    /**
    * Constructor
    *
    * @access public
    */
    function _init() {
        $this->attrReader('class');
    }

    /**
    * Get class name
    *
    * @access public
    */
    function _getClass() {
        return get_class($this);
    }

    /**
    * Register attribute with read access
    *
    * @access public
    * @param string key
    * @param bool byReference
    */
    function attrReader($key, $byReference = false) {
        $this->attrReaders[$key] = $byReference;
    }

    /**
     * Register attribute with write access
     *
     * @access public
    * @param string key
    * @param bool byReference
    */
    function attrWriter($key, $byReference = false) {
        $this->attrWriters[$key] = $byReference;
    }

    /**
    * Register attribute with full access
    *
    * @access public
    * @param string key
    * @param bool byReference
    */
    function attrAccessor($key, $byReference = false) {
        $this->attrReader($key, $byReference);
        $this->attrWriter($key, $byReference);
    }

    /**
    * Get attribute
    *
    * @access public
    * @param string key
    * @return mixed
    */
    function get($key) {
        if(isset($this->attrReaders[$key])) {
            if($this->methodExists('_get'.$key)) {
                $methodName = '_get'.ucfirst($key);
                return $this->$methodName();
            }

            if (isset($this->$key)) {
            	return $this->$key;
            } else {
            	return false;
            }
        }
        return false;
    }

    /**
    * Get attribute by reference (for Php 4.x compatibility)
    *
    * @access public
    * @param string key
    * @return mixed
    */
    function &getByRef($key) {
        if(isset($this->attrReaders[$key])) {
            if($this->methodExists('_get'.$key)) {
                $methodName = '_get'.$key;
                return $this->$methodName();
            }

            return $this->$key;
        }
        $ret = false;
        return $ret;
    }

    /**
    * Set attribute
    *
    * @access public
    * @param string key
    * @param mixed value
    */
    function set($key, $value) {
        if(isset($this->attrWriters[$key])) {
        	if($this->methodExists('_set'.$key)) {
                $methodName = '_set'.$key;
                return $this->$methodName($value);
        	}

        	$this->$key = $value;
        }
    }

    /**
     * Set attribute by reference (for Php 4.x compatibility)
    *
    * @access public
    * @param string key
    * @param mixed value
    */
    function setByRef($key, &$value) {
        if(isset($this->attrWriters[$key])) {
            if($this->methodExists('_set'.$key)) {
                $methodName = '_set'.$key;
                return $this->$methodName($value);
            }

            $this->$key = &$value;
        }
    }

    /**
    * Checks whether method exists in class
    *
    * @access public
    * @param string methodName
    * @return bool
    */
    function methodExists($methodName) {
        return method_exists($this, $methodName);
    }

    /**
    * Returns (creates) QUnit_Error object
    *
    * @access public
    * @param string message (optional)
    * @return object
    */
    function getErrorObj($message = '') {
        return QUnit::newObj('QUnit_Error', $message);
    }

    function throwError($message) {
        return $this->getErrorObj($message);
    }

    /**
    * Checks whether object is of QUnit_Error type
    *
    * @access public
    * @param object obj
    * @return bool
    */
    function isError(&$obj) {
        if(is_a($obj, 'QUnit_Error')) {
            return $obj->get('errorMessage');
        }
        return false;
    }

    /**
     * Load object Attributes from array
     */
    function loadObjectAttributes($params) {
    	foreach ($params as $attribute_name => $value) {
   			$this->$attribute_name = $value;
    	}
    }
    
    function isDemoMode($module = '', $entry = '') {
    	if ($this->state->config->get('demoMode')) {
    		if (strlen($module) && !empty($entry)) {
    			$demoEntries = explode(',', $this->state->config->get('demoEntries_' . $module));
    			if (!is_array($entry)) $entry = array($entry);
    			foreach ($entry as $val) {
	    			if (array_search($val, $demoEntries) !== false) {
	    				return true;
	    			}
    			}
    		} else {
    			return true;
    		}
    	}
    	return false;
    }
}

?>