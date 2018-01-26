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


$objFields = QUnit::newObj('App_Service_Fields');
$objFields->state = $state;
$objFields->response = $response;
$customFields = $objFields->getCustomFieldsArray();

$params = QUnit::newObj('QUnit_Rpc_Params');
$params->set('subject', get_magic_quotes_gpc() ? stripslashes($_REQUEST['subject']) : $_REQUEST['subject']);
$params->set('body', get_magic_quotes_gpc() ? stripslashes($_REQUEST['body']) : $_REQUEST['body']);
$params->set('name', get_magic_quotes_gpc() ? stripslashes($_REQUEST['name']) : $_REQUEST['name']);
$params->set('email', get_magic_quotes_gpc() ? stripslashes($_REQUEST['email']) : $_REQUEST['email']);
$params->set('queue_id', get_magic_quotes_gpc() ? stripslashes($_REQUEST['queue_id']) : $_REQUEST['queue_id']);

$arrCustom = array();
foreach ($customFields as $field_id => $field_name) {
	if (isset($_REQUEST['custom_' . $field_id])) {
		$arrCustom[$field_id] = get_magic_quotes_gpc() ? stripslashes($_REQUEST['custom_' . $field_id]) : $_REQUEST['custom_' . $field_id];
	}
}
$params->set('custom', $arrCustom);
$params->set('isPlainText', 1);

$referer_url = $_SERVER['HTTP_REFERER'];
$referer_url = preg_replace('/message=.*?&/', '', $referer_url);
$referer_url = preg_replace('/error=.*?&/', '', $referer_url);


/***********************************************
 * reCaptcha section
************************************************/

$privateKey = '';
if (strlen($privateKey)) {
    require_once('recaptchalib.php');
    
    $resp = recaptcha_check_answer ($privateKey,
                                    $_SERVER["REMOTE_ADDR"],
                                    $_POST["recaptcha_challenge_field"],
                                    $_POST["recaptcha_response_field"]);
    
    if (!$resp->is_valid) {
        //failed request
        if ($_REQUEST['failed_url'] == 'referer' || !strlen($_REQUEST['failed_url'])) {
            //open REFERER_URL
            $redirect_url = $referer_url; 
        } else {
            //open url from failed_url
            $redirect_url = $_REQUEST['failed_url']; 
        }
        $redirect_url .= (strpos($redirect_url, '?') === false ? '?&' : '&') . 
                        'error=' . urlencode("The reCAPTCHA was not entered correctly. Go back and try it again.") . '&';
    header('Location: ' . $redirect_url);
    die();
    }
}
/***********************************************/





if ($oService->callService('Tickets', 'newAnonymTicket', $params)) {
	if ($_REQUEST['success_url'] == 'referer' || !strlen($_REQUEST['success_url'])) {
		$redirect_url = $referer_url; 
	} else {
		$redirect_url = $_REQUEST['success_url']; 
	}
	$redirect_url .= (strpos($redirect_url, '?') === false ? '?&' : '&') . 
					'message=' . urlencode($state->lang->get('ticketSuccessfullyCreated')) . '&';
} else {
	//failed request
	if ($_REQUEST['failed_url'] == 'referer' || !strlen($_REQUEST['failed_url'])) {
		//open REFERER_URL
		$redirect_url = $referer_url; 
	} else {
		//open url from failed_url
		$redirect_url = $_REQUEST['failed_url']; 
	}
	$redirect_url .= (strpos($redirect_url, '?') === false ? '?&' : '&') . 
					'error=' . urlencode($response->get('error')) . '&';
}

header('Location: ' . $redirect_url);
?>