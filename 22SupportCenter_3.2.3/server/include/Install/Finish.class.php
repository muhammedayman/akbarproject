<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package AddressCorrector_Core
*   @since Version 0.1
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

QUnit::includeClass('QUnit_Ui_Widget_WizardStep');

class Install_Finish extends QUnit_Ui_Widget_WizardStep {

    function _init() {
        parent::_init();
    }

    function render() {
        $request = $this->state->getByRef('request');
        if($request->get('submit')) {
            $this->redirect('../../');
        }
        return parent::render();
    }


}

?>