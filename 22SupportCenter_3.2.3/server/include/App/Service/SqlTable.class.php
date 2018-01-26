<?php
/**
*   Handler class for login
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SeHistory
*/

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_SqlTable extends QUnit_Rpc_Service {

    function initMethod($params) {
        return false;
    }

    /**
     *  select
     *
     *  @param string columns comma separated list of columns (sql syntax)
     *  @param string from comma separated list of tables (sql syntax)
     *  @param string where where condition (sql syntax, optional)
     *  @param string table (logical) name of table
     *  @param int offset (optional, default 0)
     *  @param int limit (optional, default 10)
     *  @param string order name of column (optional)
     *  @param string orderDirection could be 'asc' or 'desc' (optional, default 'asc')
     *  @return object returns resultset
     */
    function select($params) {
        $response =& $this->getByRef('response');
        $db =& $this->state->getByRef('db');
		
        if (QUnit_Object::isError($db)) {
            $response->set('result', null);
            $response->set('error', $this->state->lang->get('dbError').$db->get('errorMessage'));
            return false;
        }
        
        if(!$params->check(array('columns', 'from', 'table')) && !$params->check('full_sql')) {
            $response->set('error', $this->state->lang->get('missingMandatoryAttributes'));
            return false;
        }

        if ($params->get('full_sql')) {
        	$sql = $params->get('full_sql');
        } else {
        
	        $sql = "select ";
	        if($params->get('distinct')) {
	            $sql = "select distinct ";
	        }
	
	        $sql .= $params->get('columns')." from ".$params->get('from');
	        if($where = $params->get('where')) {
	        	$sql .= " where $where";
	        }
	
	        if(strlen($params->get('group'))) {
	            $sql .= " group by ".$params->get('group');
	        }
        }
                
        if(strlen($params->get('order'))) {
                $sql .= " order by ".$params->get('order');
                if(strlen($params->get('orderDirection'))) {
                    $sql .= " ".$params->get('orderDirection');
                }
        }

        if(!strlen($params->get('offset'))) {
            $params->set('offset', '0');
        }
        if(!strlen($params->get('limit'))) {
            $params->set('limit', '1000');
        }
        $sql .= " limit ".$params->get('offset').", ".$params->get('limit');
        $sth = $db->execute($sql);
        $this->state->log((strlen($sth->execution_time) ? 'debug' : 'error'), '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(QUnit_Object::isError($sth)) {
            $response->set('result', null);
            $response->set('error', $this->state->lang->get('dbError').$sth->get('errorMessage'));
            return false;
        }
        $rs = QUnit::newObj('QUnit_Rpc_ResultSet');
        $rs->setRows($sth->fetchAllRows());
        $md = QUnit::newObj('QUnit_Rpc_MetaData', $params->get('table'));
        $md->setColumnNames($sth->getNames());
        $md->setColumnTypes($sth->getTypes());
        $response->setResultVar('md', $md);
        $response->setResultVar('rs', $rs);
        if ($params->get('count') != 'no') {
        	$this->count($params);
        } else {
        	$response->setResultVar('count', count($rs->getRows()));
        }
        return true;
    }

    /**
     *  count
     *
     *  @param string from comma separated list of tables (sql syntax)
     *  @param string where where condition (sql syntax, optional)
     *  @param string table (logical) name of table
     */
    function count($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        
        if (QUnit_Object::isError($db)) {
            $response->set('error', $this->state->lang->get('dbError').$db->get('errorMessage'));
            return false;
        }
        
        if(!$params->check(array('table'))) {
            $response->set('error', $this->state->lang->get('missingMandatoryAttributes'));
            return false;
        }

        $sql = "SELECT ";
        
        if($params->get('count_columns')) {
            $count_columns = $params->get('count_columns');
        } else {
        	$count_columns = "*";
        }

        if($params->get('distinct')) {
            $count_columns = "DISTINCT " . $count_columns;
        }
        
        $sql .= "COUNT($count_columns) from ".$params->get('from');
        if($where = $params->get('where')) {
            $sql .= " where $where";
        }
        if(strlen($params->get('group')) && $params->get('count_group_by') != 'no') {
            $sql .= " group by ".$params->get('group');
        }
        $sth = $db->execute($sql);
        $this->state->log((strlen($sth->execution_time) ? 'debug' : 'error'), '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(QUnit_Object::isError($sth)) {
            $response->set('error', $this->state->lang->get('dbError').$sth->get('errorMessage'));
            return false;
        }
        $row = $sth->fetchRow(); 
        $count = $row[0];
        if($count == null) $count = 0;
        $response->setResultVar('count', $count);
        return true;
    }

    /**
     *  getMetaData
     *
     *  @param string table (logical) name of table
     */
    function getMetaData($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        if (QUnit_Object::isError($db)) {
            $response->set('result', null);
            $response->set('error', $this->state->lang->get('dbError').$db->get('errorMessage'));
            return false;
        }
        
        
        $md = QUnit::newObj('QUnit_Db_MetaData');
        $columns = $db->columns($params->get('table'));
        $md->colNames = $columns->getNames();
        $md->colTypes = $columns->getTypes();
        $md->tableName = $params->get('table');
        $response->setResultVar('md', $md);
        return true;

    }

    /**
     *  insert
     *
     *  @param string table
     *  @param array fields associative array of fields
     *  @return string id of inserted row
     */
    function insert($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        if (QUnit_Object::isError($db)) {
            $response->set('result', null);
            $response->set('error', $this->state->lang->get('dbError').$db->get('errorMessage'));
            return false;
        }
        
        $table = QUnit::newObj('QUnit_Db_Table', $db, $params->get('table'));
        $table->setByRef('state', $this->state);
        $fields = $params->get('fields');
        $table->fill($fields);

        $ret = $table->check();
        if(QUnit_Object::isError($ret)) {
            $response->set('error', $this->state->lang->get('checkFailed').$ret->get('errorMessage'));
            return false;
        }

        $sth = $table->insert($params->get('ignore'));
        if(QUnit_Object::isError($sth)) {
        	$response->set('error', $this->state->lang->get('insertFailed').$sth->get('errorMessage'));
            return false;
        }
        if ($sth->insertedId) {
	        $response->set('result', $sth->insertedId);
        } else {
	        $response->set('result', true);
        }
        return true;
    }

    /**
     *  update
     *
     *  @param string table
     *  @param string where
     *  @param array fields associative array of fields
     *  @return boolean
     */
    function update($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');
        
        if (QUnit_Object::isError($db)) {
            $response->set('result', null);
            $response->set('error', $this->state->lang->get('dbError').$db->get('errorMessage'));
            return false;
        }
        
        $table = QUnit::newObj('QUnit_Db_Table', $db, $params->get('table'));
        $table->setByRef('state', $this->state);

        $fields = $params->get('fields');
        $table->fill($fields);
        
        $sth = $table->update($params->get('where'));
        if(QUnit_Object::isError($sth)) {
            $response->set('error', $this->state->lang->get('updateFailed').$sth->get('errorMessage'));
            return false;
        }
        $response->set('result', "true");
        return true;
    }

    /**
     *  delete
     *
     *  @param string table
     *  @param string where (optional)
     *  @return boolean
     */
    function delete($params) {
        $db =& $this->state->getByRef('db');
        $response =& $this->getByRef('response');

        if (QUnit_Object::isError($db)) {
            $response->set('result', null);
            $response->set('error', $this->state->lang->get('dbError').$db->get('errorMessage'));
            return false;
        }
        
        $sql = "delete from ".$params->get('table')." where ".$params->get('where');

        $sth = $db->execute($sql);
        $this->state->log((strlen($sth->execution_time) ? 'debug' : 'error'), '(' . $sth->execution_time . ') ' . $sql, 'sql');
        if(QUnit_Object::isError($sth)) {
            $response->set('error', $this->state->lang->get('dbError').$sth->get('errorMessage'));
            return false;
        }
        $response->set('result', "true");
        return true;
    }

}
?>