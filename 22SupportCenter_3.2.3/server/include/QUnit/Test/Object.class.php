<?php
/**
*
*   @author Andrej Harsani
*   @copyright Copyright © 2004
*   @since Version 0.1a
*   $Id: Core.class.php 1370 2004-11-23 16:25:56Z jsujan $
*/

class QUnit_Test_Object extends PHPUnit_TestCase {

    function setUp() {
        $this->obj = QUnit::newObj('QUnit_Object');
    }

    function testAttrAccessor() {
        $this->obj->attrAccessor('testAttr');
        $this->obj->set('testAttr', 'test');

        $this->assertEquals($this->obj->get('testAttr'), 'test');
    }

    function testGetClass() {
        $this->assertEquals('QUnit_Object', $this->obj->get('class'));
    }

}

?>