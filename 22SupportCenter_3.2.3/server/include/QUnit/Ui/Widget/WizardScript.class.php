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

class QUnit_Ui_Widget_WizardScript extends QUnit_Ui_Widget {

    var $steps;
    var $currentStep;
    var $lastStep;

    function _init() {
        parent::_init();
    }

    function &addStep(&$step) {
        $this->steps[$step->get('name')] =& $step;
        $this->lastStep = $step->get('name');
        return $step;
    }

    function setCurrentStep($name) {
        $this->resetSteps();
        while($step =& $this->getStep()) {
            if($step->get('name') == $name) {
                $this->currentStep =& $step;
                return true;
            }
            $step->setDone(true);
        }
        return false;
    }

    function &getCurrentStep() {
        if(!is_object($this->currentStep)) {
            $this->currentStep =& reset($this->steps);
        }
        return $this->currentStep;
    }

    function &getNextStep() {
        $current =& $this->getCurrentStep();
        $this->resetSteps();
        while($step =& $this->getStep()) {
            if($step->get('name') == $current->get('name')) {
                $step->setDone(true);
                $nextStep =& $this->getStep();
                $this->currentStep =& $nextStep;
                return $nextStep;
            }
        }
        return false;
    }

    function getSteps() {
        return $this->steps;
    }

    function resetSteps() {
        reset($this->steps);
    }

    function &getStep() {
        $step = each($this->steps);
        return $this->steps[$step['key']];
    }

    function isLastStep() {
        $current =& $this->getCurrentStep();
        return $current->get('name') == $this->lastStep;
    }
}

?>