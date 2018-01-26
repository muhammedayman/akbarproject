<?php
/**
*
*   @author Andrej Harsani
*   @copyright Copyright © 2004
*   @package wrapper
*   @since Version 0.1a
*   $Id: Core.class.php 1370 2004-11-23 16:25:56Z jsujan $
*/

class QUnit_Rpc_Test_Response extends PHPUnit_TestCase {

    function setUp() {
        $this->obj = QUnit::newObj('QUnit_Rpc_Response');
    }

    function testIsError() {
        $this->obj->set('error', 'test');

        $this->assertTrue($this->obj->isError());
    }
}

?>