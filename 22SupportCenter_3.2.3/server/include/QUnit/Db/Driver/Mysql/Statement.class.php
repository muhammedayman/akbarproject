<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package QUnit
*   @since Version 0.1
*   $Id: RecordSetMysql.class.php,v 1.1 2005/05/14 11:46:22 jsujan Exp $
*/

QUnit::includeClass('QUnit_Db_BaseStatement');
class QUnit_Db_Driver_Mysql_Statement extends QUnit_Db_BaseStatement {

    function execute() {
        $this->result = mysql_query($this->statement, $this->handle);
        
        if (preg_match('/^insert/i', $this->statement)) {
        	$this->insertedId = $this->getInsertedId();
        }
        
        return $this->result;
    }
    
    function getInsertedId() {
    	return mysql_insert_id($this->handle);
    }

    function getNames() {
        $numFields = mysql_num_fields($this->result);
        $names = array();
        for($i=0; $i<$numFields; $i++) {
            $names[] = mysql_field_name($this->result, $i);
        }
        return $names;
    }

    function getTypes() {
        $numFields = mysql_num_fields($this->result);
        $types = array();
        for($i=0; $i<$numFields; $i++) {
            $types[] = $this->translateType(mysql_field_type($this->result, $i));
        }
        return $types;
    }

    function translateType($type) {
        switch(strtolower($type)) {
            case 'int':
            case 'integer':
            case 'number':
            case 'decimal':
                return 'number';
                break;
            case 'datetime':
            case 'date':
            case 'time':
                return 'date';
                break;
            default:
                return 'string';
                break;
        }
    }

    function fetchArray() {
        return mysql_fetch_assoc($this->result);
    }

    function fetchRow() {
        return mysql_fetch_row($this->result);
    }

    function fetchAllRows() {
        $rows = array();
        while($row = $this->fetchRow()) {
                $rows[] = $row;
        }
        return $rows;
    }

    function fetchAllRowsAsoc() {
        $rows = array();
        while($row = $this->fetchArray()) {
                $rows[] = $row;
        }
        return $rows;
    }
    
    function rowCount() {
        return mysql_num_rows($this->result);
    }

    function affectedRows() {
        return mysql_affected_rows($this->handle);
    }

    function move($rowNumber) {
        return mysql_data_seek($this->result, $rowNumber);
    }

    function getErrorNumber() {
        return mysql_errno($this->handle);
    }
    
    function getErrorMessage() {
        switch(mysql_errno($this->handle)) {
            case 1062:
                return 'Duplicate record';
                break;

        }
        return mysql_error($this->handle);
    }

}
?>