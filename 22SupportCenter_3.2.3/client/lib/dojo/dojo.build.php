<?php
ini_set('session.auto_start', 0);

if( !ini_get('safe_mode') ){
	set_time_limit(0);
}
error_reporting(E_ALL & ~E_NOTICE);

$encodings = array();
$version = "ver1build110329064447";

if (isset($_COOKIE["version"])) {
	$version_cookie = explode(" ", $_COOKIE["version"]);
} else {
	$version_cookie = array("");
}

if($version == $version_cookie[0] && (isset($_SERVER["HTTP_CACHE_CONTROL"]) && $_SERVER["HTTP_CACHE_CONTROL"] == "max-age=0")) {
	header("HTTP/1.1 304 Not Modified");
	die;
}

if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && !(ini_get('zlib.output_compression')==true || strtolower(ini_get('zlib.output_compression'))=='on')) {
	$encodings = explode(',', strtolower(preg_replace("/s+/", "", $_SERVER['HTTP_ACCEPT_ENCODING'])));
	if (in_array('gzip', $encodings)) {
			
		header("Content-Encoding: gzip");
		$name = 'dojo.build.js.gz';
		$fp = fopen($name, 'rb');
		header("Content-Type: text/html");
		header("Content-Length: " . filesize($name));

		$offset = 3600 * 24*30;	
		header("Cache-Control: max-age=$offset, public");
		header("Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT");		
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", time() - 100000) . " GMT");
		header("Connection: Keep-Alive");
		
		fpassthru($fp);
		die;
	}
}

$offset = 3600 * 24*30;	
header("Cache-Control: max-age=$offset, public");
header("Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT");		
header("Last-Modified: " . gmdate("D, d M Y H:i:s", time() - 100000) . " GMT");
header("Connection: Keep-Alive");
$name = "dojo.build.js";
$fp = fopen($name, 'r');
header("Content-Type: text/html");
header("Content-Length: " . filesize($name));
fpassthru($fp);
?>
