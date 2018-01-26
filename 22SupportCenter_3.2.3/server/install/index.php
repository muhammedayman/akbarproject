<?php
ini_set('zend.ze1_compatibility_mode', true);
error_reporting(0);
if( !ini_get('safe_mode') ){
	set_time_limit(0);
}

define('CLASS_PATH', dirname(dirname(__FILE__)) . '/include/');
define('SERVER_PATH', dirname(dirname(__FILE__)) . '/');
require_once CLASS_PATH.'/QUnit.class.php';


$config = QUnit::newObj('QUnit_Config', realpath(dirname(__FILE__)).'/../settings/config.inc.php');
QUnit::includeClass('QUnit_Db_Object');
$db = QUnit_Db_Object::getDriver($config->get('db'));

$state = QUnit::newObj('QUnit_State');
$state->setByRef('config', $config);
$db_connect = $db->connect();
$state->setByRef('db', $db_connect);
$req = QUnit::newObj('QUnit_Container', $_REQUEST);
$state->setByRef('request', $req);

$page = QUnit::newObj('Install_Main');
$page->set('templatePath', 'templates');
$page->setByRef('state', $state);
echo $page->render();

?>
