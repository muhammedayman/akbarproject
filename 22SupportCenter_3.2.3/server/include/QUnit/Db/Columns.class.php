<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package Arb
*   @since Version 0.1
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

QUnit::includeClass('QUnit_Container');

class QUnit_Db_Columns extends QUnit_Container {

    function addColumn($name, $type, $length, $needed = false) {
        $column = QUnit::newObj('QUnit_Db_Column', $name, $type, $length, $needed);
        $this->set($name, $column);
    }

    function getNames() {
        $arr = array();
        while($column = $this->getNext()) {
            $arr[] = $column->get('name');
        }
        return $arr;
    }

    function getTypes() {
        $arr = array();
        while($column = $this->getNext()) {
            $arr[] = $column->get('type');
        }
        return $arr;
    }

}

?>