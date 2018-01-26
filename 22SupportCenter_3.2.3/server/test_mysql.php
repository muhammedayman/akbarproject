<?php
ini_set('session.auto_start', 0);
ini_set('zend.ze1_compatibility_mode', true);
if( !ini_get('safe_mode') ){
	set_time_limit(0);
}
error_reporting(E_ALL & ~E_NOTICE);

//get server time zone and set it to avoid wrong settings
if (function_exists('date_default_timezone_set')) {
	error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
	date_default_timezone_set(date_default_timezone_get());
}

define('SERVER_PATH', dirname(__FILE__) . '/');
define('CLASS_PATH', dirname(__FILE__) . '/include/');
require_once 'settings/config.inc.php';

$link = mysqli_connect($config['db']['Host'], $config['db']['User'], $config['db']['Password']);
if (!$link) {
    die('Not connected : ' . mysqli_error());
}

$db_selected = mysqli_select_db($config['db']['Database'], $link);
if (!$db_selected) {
    die ('Can\'t use ' . $config['db']['Database'] . ': ' . mysqli_error());
}

mysqli_close($link);

echo 'Mysql is working.'
?>