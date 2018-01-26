<?php
/**
*   Base database driver class
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package QUnit
*   @since Version 0.1
*   $Id: DbConnectionMysql.class.php,v 1.1 2005/05/14 11:46:22 jsujan Exp $
*/

QUnit::includeClass('QUnit_Object');
class QUnit_Db_BaseDriver extends QUnit_Object {

    /**
    * Constructor
    *
    * @access public
    * @param array params (Host, User, Password, Database)
    */
    function _init($params) {
        $this->host = $params['Host'];
        $this->user = $params['User'];
        $this->password = $params['Password'];
        $this->database = $params['Database'];
    }

    /**
    * Connects to database
    *
    * @access public
    * @return object
    */
    function connect() {
        $this->handle = null;
        return QUnit::newObj('QUnit_Db_BaseDatabase', $this->handle);
    }

}

?>