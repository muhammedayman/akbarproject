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

class Install_DbUpgrade extends QUnit_Ui_Widget_WizardStep {
	var $update_messages = array();
	
    function _init() {
        parent::_init();
        $this->set('template', 'DbUpgrade');
        $this->set('title', 'Upgrade');
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
        $this->request = $this->state->getByRef('request');
        if($this->request->get('submit')) {
            if(!$this->upgrade()) {
                return false;
            }
            return true;
        }
        return false;
    }

    function upgrade() {        
        $db =& $this->state->getByRef('db');
        
        $sth = $db->execute("select * from settings where setting_key = 'dbLevel'");
    	if(QUnit_Object::isError($sth)) {
        	$this->set('errorMessage',  $sth->get('errorMessage'));
            return false;
        }
        if($sth->rowCount() != 1) {
        	$dbLevel = '0';
        } else {
        	$row = $sth->fetchArray();
        	$dbLevel = $row['setting_value'];
        }
        
        if ($dh = opendir(SERVER_PATH . "install/db/updates")) {
        	$arrDirs = array();
			while (($file = readdir($dh)) !== false) {
			    if ($file == "." || $file == "..") {
			        continue;
			    }
			    if(version_compare($file, $dbLevel) > 0 && strlen($file)) {
			    	$arrDirs[$file] = $file;
			    }
			}
			
			while (!empty($arrDirs)) {
				$minVersion = null;
				foreach ($arrDirs as $dir) {
					if ($minVersion == null) {
						$minVersion = $dir;
					} else if (version_compare($minVersion, $dir) > 0) {
						$minVersion = $dir;
					}
				}
				if (strlen($minVersion)) {
					unset($arrDirs[$minVersion]);
			    	$this->update_messages[] = '<b>' . $minVersion . '</b>';
					if(!$this->executeUpdatesInDir(SERVER_PATH . "install/db/updates/".$minVersion)) {
						return false;
					}
				}
			}
			
        }        
        return true;
    }
    
    function executeUpdatesInDir($dir) {
        if ($dh = opendir($dir)) {
        	while (($file = readdir($dh)) !== false) {
                if(preg_match('/^.*\.sql$/', $file)) {
                    $this->update_messages[] = '<b>' . $file . '</b>';
                	if (!$this->executeSqlFile($dir."/".$file)) {
                		return false;
                	}
                }
            }
        }
        return true;    	
    }
    
    function executeSqlFile($file) {
    	$db =& $this->state->getByRef('db');
        foreach(explode(';', file_get_contents($file)) as $statement) {
           $statement = trim($statement);
           if(strlen($statement)) {
           		if (substr($statement, 0, 1) == '#') {
           			$statement = trim(ltrim($statement, '#'));
           			$mandatory = false;
           		} else {
           			$mandatory = true;
           		}
           	
                $ret = $db->execute(trim($statement));
                $this->update_messages[] = 'Executing: ' . trim($statement);
                if(QUnit_Object::isError($ret)) {
                    if ($mandatory) {
                        $this->set('errorMessage',  $ret->get('errorMessage'));
                        $this->update_messages[] = 'Failed execution of SQL with error: ' . $ret->get('errorMessage');
                        return false;
                    }
                }
            }
        }
        return true;
    }
}

?>