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

class Install_DbConfig extends QUnit_Ui_Widget_WizardStep {

    function _init() {
        parent::_init();
        $this->set('template', 'DbConfig');
        $this->set('title', 'Set Up Database');
    }

    function process() {
        if($this->processForm()) {
            return true;
        }
        return false;
    }

    function render() {
        return parent::render();
    }

    function processForm() {
        $request = $this->state->getByRef('request');
        if($request->get('submit')) {
            if(!$this->createDatabase()) {
                return false;
            }
            return true;
        }
        return false;
    }

    function createDatabase() {
        $request =& $this->state->getByRef('request');

        foreach(array('Host', 'Database', 'User') as $key) {
            if(!strlen($request->get($key))) {
                $this->set('errorMessage', $key.' cannot be empty');
                return false;
            }
            $params[$key] = $request->get($key);
        }
        $params['Password'] = $request->get('Password');
        $params['Driver'] = 'MySql';

        if(!$this->saveConfig($params)) {
            return false;
        }

        QUnit::includeClass('QUnit_Db_Object');
        $dbObj = QUnit_Db_Object::getDriver($params);
        $db = $dbObj->connect();
        if(QUnit_Object::isError($db)) {
        	$this->set('errorMessage',  $db->get('errorMessage'));
        	return false;
        }

      /*  $mysqlVersion = $db->getVersion();
        if(version_compare($mysqlVersion, "5.6.31") < 0) {
        	$this->set('errorMessage',  "MySql version should be at least 4.1.0 (Your MySQL version is $mysqlVersion)");
        	return false;
        }
*/
        
        $sql = "SET storage_engine=MYISAM";
        $ret = $db->execute(trim($sql));
        if(QUnit_Object::isError($ret)) {
        	$this->set('errorMessage',  $ret->get('errorMessage'));
			return false;
		}

		//set UTF-8 collaction as default for this database
		$sql = "ALTER DATABASE " . '`' . $request->get('Database') . '`' . " DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
        $ret = $db->execute(trim($sql));
        if(QUnit_Object::isError($ret)) {
            $this->set('errorMessage',  $ret->get('errorMessage'));
            return false;
        }
        
        foreach(explode(';', file_get_contents('db/create.sql')) as $statement) {
            if(strlen(trim($statement))) {
                $ret = $db->execute(trim($statement));
                if(QUnit_Object::isError($ret)) {
                    $this->set('errorMessage',  $ret->get('errorMessage'));
                    return false;
                }
            }
        }
        return true;
    }

    function saveConfig($params) {
    	if(!is_writable('../settings')) {
    		$this->set('errorMessage', 'Directory settings is not writable');
    		return false;
        }
		if (file_exists('../settings/config.inc.php')) {        
        	$fileName = '../settings/config.inc.php';
		} else {
			$fileName = '../settings/config.inc.php.dist';
		}
        
        $fileObj = QUnit::newObj('QUnit_Io_File', realpath($fileName));
        if(!($content = $fileObj->getContents())) {
            $this->set('errorMessage', 'Cannot read template config file');
            return false;
        }

        foreach($params as $key => $value) {
            $content = str_replace('\\', '', preg_replace("/(config\['db'\]\['$key'\]\s=\s)'.*'/", "$1'$value'", $content));
        }
        $fileObj = QUnit::newObj('QUnit_Io_File', realpath('../settings').'/config.inc.php', "w");
        if(!$fileObj->putContents($content)) {
            $this->set('errorMessage', 'Cannot write config file');
            return false;
        }
        return true;
    }

}

?>
