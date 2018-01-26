<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package QUnit
*   @since Version 0.1
*   $Id: DbConnectionMysql.class.php,v 1.1 2005/05/14 11:46:22 jsujan Exp $
*/

QUnit::includeClass('QUnit_Db_BaseDriver');
class QUnit_Db_Driver_Mysql_Driver extends QUnit_Db_BaseDriver {

    function connect() {
        $this->handle = @mysql_connect($this->host, $this->user, $this->password);
        if(!$this->handle) {
            return $this->throwError("Unable to connect to database: " . mysql_error());
        }
        if(!mysql_select_db($this->database, $this->handle)) {
            return $this->throwError("Unable to select database ".$this->database);
        }
        $driver = QUnit::newObj('QUnit_Db_Driver_Mysql_Database', $this->handle);
        $this->post_connect($driver);
        return $driver;
    }
    
    function post_connect($driver) {
    	$driver->execute("SET NAMES utf8");
    	$driver->execute("SET CHARACTER_SET utf8");
        $driver->execute("SET SQL_MODE = ''");
    }
}

?>
