<?php
/**
 *
 *   @author Juraj Sujan
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package Newsletter
 *   @since Version 0.1
 *   $Id: Config.class.php,v 1.5 2005/03/14 13:57:24 jsujan Exp $
 */

QUnit::includeClass('QUnit_Container');
class QUnit_Config extends QUnit_Container {

	function _init($configFile) {
		$this->setConfigFile($configFile);
	}

	function setConfigFile($configFile) {
		if(is_readable($configFile)) {
			include $configFile;
			$this->fill($config);
			return true;
		}
		return false;
	}


	function loadDbSettings($db) {
		if (!$db || strtolower(get_class($db)) == 'qunit_error') {
			echo $db->get('errorMessage');
			return false;
		}
   
		$sql = "SELECT * from settings WHERE user_id IS NULL OR user_id = ''";
		$sth = $db->execute($sql);
		$rows = $sth->fetchAllRows();
		$names = $sth->getNames();
		$setting_values = array();
		if (is_array($rows) && !empty($rows)) {
			foreach ($rows as $row) {
				$setting_values[$row[array_search('setting_key', $names)]] = $row[array_search('setting_value', $names)];
			}
		}
		$this->fill($setting_values);
		return true;
	}

	function getIniSize($setting) {
		$val = trim(ini_get($setting));
			
		if (!strlen($val)) $val = '10g';
			
		$last = strtolower($val{strlen($val)-1});
		switch($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}
}

?>