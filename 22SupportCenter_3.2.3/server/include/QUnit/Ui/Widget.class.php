<?php
/**
*   Base class for all widgets
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package QUnit
*   @since Version 0.1
*   $Id: Widget.class.php,v 1.6 2005/03/16 23:27:23 jsujan Exp $
*/

QUnit::includeClass('QUnit_Object');

class QUnit_Ui_Widget extends QUnit_Object {

    /**
    *
    * Constructor.
    *
    * @access public
    *
    * @param string $template name of the template
    *
    */
    function _init() {
        parent::_init();
        $this->attrAccessor('state');
        $this->attrAccessor('template');
        $this->attrAccessor('templatePath');
        $this->attrAccessor('templateSuffix');
        $this->attrAccessor('name');
        $this->attrAccessor('target');

        $this->templatePath = realpath(dirname(__FILE__)).'/templates';
        $this->templateSuffix = '.tpl.php';
        $class = explode('_', $this->get('class'));
        $this->name = ucfirst(array_pop($class));
        $this->target = $this->name;
        $this->template = $this->name;
    }

    function &createWidget($className) {
        if(QUnit::existsClass('QUnit_Ui_Widget_'.$className)) {
            $obj = QUnit::newObj('QUnit_Ui_Widget_'.$className);
            $obj->setByRef('state', $this->getByRef('state'));
            $obj->set('target', $this->get('target'));
            return $obj;
        }
        return false;
    }

    /**
    * Renders whole widget and returns the html code string
    *
    * @access public
    *
    * @return string
    *
    */
    function render() {
    	ob_start();
        $template = $this->templatePath.'/'.$this->template.$this->templateSuffix;
        if(!file_exists($template) || !is_readable($template)) {
        	ob_end_flush();
            return "";
        }
        include($template);
        return ob_get_clean();
    }

    function redirect($request, $time = 0) {
        header("Location: " . $request);
    }

}
?>