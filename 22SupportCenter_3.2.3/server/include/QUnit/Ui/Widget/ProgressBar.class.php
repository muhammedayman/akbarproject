<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package QUnit
*   @since Version 0.1
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

QUnit::includeClass('QUnit_Ui_Widget');

class QUnit_Ui_Widget_ProgressBar extends QUnit_Ui_Widget {

    var $model;

    function _init() {
        parent::_init();
        $this->set('template', 'ProgressBar');
    }

    function setModel(&$model) {
        $this->model =& $model;
    }

    function render() {
        return parent::render();
    }
}

?>