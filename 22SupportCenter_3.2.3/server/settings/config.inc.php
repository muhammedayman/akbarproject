<?php
  	//Database settings
    $config['db']['Host'] = 'localhost';
    $config['db']['User'] = 'root';
    $config['db']['Password'] = '';
    $config['db']['Driver'] = 'MySql';
    $config['db']['Database'] = 'aymanakb';

	$config['passwordEncoding'] = 'md5';  //possible values: md5, plain
    $config['authMethods'] = array('SupportCenter'); //array of authentification methods, which should be used during authentification request 
    $config['ldapServer'] = '';

    $config['DownloadManagerRepositoryPath'] = false;      //path to Download Manager import repository
    $config['DownloadManagerACLFile'] = false;          //access control import file
    
    define('SMTP_AUTH_METHODS', 'DIGEST-MD5,CRAM-MD5,LOGIN,PLAIN');
?>