<?php
/**
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*   @since Version 1.0
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/



QUnit::includeClass("QUnit_Object");
class QUnit_Net_EmailValidator extends QUnit_Object {

	var $emailAddres = '';
	var $emailDomain = '';
	var $senderEmail = 'support@qualityunit.com';
	var $connectAddress = '';
	var $hostname = 'www.qualityunit.com';
	var $messages = array();
	var $messagesSA = array();
	var $messagesSAIP = array();

	var $_validateMX = true;
	var $_validateAddress = false;

	function QUnit_Net_EmailValidator() {
		QUnit_Object::QUnit_Object();
		if (isset($_SERVER['HTTP_HOST']) && strlen($_SERVER['HTTP_HOST'])) {
			$this->hostname = $_SERVER['HTTP_HOST'];
		}
	}

	function _rfc822_compactible_regexp($_email) {
		$qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
		$dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
		$atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c'.
        '\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
        $quoted_pair = '\\x5c[\\x00-\\x7f]';
        $domain_literal = "\\x5b($dtext|$quoted_pair)*\\x5d";
        $quoted_string = "\\x22($qtext|$quoted_pair)*\\x22";
        $domain_ref = $atom;
        $sub_domain = "($domain_ref|$domain_literal)";
        $word = "($atom|$quoted_string)";
        $domain = "$sub_domain(\\x2e$sub_domain)*";
        $local_part = "$word(\\x2e$word)*";
        $addr_spec = "$local_part\\x40$domain";
        return preg_match("!^$addr_spec$!", $_email) ? 1 : 0;
	}


	function _rfc2822_compactible_regexp($_email) {
		$regexp = "/^(.* )?[<\[]?((?:(?:(?:[a-zA-Z0-9][\.\-\+_]?)*)[a-zA-Z0-9])+)\@(((?:(?:(?:[a-zA-Z0-9][\.\-_]?){0,62})[a-zA-Z0-9])+)\.([a-zA-Z0-9]{2,6}))[>\]]?$/";
		if (preg_match($regexp, $_email, $_matches)) {
			$this->emailAddres = $_matches[2] . '@' . $_matches[3];
			$this->emailDomain = $_matches[3];
			return true;
		} else {
			return false;
		}
	}


	function _validateRegexp($_email) {
		$fchk = explode('@', $_email);
		if (!isset($fchk[1])) {
			return false;
		}
		$this->emailAddres = $_email;
		$this->emailDomain = $fchk[1];
		if (preg_match('/^[0-9]{1,3}[\.]{1,1}[0-9]{1,3}[\.]{1,1}[0-9]{1,3}[\.]{1,1}[0-9]{1,3}$/', $fchk[1])) {
			$regexIP = '(\\d|[1-9]\\d|1\\d\\d|2[0-4]\\d|25[0-5])';
			if (preg_match("/^$regexIP\\.$regexIP\\.$regexIP\\.$regexIP$/", $fchk[1])) {
				return true;
			}
		} else {
			if ($this->_rfc822_compactible_regexp($_email)) {
				return true;
			}
			if ($this->_rfc2822_compactible_regexp($_email)) {
				return true;
			}
		}
		return false;
	}


	function _validateMxDns() {
		if (getmxrr($this->emailDomain, $mxHost, $weights)) {
			$this->connectAddress = $mxHost[0];
			return true;
		} else {
			$host = $this->emailDomain;
			if (checkdnsrr($host, 'ANY')) {
				$this->connectAddress = $this->emailDomain;
				return true;
			}
		}
		return false;
	}

	function _validateServerAddress() {
		$result = true;
		$connect = @fsockopen ( $this->connectAddress, 25, $errorno, $errorstr, 1 );
		if ($connect) {
			stream_set_timeout($connect, 1);
			if (ereg("^220", $out = fgets($connect, 1024))) {
				fputs ($connect, "HELO " . $this->hostname . "\r\n");
				$out = fgets ( $connect, 1024 );
				fputs ($connect, "MAIL FROM: <{$this->senderEmail}>\r\n");
				$from = fgets ( $connect, 1024 );
				fputs ($connect, "RCPT TO: <{$this->emailAddres}>\r\n");
				$to = fgets ($connect, 1024);
				fputs ($connect, "QUIT\r\n");
				fclose($connect);

				if (!ereg ("^250", $from) || !ereg ( "^250", $to )) {
					$_vMessageSABs = 'not passed';
					$result = false;
				} else {
					$_vMessageSABs = 'passed';
				}
			} else {
				$_vMessageSABs = 'not passed';
				$result = false;
			}
		}  else {
			$_vMessageSABs = 'can not connect to the email server';
			$result = false;
		}
		$this->messagesSA['_vMessageSABs'] = $_vMessageSABs;
		return $result;
	}


	//return level of email check, which was ok
	function returnValidatedEmailStatus($_email) {
		$level = -1;
		if ($this->_validateRegexp($_email)) {
			$level = 1;
			if ($this->_validateMX == true) {
				if ($this->_validateMxDns()) {
					$level = 2;
					if ($this->_validateAddress == true) {
						if ($this->_validateServerAddress()) {
							$level = 3;
						}
					}
				}
			}
		}
		return $level;
	}
}

// support windows platforms
if (!function_exists ('getmxrr') ) {
	function getmxrr($hostname, &$mxhosts, &$mxweight) {
		if (!is_array ($mxhosts) ) {
			$mxhosts = array ();
		}

		if (!empty ($hostname) ) {
			$output = "";
			@exec ("nslookup.exe -type=MX " . escapeshellarg($hostname), $output);
			$imx=-1;

			foreach ($output as $line) {
				$parts = "";
				if (preg_match ("/^$hostname\tMX preference = ([0-9]+), mail exchanger = (.*)$/", $line, $parts) ) {
					$imx++;
					$mxweight[$imx] = $parts[1];
					$mxhosts[$imx] = $parts[2];
				}
			}
			return ($imx!=-1);
		}
		return false;
	}
}

if (!function_exists('checkdnsrr')) {
	function checkdnsrr($hostName, $recType = 'MX')
	{
		if(!empty($hostName)) {
			exec("nslookup.exe -type=$recType " . escapeshellarg($hostName), $result);
			// check each line to find the one that starts with the host
			// name. If it exists then the function succeeded.
			foreach ($result as $line) {
				if(eregi("^$hostName",$line)) {
					return true;
				}
			}
		}
		return false;
	}
}
?>
