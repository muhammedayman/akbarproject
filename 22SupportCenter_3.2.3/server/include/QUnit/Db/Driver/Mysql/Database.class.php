<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package QUnit
*   @since Version 0.1
*   $Id: DbConnectionMysql.class.php,v 1.1 2005/05/14 11:46:22 jsujan Exp $
*/

if (!function_exists('mysql_connect')) {
    die("Missing MySql support in your php ! Solution: Load mysql extension in your php.ini or recompile php with mysql support.");
}

QUnit::includeClass('QUnit_Db_BaseDatabase');
class QUnit_Db_Driver_Mysql_Database extends QUnit_Db_BaseDatabase {
	
    function prepare($stmt) {
    	return QUnit::newObj('QUnit_Db_Driver_Mysql_Statement', $stmt, $this->handle);
    }

    function execute($stmt) {
    	$arr_start = @gettimeofday();
    	$sth = $this->prepare($stmt);
    	if($sth->execute()) {
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
        return QUnit_Object::getErrorObj($sth->getErrorMessage());
    }

    function columns($table) {
        $cols = QUnit::newObj('QUnit_Db_Columns');

        $sth = $this->execute("SHOW COLUMNS FROM `$table`");
        if(QUnit_Object::isError($sth)) {
            return $sth;
        }
        while ($row = $sth->fetchArray()) {
            preg_match('/([a-zA-Z]+)(\(([0-9]+)\))?/', $row['Type'], $matches);
            if (!isset($matches[3])) $matches[3] = null;
            $cols->addColumn($row['Field'], $matches[1], $matches[3], (strtolower($row['Null']) == 'no' && !($matches[1] == 'datetime' && $row['Default'] == '0000-00-00 00:00:00')) ? true : false);
        }
        return $cols;
    }

    function disconnect() {
        $this->connected = false;
    }

    function escapeString($str) {
        return mysql_real_escape_string($str);
    }
    
    function getVersion() {
    	return mysql_get_server_info($this->handle);
    }

}

?>