<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package Arb
*   @since Version 0.1
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

class QUnit_Rpc_ResultSet {

    function QUnit_Rpc_ResultSet() {
        $this->rows = array();
    }

    function setRows($rows) {
        $this->rows = $rows;
    }

    function addRow($array) {
        $this->rows[] = $array;
    }

    function getRows($md = null) {
    	
    	$rows = empty($this->rows) ? array() : $this->rows; 
    	
    	if ($md !== null) {
    		foreach ($rows as $rowid => $row) {
    			foreach($row as $colid => $val) {
    				$rows[$rowid][$md->colNames[$colid]] = $val;
    			}
    		}
    	}
    	
    	return $rows;
    }
}

?>