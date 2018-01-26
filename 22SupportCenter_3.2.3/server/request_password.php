<?php
ini_set('zend.ze1_compatibility_mode', true);
ini_set('session.auto_start', 0);

global $arr_start;
if (empty($arr_start)) $arr_start = gettimeofday();

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

$state = QUnit::newObj('QUnit_State');
$state->setByRef('config', $config);
$state->setByRef('db', $connect);
if (strlen($config->get('logFile'))) {
    $state->addLogger('File');
}
$state->addLogger('Db', $config->get('minLogLevel') == 'debug' ? 'info' : $config->get('minLogLevel'));
$state->lang->setDefaultLanguage($config->get('defaultLanguage'));

$oService = QUnit::newObj('QUnit_Rpc_Service');
$oService->setByRef('state', $state);
$oService->setByRef('response', $response);


$params = array(
  		'knowledgeBaseURL' => $state->config->get('knowledgeBaseURL'),
  		'knowledgeBasePath' => $state->config->get('knowledgeBasePath'),
  		'applicationURL' => $state->config->get('applicationURL'),
  		'fileExtension' => '.html',
  		'pageTitle' => 'SupportCenter ' . $state->lang->get('SubmitNewTicket'),
  		'metaDescription' => 'SupportCenter ' . $state->lang->get('SubmitNewTicket'),
  		'crumbs' => '',
  		'languages' => ''
);

global $state;
global $config;


$paramsService = QUnit::newObj('QUnit_Rpc_Params');

$paramsService->setField('email', get_magic_quotes_gpc() ? stripslashes($_REQUEST['email']) : $_REQUEST['email']);
if (strlen($_REQUEST['email'])) {
    if ($oService->callService('Users', 'requestNewPassword', $paramsService)) {
        $params['message'] = $state->lang->get('newPasswordSent');
    } else {
        $response =& $oService->getByRef('response');
        $params['message'] = $response->get('error');
    }
}


ob_start();
include(SERVER_PATH . 'templates/knowledgebase/request_password.inc.php');
$params['content'] = ob_get_contents();
ob_end_clean();

QUnit::includeClass('App_Template');
echo App_Template::loadTemplateContent('knowledgebase/page_layout.html', $params);
?>