<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package QUnit
*   @since Version 0.1
*   $Id: DbConnectionMysql.class.php,v 1.1 2005/05/14 11:46:22 jsujan Exp $
*/

QUnit::includeClass('QUnit_Object');
class QUnit_Db_BaseDatabase extends QUnit_Object {

    var $handle;
    var $connected;

    function _init(&$handle) {
    	$this->handle = $handle;
    	$this->connected = true;
    }

    function prepare($stmt) {
    	return QUnit::newObj('QUnit_Db_BaseStatement', $stmt, $this->handle);
    }

    function execute($stmt) {
    	$arr_start = @gettimeofday();
    	 
    	$sth = QUnit::newObj('QUnit_Db_BaseStatement', $stmt, $this->handle);
    	$sth->execute();

    	$arr_end = @gettimeofday();

    	$delay_sec = $arr_end['sec'] - $arr_start['sec'];
    	$delay_usec = $arr_end['usec'] - $arr_start['usec'];
    	if ($delay_usec < 0) {
            $delay_sec--;
            $delay_usec = 1000000 + $delay_usec;
        }
        $delay_usec = str_repeat('0', 6 - strlen($delay_usec)) . $delay_usec;
        $sth->execution_time = $delay_sec + $delay_usec/1000000; 
        return $sth;
    }

    function columns($table) {
        return array();
    }

    function disconnect() {
        $this->connected = false;
    }

    function createUniqueId($length = 10) {
        $uniqueId = substr(md5(uniqid(rand(), true)), 0, $length);
        return $uniqueId;
    }

    function getDateString($time = '') {
        if($time === '') $time = time();
        return strftime("%Y-%m-%d %H:%M:%S", $time);
    }
    
    function getTimeFromDbDateTime($strDateTime) {
        return strptime($strDateTime, "%Y-%m-%d %H:%M:%S");
    }

    function escapeString($str) {
        return addslashes($str);
    }
    
    function getVersion() {
    	return "0";    	
    }

}

?>