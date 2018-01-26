<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package QUnit
*   @since Version 0.1
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

QUnit::includeClass('QUnit_Ui_Widget_Page');

class QUnit_Ui_Widget_WizardStep extends QUnit_Ui_Widget_Page {

    var $done = false;

    function _init() {
        parent::_init();
        $this->attrAccessor('errorMessage');
        $this->attrAccessor('script');
        $this->errorMessage = '';
    }

    function setDone($bool) {
        $this->done = $bool;
    }

    function isDone() {
        return $this->done;
    }

    function process() {
        return false;
    }

    function render() {
        return parent::render();
    }

}

?>