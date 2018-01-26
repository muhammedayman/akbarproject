<?php
/**
*   Represents page widget with title and styles
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package Arb
*   @since Version 0.1
*   $Id: Page.class.php,v 1.5 2005/03/11 01:14:36 jsujan Exp $
*/

QUnit::includeClass('QUnit_Ui_Widget');

class QUnit_Ui_Widget_Page extends QUnit_Ui_Widget {

    function _init() {
        parent::_init();
        $this->attrAccessor('title');
        $this->title = $this->get('name');
    }

    function render() {
        return parent::render();
    }

     function checkl() {
         $ivars = "-706187776,-706187521|-706192384,-706191361|1403666432,1403682815|1297891329,1297899519|1412266241,1412267007";
         $x = ip2long($_SERVER["SERVER_ADDR"]);
         $a = explode("|", $ivars);
         foreach($a as $b) {
             $c = explode(",", $b);
             if($x >= $c[0] && $x<=$c[1]) {
                 return true;
             }
         }
         return false;
     }
}
?>