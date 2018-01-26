<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package Arb
*   @since Version 0.1
*   $Id: MainPage.class.php,v 1.10 2005/03/16 23:27:23 jsujan Exp $
*/

QUnit::includeClass('QUnit_Ui_Widget_Page');

class QUnit_Ui_Widget_MainPage extends QUnit_Ui_Widget_Page {

    function _init() {
        parent::_init();
        $this->attrAccessor('page');
    }

    function _getPageName() {
        if(!isset($_REQUEST['mdl'])) {
            return false;
        }
        return $this->_getPageClassName($_REQUEST['mdl']);
    }

    function _checkPage($pageName) {
        return QUnit_Global::existsClass($pageName);
    }

    function &_getPage() {
        if(!($pageName = $this->_getPageName()) || !$this->_checkPage($pageName)) {
            return $this->_getDefaultPage();
        }
        return QUnit_Global::newobj($pageName);
    }

    function _getPageClassName($pageName) {
        return 'QUnit_Ui_Widget_' . $pageName;
    }

    function &_getDefaultPage() {
        return QUnit_Global::newobj('QUnit_Ui_Widget_Page');
    }

    function render() {
        return parent::render();
    }

}
?>