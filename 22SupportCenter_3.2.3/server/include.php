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
require_once (CLASS_PATH . 'QUnit.class.php');

$config = QUnit::newObj('QUnit_Config', 
					realpath(dirname(__FILE__)).'/settings/config.inc.php');
QUnit::includeClass('QUnit_Db_Object');
$dbObject = QUnit_Db_Object::getDriver($config->get('db'));
$db = $dbObject->connect();
$config->loadDbSettings($db);

$state = QUnit::newObj('QUnit_State');
$state->setByRef('config', $config);
$state->setByRef('db', $db);
$state->addLogger('File');
$state->addLogger('Db', $config->get('minLogLevel') == 'debug' ? 
						'info' : $config->get('minLogLevel'));

$response = QUnit::newObj('QUnit_Rpc_Response');
$response->set('id', '1');

$oService = QUnit::newObj('QUnit_Rpc_Service');
$oService->setByRef('state', $state);
$oService->setByRef('response', $response);

$params = QUnit::newObj('QUnit_Rpc_Params');

/*****************************************************************************/
?>