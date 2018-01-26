<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package Arb
*   @since Version 0.1
*   $Id: Session.class.php,v 1.6 2005/03/16 23:27:23 jsujan Exp $
*/

class QUnit_Session {
	var $prefix;
	var $localPrefix;

	function start() {
		session_start();
    }

    function getName() {
        return $this->prefix;
    }

    function setName($name) {
        $this->prefix = $name;
    }

    function setId($id) {
        session_id($id);
    }

    function getId() {
        return session_id();
    }

    function regenerateId() {
        session_regenerate_id();
        return session_id();
    }

    function setPrefix($prefix) {
        $this->prefix = $prefix;
    }

    function setLocalPrefix($prefix) {
        $this->localPrefix = $prefix;
    }

    function setVar($var, $value) {
        $_SESSION[$this->prefix][$this->localPrefix][$var] = $value;
    }

    function getVar($var) {
        if($this->existsVar($var)) {
            return $_SESSION[$this->prefix][$this->localPrefix][$var];
        }
        return false;
    }

    function existsVar($var) {
    	return isset($_SESSION[$this->prefix]) && isset($_SESSION[$this->prefix][$this->localPrefix]) && isset($_SESSION[$this->prefix][$this->localPrefix][$var]);
    }

    function existsSession() {
    	return isset($_SESSION[$this->prefix]);
    }

    function destroy() {
    	$this->start();
        session_unset();
        //session_destroy();
	    if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()-42000, '/');
		}
    }

}
?>