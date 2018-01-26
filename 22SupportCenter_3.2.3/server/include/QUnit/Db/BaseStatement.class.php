<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package QUnit
*   @since Version 0.1
*   $Id: RecordSetMysql.class.php,v 1.1 2005/05/14 11:46:22 jsujan Exp $
*/

QUnit::includeClass('QUnit_Object');
class QUnit_Db_BaseStatement extends QUnit_Object {

    var $handle;
    var $statement;
    var $result;
    var $execution_time;

    function _init(&$stmt, &$handle) {
        $this->statement = $stmt;
        $this->handle = $handle;
    }

    function execute() {
        return true;
    }

    function fetchArray() {
        return $array;
    }

    function fetchRow() {
        return array();
    }

    function fetchRows() {
        return $array;
    }

    function fetchAllRows() {
        return $array;
    }
    
    function fetchAllRowsAsoc() {
        return $array;
    }

    function rowCount() {
        return 0;
    }

    function move($rowNumber) {
        return true;
    }

    function isError() {
        return $this->result === false;
    }

    function getErrorMessage() {
        return "";
    }

    function getNames() {
        return array();
    }

    function getTypes() {
        return array();
    }
}
?>