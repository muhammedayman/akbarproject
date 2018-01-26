<?php

QUnit::includeClass("QUnit_Ui_Widget_Page");

class Install_Main extends QUnit_Ui_Widget_Page {

	function _init() {
		parent::_init();
		$this->attrAccessor('wizard');

		$this->set('template', 'Main');
		$this->set('title', 'Install');

		$this->wizard = $this->createWidget('Wizard');
	}

	function render() {
		switch($this->getInstallType()) {
			case 'upgrade':
				$this->initUpgrade();
				break;
			default:
				$this->initInstall();
				break;
		}
		return parent::render();
	}

	function getInstallType() {
		$session = QUnit::newObj('QUnit_Session');
		if(function_exists('session_start')) {
			$session->start();
		}
		$installType = $session->getVar('installType');
		if(!$installType || $installType == '') {
			if($this->isDatabaseWorkingAndInitialized()) {
				$session->setVar('installType', 'upgrade');
			} else {
				$session->setVar('installType', 'install');
			}
			$installType = $session->getVar('installType');
		}
		return $installType;
	}
	
	function isDatabaseWorkingAndInitialized() {
		$db =& $this->state->getByRef('db');
		if (!method_exists($db, 'execute')) {
			return false;
		}
		$sth = $db->execute("select * from settings where setting_key = 'dbLevel'");
		if(QUnit_Object::isError($sth)) {
			return false;
		}
		if($sth->rowCount() != 1) {
			return false;
		}
		return true;
	}

	function initInstall() {
		$this->wizard->addStep($this->getStepObj('Start'));
		$this->wizard->addStep($this->getStepObj('RequirementsCheck'));
		$this->wizard->addStep($this->getStepObj('DbConfig'));
		
		$cL = 0;
		if ($cL != 1 && !$this->checkl()) {
			$this->wizard->addStep($this->getStepObj('Authorization'));
		}
		$this->wizard->addStep($this->getStepObj('Config'));
		$this->wizard->addStep($this->getStepObj('InsertAdmin'));
		$this->wizard->addStep($this->getStepObj('Finish'));
	}

	function initUpgrade() {
		$this->wizard->addStep($this->getStepObj('Startupgrade'));
		$this->wizard->addStep($this->getStepObj('DbUpgrade'));
		$this->wizard->addStep($this->getStepObj('Finishupgrade'));
	}

	function &getStepObj($step) {
		$obj = QUnit::newObj('Install_'.$step);
		$obj->set('templatePath', $this->get('templatePath'));
		$state = $this->get('state');
		$obj->setByRef('state', $state);
		return $obj;
	}

}
?>