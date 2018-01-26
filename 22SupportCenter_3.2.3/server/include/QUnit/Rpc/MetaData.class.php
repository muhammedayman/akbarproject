<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package Arb
*   @since Version 0.1
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

class QUnit_Rpc_MetaData {

    function QUnit_Rpc_MetaData($tableName) {
        $this->colNames = array();
        $this->colTypes = array();
        $this->tableName = $tableName;
    }

    function addColumn($name, $type) {
        $this->colNames[] = $name;
        $this->colTypes[] = $type;
    }

    function setColumnNames($colNames) {
        if(is_array($colNames)) {
            $this->colNames = $colNames;
        }
    }

    function setColumnTypes($colTypes) {
        if(is_array($colTypes)) {
            $this->colTypes = $colTypes;
        }
    }
}
?>