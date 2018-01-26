<?php
/**
*   Represents Pop3 Account
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/

QUnit::includeClass("QUnit_Object");
class App_Mail_Pop3Account extends QUnit_Object {
	var $account_id;
	var $account_name;
	var $account_email;
	var $from_name;
	var $from_name_format;
	var $pop3_server;
	var $pop3_port;
	var $pop3_ssl;
	var $pop3_username;
	var $pop3_password;
	var $use_smtp;
	var $smtp_server;
	var $smtp_port;
	var $smtp_ssl;
	var $smtp_require_auth;
	var $smtp_username;
	var $smtp_password;
	var $apop = 0;
	var $delete_messages = 'n';
	var $last_unique_msg_id;
}
?>