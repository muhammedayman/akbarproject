<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package Newsletter
*   @since Version 0.1
*   $Id: Config.class.php,v 1.5 2005/03/14 13:57:24 jsujan Exp $
*/

QUnit::includeClass('QUnit_Object');
class QUnit_State extends QUnit_Object {

    function _init() {
        $this->attrAccessor('config');
        $this->attrAccessor('db');
        $this->attrAccessor('request');
        $this->attrAccessor('loggers');
        $this->attrAccessor('lang'); 
        $this->loggers = QUnit::newObj('QUnit_Container');
        $this->lang = QUnit::newObj('QUnit_Lang_Parser');

        $this->attrAccessor('debug');

        $this->debug = false;
    }

    function addLogger($logger, $minLogLevel = '') {
        if(QUnit::existsClass('QUnit_Logger_Method_'.$logger)) {
            $oLogger = QUnit::newObj('QUnit_Logger_Method_'.$logger);
            $oLogger->set('minLogLevel', strlen($minLogLevel) ? $minLogLevel : $this->config->get('minLogLevel'));
            $oLogger->setByRef('config', $this->config);
            $this->loggers->set($logger, $oLogger);
        }
    }

    function log($level, $message, $type, $user_ids = '', $emails = '') {
    	foreach($this->loggers->data as $logger) {
            $logger->log($level, $message, $type, $user_ids, $emails);
        }
    }
}

?>