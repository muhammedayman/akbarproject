<?php
ini_set('zend.ze1_compatibility_mode', true);
ini_set('session.auto_start', 0);
if( !ini_get('safe_mode') ){
	set_time_limit(0);
}
error_reporting(0);

//get server time zone and set it to avoid wrong settings
if (function_exists('date_default_timezone_set')) {
	error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
	date_default_timezone_set(date_default_timezone_get());
}
ini_set('session.gc_maxlifetime', 28800);

define('SERVER_PATH', dirname(__FILE__) . '/');
define('CLASS_PATH', dirname(__FILE__) . '/include/');
require_once 'include/QUnit.class.php';

$config = QUnit::newObj('QUnit_Config', realpath(dirname(__FILE__)).'/settings/config.inc.php');
QUnit::includeClass('QUnit_Db_Object');
$db = QUnit_Db_Object::getDriver($config->get('db'));
$connect = $db->connect();

$config->loadDbSettings($connect);

$session = QUnit::newObj('QUnit_Session');
$session->start();


$state = QUnit::newObj('QUnit_State');
$state->setByRef('config', $config);
$state->setByRef('db', $connect);
if (strlen($config->get('logFile'))) {
	$state->addLogger('File');
}
$state->addLogger('Db', $config->get('minLogLevel') == 'debug' ? 'info' : $config->get('minLogLevel'));
$state->lang->setDefaultLanguage($config->get('defaultLanguage'));


$response = QUnit::newObj('QUnit_Rpc_Response');
$response->set('id', '1');

$oService = QUnit::newObj('QUnit_Rpc_Service');
$oService->setByRef('state', $state);
$oService->setByRef('response', $response);


$params = QUnit::newObj('QUnit_Rpc_Params');


$params->set('langugage', get_magic_quotes_gpc() ? stripslashes($_REQUEST['language']) : $_REQUEST['language']);
$params->set('email', get_magic_quotes_gpc() ? stripslashes($_REQUEST['email']) : $_REQUEST['email']);
$params->set('password', md5(get_magic_quotes_gpc() ? stripslashes($_REQUEST['password']) : $_REQUEST['password']));

$referer_url = $_SERVER['HTTP_REFERER'];
$referer_url = preg_replace('/message=.*?&/', '', $referer_url);
$referer_url = preg_replace('/error=.*?&/', '', $referer_url);

if ($oService->callService('Users', 'login', $params)) {
	$rs = $response->getResultVar('rs');
	$md = $response->getResultVar('md');
	$rows = $rs->getRows($md);
	
	$redirect_url = $config->get('applicationURL') . 'client/index.php?hash=' . $rows[0]['hash'] . '&language=' . $_REQUEST['language'];
} else {
	//failed request
	if ($_REQUEST['failed_url'] == 'referer' || $_REQUEST['failed_url'] == 'referrer' || !strlen($_REQUEST['failed_url'])) {
		//open REFERER_URL
		$redirect_url = $referer_url; 
	} else {
		//open url from failed_url
		$redirect_url = $_REQUEST['failed_url']; 
	}
	$redirect_url .= (strpos($redirect_url, '?') === false ? '?&' : '&') . 
					'login_error=' . urlencode($response->get('error')) . '&';
}

header('Location: ' . $redirect_url);
?>