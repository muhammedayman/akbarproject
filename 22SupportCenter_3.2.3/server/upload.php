<?php
ini_set('zend.ze1_compatibility_mode', true);
ini_set('session.auto_start', 0);
if( !ini_get('safe_mode') ){
	set_time_limit(0);
}
error_reporting(E_ALL & ~E_NOTICE);

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
$dbObject = QUnit_Db_Object::getDriver($config->get('db'));
$db = $dbObject->connect();
$config->loadDbSettings($db);

$session = QUnit::newObj('QUnit_Session');
$session->start();
?>
<?php echo '<?xml version="1.0" encoding="UTF-8" ?>'."\n"; ?>
<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>IframeIO</title>
	</head>
	<body>
		<?php
			if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
				$state = QUnit::newObj('QUnit_State');
				$state->setByRef('config', $config);
				$state->setByRef('db', $db);
				$state->addLogger('File');
				$state->addLogger('Db', 'info');
				
				$response = QUnit::newObj('QUnit_Rpc_Response');
				$response->set('id', '1');
				
				$oService = QUnit::newObj('QUnit_Rpc_Service');
				$oService->setByRef('state', $state);
				$oService->setByRef('response', $response);
				
				$downloader = QUnit::newObj('App_Service_Files');
				$downloader->state = $state;
				$downloader->response = $response;
				
				$downloader->uploadFile(); 
			} else {
				echo '<textarea>';
				echo '{"result": null, "error": "'.$_FILES['file']['error'].'"}';
				echo '</textarea>';
			}
		?>
	</body>
</html>
