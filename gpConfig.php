<?php
session_start();

//Include Google client library 
include_once 'src/Google_Client.php';
include_once 'src/contrib/Google_Oauth2Service.php';

/*
 * Configuration and setup Google API
 */
$clientId = '376695148972-ptvnue6vfugvcv2sbftuciba28f2tj10.apps.googleusercontent.com';
$clientSecret = 'RxD2pyx7DpLeV5N6WR_EXuwp';
$redirectURL = 'http://localhost/akbar_cmp_project/';

//Call Google API
$gClient = new Google_Client();
$gClient->setApplicationName('Login to CodexWorld.com');
$gClient->setClientId($clientId);
$gClient->setClientSecret($clientSecret);
$gClient->setRedirectUri($redirectURL);

$google_oauthV2 = new Google_Oauth2Service($gClient);
?>