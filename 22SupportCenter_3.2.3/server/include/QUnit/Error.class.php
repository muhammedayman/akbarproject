<?php
/**
*   Base object class
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @since Version 0.1
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

QUnit::includeClass('QUnit_Object');
class QUnit_Error extends QUnit_Object {

    /**
    *
    * Constructor
    *
    * @access public
    *
    */
    function _init($message = '') {
        $this->attrAccessor('errorMessage');
        $this->set('errorMessage', $message);
    }

}

?>