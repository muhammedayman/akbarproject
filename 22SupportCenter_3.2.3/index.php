<?php
ini_set('zend.ze1_compatibility_mode', true);

if(!file_exists("server/settings/config.inc.php")) {
	header("Location: server/install/index.php");
	exit;
}

header("Location: client/index.php");
exit;
?>