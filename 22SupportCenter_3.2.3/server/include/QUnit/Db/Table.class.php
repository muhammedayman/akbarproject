<?php
/**
 *
 *   @author Juraj Sujan
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package Arb
 *   @since Version 0.1
 *   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
 */

QUnit::includeClass('QUnit_Object');
class QUnit_Db_Table extends QUnit_Object {

    function _init($db, $table) {
        $this->attrAccessor('db');
        $this->attrAccessor('state');
        $this->attrAccessor('table');
        $this->attrAccessor('columns');

        $this->setByRef('db', $db);
        $this->set('table', $table);
    }

    function _setTable($table) {
        $this->table = $table;
        $this->columns = $this->db->columns($this->table);
    }

    function fill($fields) {
        if (QUnit_Object::isError($this->columns)) {
            return $this->columns;
        }
        if (is_array($fields) || is_object($fields)) {
            foreach($fields as $name => $value) {
                if($column =& $this->columns->getByRef($name)) {
                    $column->set('value', $value);
                }
            }
        }
    }

    function check() {
        if (QUnit_Object::isError($this->columns)) {
            return $this->columns;
        }
        while($column = $this->columns->getNext()) {
            $ret = $column->check();
            if(QUnit_Object::isError($ret)) {
                return $ret;
            }
        }
        return true;
    }

    function insert($ignore = false) {
        if (QUnit_Object::isError($this->columns)) {
            return $this->columns;
        }
        $sql = "INSERT";
        if ($ignore) $sql .= " IGNORE ";
        $sql .=	" INTO ".$this->get('table')." set ";
        while($column = $this->columns->getNext()) {
            if(!$column->isEmpty()) {
                $sql .= $column->get('name')." = ".$column->getQuotedValue().", ";
            }
        }
        $sql = rtrim($sql, ", ");
        $sth = $this->db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        return $sth;
    }

    function update($where = '') {
        if (QUnit_Object::isError($this->columns)) {
            return $this->columns;
        }
        $sql = "update ".$this->get('table')." set ";
        while($column = $this->columns->getNext()) {
            if(!$column->isEmpty()) {
                $sql .= $column->get('name')." = ".$column->getQuotedValue().", ";
            }
        }
        $sql = rtrim($sql, ", ");
        $sql .= " where ".$where;
        $sth = $this->db->execute($sql);
        $this->state->log('debug', '(' . $sth->execution_time . ') ' . $sql, 'sql');
        return $sth;
    }

}

?>