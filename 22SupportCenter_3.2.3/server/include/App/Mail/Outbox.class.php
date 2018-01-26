<?php
/**
*   Represents Outbox entry
*
*   @author Viktor Zeman
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package SupportCenter
*/
QUnit::includeClass("QUnit_Net_Mail");

class App_Mail_Outbox extends QUnit_Net_Mail {

	function _init() {
        parent::_init();
        $this->attrAccessor('t_id');
        $this->attrAccessor('ticket_id');
        $this->attrAccessor('mail_id');
    }
	
    function send($method, $params = '', $immediate_delivery = true) {
    	if ($immediate_delivery) {
    		global $state;
    		if ($res = QUnit_Net_Mail::send($method, $params)) {
    			$state->log('info', $state->lang->get('mailSentFromOutbox', $this->get('subject'), $this->get('recipients')), 'Outbox', null, explode(',', $this->get('recipients')));
    		} else {
    			$state->log('info', $state->lang->get('failedToSendMailFromOutbox', $this->get('subject'), $this->get('recipients')), 'Outbox', null, explode(',', $this->get('recipients')));
    		}
    		return $res;
    	} else {
    		return $this->delayed_delivery($method, $params);
    	}
    }

    function delayed_delivery($method, $inParams) {
		$session = QUnit::newObj('QUnit_Session');
    	
    	$outbox = QUnit::newObj('App_Service_OutboxMails');

    	$params = $outbox->createParamsObject();
    	$params->setField('method', $method);
    	$params->setField('params', serialize($inParams));
    	$params->setField('mail_object', serialize($this));
    	$params->setField('subject', $this->get('subject'));
    	$params->setField('recipients', $this->get('recipients'));
    	if (strlen($this->get('mail_id'))) {
    		$params->setField('mail_id', $this->get('mail_id'));
    	}
    	if (strlen($this->get('t_id'))) {
    		$params->setField('ticket_id', $this->get('t_id'));
    	}else if (strlen($this->get('ticket_id'))) {
    		$params->setField('ticket_id', $this->get('ticket_id'));
    	}
    	if (strlen($session->getVar('userId'))) {
    		$params->setField('user_id', $session->getVar('userId'));
    	}
    	
    	if (!$outbox->callService('OutboxMails', 'insertMail', $params)) {
    		$this->set('error', $outbox->response->get('error'));
    		return false;
    	} else {
    		return true;
    	}
    }
}
?>