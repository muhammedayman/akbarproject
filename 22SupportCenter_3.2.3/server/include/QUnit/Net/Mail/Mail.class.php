<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package QUnit
*   @since Version 0.1
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

QUnit::includeClass('QUnit_Object');

class QUnit_Net_Mail_Mail extends QUnit_Object {

    function _init($params) {
        parent::_init();
    }

    function send($recipients, $headers, $body) {
        $mail =& Mail::factory('mail');
        return $mail->send($recipients, $headers, $body);
    }
}

?>