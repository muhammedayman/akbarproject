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

//sleep(rand(0,30));

define('SERVER_PATH', dirname(__FILE__) . '/');
define('CLASS_PATH', dirname(__FILE__) . '/include/');
require_once 'include/QUnit.class.php';

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
if ($config->get('defaultLanguage')) {
	$state->lang->setDefaultLanguage($config->get('defaultLanguage'));
}
						
$response = QUnit::newObj('QUnit_Rpc_Response');
$response->set('id', '1');

$oService = QUnit::newObj('QUnit_Rpc_Service');
$oService->setByRef('state', $state);
$oService->setByRef('response', $response);

$params = QUnit::newObj('QUnit_Rpc_Params');

$executor = QUnit::newObj('App_Service_ExecutionController');
$executor->setByRef('state', $state);
$executor->setByRef('response', $response);

if (!$executor->canStartProcess()) {
    die();
}

/*****************************************************************************/

$executor->setRunning();

if (!$oService->callService('MailParser', 'runParser', $params)) {
	$state->log('error', 'Mail Parser failed.', 'MailParser'); 
}

$executor->setRunning();

$outboxProcessor = QUnit::newObj('App_Service_OutboxMails');
$outboxProcessor->state = $state;
$outboxProcessor->response = $response;
$outboxProcessor->sendOutBoxMails();

$executor->setRunning();

//Database maintaninance
$dbMaintainance = QUnit::newObj('App_Service_Maintainance');
$dbMaintainance->state = $state;
$dbMaintainance->response = $response;

$executor->setRunning();
$dbMaintainance->executeEscalationRules();
$executor->setRunning();
$dbMaintainance->recomputeMissingIndex();
$executor->setRunning();
$dbMaintainance->generateKnowledgeBase();
$executor->setRunning();
$dbMaintainance->cleanupLog();
$executor->setRunning();
$dbMaintainance->cleanupFiles();
$executor->setRunning();
$dbMaintainance->optimizeTables();
$executor->setRunning();
$dbMaintainance->analyzeTables();
//finish execution of process
$executor->setRunning(false);

?>