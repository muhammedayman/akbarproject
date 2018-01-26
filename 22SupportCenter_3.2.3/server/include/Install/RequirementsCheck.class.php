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

class Install_RequirementsCheck extends QUnit_Ui_Widget_WizardStep {

	function _init() {
		parent::_init();
		$this->set('template', 'RequirementsCheck');
		$this->set('title', 'Requirements Check');
		$this->attrAccessor('mbstringInstalled');
		$this->attrAccessor('iconvInstalled');
		$this->attrAccessor('phpVersion');
		$this->attrAccessor('phpVersionCheck');
		$this->attrAccessor('post_max_size');
		$this->attrAccessor('max_execution_time');
		$this->attrAccessor('max_input_time');
		$this->attrAccessor('memory_limit');
		$this->attrAccessor('upload_max_filesize');
		$this->attrAccessor('sessionWork');
		$this->attrAccessor('mysqlSupport');
	}

	function process() {
		if($this->processForm()) {
			return true;
		}
		return false;
	}

	function render() {
		$this->check();
		return parent::render();
	}

	function processForm() {
		$request = $this->state->getByRef('request');
		if($request->get('submit')) {
			if($this->check()) {
				return true;
			}
		}
		return false;
	}

	function check() {
		$correct = true;
		$session = QUnit::newObj('QUnit_Session');
		$this->set("sessionWork", true);
		if('OK' != $session->getVar('sessionTest')) {
			$this->set("sessionWork", false);
			$correct = false;
		}
		
		$this->set('mysqlSupport', function_exists('mysql_connect'));
		if (!function_exists('mysql_connect')) {
			$correct = false;
		}
		$this->set('post_max_size', ini_get('post_max_size'));
		$this->set('upload_max_filesize', ini_get('upload_max_filesize'));
		$this->set('max_execution_time', ini_get('max_execution_time'));
		$this->set('max_input_time', ini_get('max_input_time'));
		$this->set('memory_limit', ini_get('memory_limit'));

		if(!function_exists('mb_convert_encoding')) {
			$this->set("mbstringInstalled", false);
		} else {
			$this->set("mbstringInstalled", true);
		}
		$arr = get_loaded_extensions();
		if(array_search('iconv', $arr)===false) {
			$this->set("iconvInstalled", false);
		} else {
			$this->set("iconvInstalled", true);
		}

		if (!$this->get('mbstringInstalled') && !$this->get('iconvInstalled')) {
			$correct = false; 
		}
		
		
		$this->set('phpVersion', PHP_VERSION);
		if(version_compare(PHP_VERSION, "5.6.31") >= 0) {
			$this->set('phpVersionCheck', true);
		} else {
			$this->set('phpVersionCheck', false);
			$correct = false;
		}

		return $correct;
	}
}

?>
