<?php
/**
*
*   Base Db Object
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package QUnit
*   @since Version 0.1
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

class QUnit_Db_Object {

    /**
    * Returns driver object for specific database
    *
    * @access public
    * @param array params (Driver, Host, User, Password, Database)
    * @return object
    */
    function getDriver($params) {
        $driverName = "QUnit_Db_Driver_{$params['Driver']}_Driver";
        if(!QUnit::existsClass($driverName)) {
            return QUnit::newObj('QUnit_Db_BaseDriver', $params);
        }
        return QUnit::newObj($driverName, $params);
    }

}

?>