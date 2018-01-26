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

class Install_Authorization extends QUnit_Ui_Widget_WizardStep {

    function _init() {
        parent::_init();
        $this->set('template', 'Authorization');
        $this->set('title', 'Authorization');
    }

    function process() {
    	if($this->processForm()) {
    		return true;
        }
        return false;
    }

    function processForm() {
    	$request = $this->state->getByRef('request');
    	if($request->get('submit')) {
    		if($this->authorize()) {
    			return false;
    		}
    		return true;
    	}
    	return false;
    }

    function authorize() {
    	global $config;
    	$request = $this->state->getByRef('request');
    	
    	foreach(array('productCode') as $key) {
    		if(strlen($request->get($key))) {
    			$this->set('errorMessage', $key.' cannot be empty');
    			return false;
            }
            $params[$key] = $request->get($key);
        }
    	
     	$domain = $this->parseDomain(strtolower($_SERVER['HTTP_HOST']));

    	$code = $request->get('productCode');
    	$host = 'support.qualityunit.com';
    	$path = "/server/custom/license_check.php";
        $host = 'www.qualityunit.com';
        $path = "/members/check.php";
    	if($domain === false) {
    		QUnit_Messager::setErrorMessage(L_G_AUTHBADHOST);
    		return false;
    	}

    	$req = "c=".urlencode($code)."&d=".urlencode($domain);

    	$header .= "POST $path HTTP/1.0\r\n";
    	$header .= "Host: $host\r\n";
    	$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
    	$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";

   		$fp = fsockopen($host, 80, $errno, $errstr, 30);
    	if ($fp) {
    		$this->set('errorMessage', 'Connection failed. Unable to authentificate.');
    		return false;
    	}
    	fwrite($fp, $header.$req);
    	$answer = $this->getPageContents($fp);
    	fclose($fp);
    	if(strstr($answer, 'FAILED')) {
    		$this->set('errorMessage', 'Invalid Product Code. Unable to authentificate.');
    		return false;
    	}
    	 
    	global $_SESSION;
    	$_SESSION['auth_id'] = $code;
    	$_SESSION['auth_license'] = $answer;
    	return $this->saveConfig();
    }


    function saveConfig() {
    	$request =& $this->state->getByRef('request');
    	$params = array();
    	$params['customerId'] = $_SESSION['auth_id'];
    	$params['license'] = $_SESSION['auth_license'];


        $db =& $this->state->getByRef('db');
    	    	
    	foreach ($params as $name => $val) {
	    	$sql = "INSERT INTO settings (setting_id, setting_key, setting_value) 
					VALUES ('" . md5($name) . "', '" . $name . "', '" . $val . "')";
	        $ret = $db->execute($sql);
	        if(QUnit_Object::isError($ret)) {
	        	$this->set('errorMessage',  $ret->get('errorMessage'));
				return false;
			}
    	}
		return true;
    }
    
    
    function getPageContents(&$fp) {
    	if(!preg_match('/^HTTP\/[0-9]\.[0-9]\s200 OK$/', trim(fgets($fp, 1024)))) {
    		return false;
    	}
    	while(preg_match('/^[-a-zA-z]+:.+$/', trim(fgets($fp, 1024)))) {
    	}
    	 
    	return trim(fgets($fp, 1024));
    }

    function parseDomain($host)
    {
    	// first remove any :port part
    	$pos = strpos($host, ':');
    	if($pos !== false) {
    		$host = substr($host, 0, $pos);
    	}

    	$domain = explode('.', $host);
    	$last = count($domain) - 1;
    	if(count($domain) < 1) {
    		return false;
    	} else if(count($domain) < 2) {
    		return $domain[0];
	    } elseif(count($domain) > 3) {
	        return $domain[$last-2].'.'.$domain[$last-1].'.'.$domain[$last];            
	    } else {
	        return $domain[$last-1].'.'.$domain[$last];
	    }
	}
     
}
?>