<?php
ini_set('zend.ze1_compatibility_mode', true);
ini_set('session.auto_start', 0);
$version = explode(".",function_exists("phpversion") ? phpversion() : "3.0.7");

$php_version = intval($version[0])*1000000+intval($version[1])*1000+intval($version[2]);

if($php_version<4003000) {
	echo "establishing SSL connections requires at least PHP version 4.3.0";
	exit();
}
if(!function_exists("extension_loaded")
|| !extension_loaded("openssl")) {
	echo "Establishing SSL connections requires the OpenSSL extension enabled";
	exit();
}


if ($fp = fsockopen('tls://pop.gmail.com', 995, $error, $error_str, 30)) {
	fclose($fp);
	echo "SSL Support functional.";
} else {
	echo $error . ' - ' . $error_str;
}
?>