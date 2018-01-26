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

class QUnit_Ui_Widget_Wizard extends QUnit_Ui_Widget {

    function _init() {
        parent::_init();

        $this->attrAccessor('scriptObj');
        $this->attrAccessor('progBar');
        $this->attrAccessor('step');
        $this->attrAccessor('script');
        $this->scriptObj =& $this->createWidget('WizardScript');
    }

    function &addStep(&$step) {
		$step->set('script', $this->get('script'));    	
        $scriptObj =& $this->getByRef('scriptObj');
        $scriptObj->addStep($step);
        return $step;
    }

    function render() {
        $this->progBar =& $this->createWidget('ProgressBar');
        //$request =& $this->getRequest();
        $this->progBar->setModel($this->scriptObj);

        if(isset($_REQUEST['step'])) {
            $step = $_REQUEST['step'];
            $this->scriptObj->setCurrentStep($step);
            $this->step =& $this->scriptObj->getCurrentStep();
            if(!$this->scriptObj->isLastStep() && $this->step->process()) {
            	
            	if (count($this->step->update_messages) > 0) {
            		//print messages
            	} elseif ($nextStep =& $this->scriptObj->getNextStep()) {
                    $this->redirect("?mdl=".$this->get('target')."&step=".$nextStep->get('name')."&script=".$this->get('script'));
                }
            }
        } else {        	
            $this->step =& $this->scriptObj->getCurrentStep();
        }
        
        $this->step->set('target', $this->get('target'));
        return parent::render();
    }
}

?>