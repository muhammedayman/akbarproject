<?php
/**
 *   Sending emails
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */
QUnit::includeClass("App_Template");
QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_SendMail extends QUnit_Rpc_Service {

    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
            default:
                return false;
                break;
        }
    }

    function loadMailAccount($account_id = '', $queue_email = '') {
        $response =& $this->getByRef('response');
        $paramsAccount = $this->createParamsObject();

        $load_default = true;

        if (strlen($account_id)) {
            $paramsAccount->set('account_id', $account_id);
            if (! $this->callService('MailAccounts', 'getMailAccountsListAllFields', $paramsAccount)) {
                $this->state->log('error', $this->state->lang->get('failedToSelectMailAccount', $response->error), 'Ticket');
                return false;
            }
            $res = & $response->getByRef('result');
            if ($res['count'] > 0) {
                $load_default = false;
            } else {
                $paramsAccount->set('account_id', false);
            }
        } else if (strlen($queue_email)) {
            $paramsAccount->set('account_email', $queue_email);
            if (! $this->callService('MailAccounts', 'getMailAccountsListAllFields', $paramsAccount)) {
                $this->state->log('error', $this->state->lang->get('failedToSelectMailAccount', $response->error), 'Ticket');
                return false;
            }
            $res = & $response->getByRef('result');
            if ($res['count'] > 0) {
                $load_default = false;
            } else {
                $paramsAccount->set('account_email', false);
            }
        }

        if ($load_default) {
            if (! $this->callService('MailAccounts', 'getDefaultMailAccount', $paramsAccount)) {
                $this->state->log('error', $this->state->lang->get('failedToSelectMailAccount', $response->error), 'Ticket');
                return false;
            }
        }

        $res = & $response->getByRef('result');
        if ($res['count'] == 0) {
            $this->state->log('error', $this->state->lang->get('failedToSelectMailAccount', $response->error), 'Ticket');
            return false;
        }
         
        $mailAccount = $res['rs']->getRows($res['md']);
        $mailAccount = $mailAccount[0];
        return $mailAccount;
    }

    function loadBodyFromTemplate($params) {
        if (!strlen($params->get('template')) && strlen($params->get('template_text'))) {
            return App_Template::evaluateTemplate($params, $params->get('template_text'));
        } else if ($template_text = App_Template::loadTemplateContent($params->get('template'), $params)) {
            return $template_text;
        }
        return false;
    }

    function coverMailWithGlobalBodyTemplate($params) {
        $response =& $this->getByRef('response');
        $templates = QUnit::newObj('App_Service_MailTemplates');
        $templates->state = $this->state;
        $templates->response = $response;

        if ($queue_id = $params->get('queue_id')) {
            $template = $templates->loadTemplate('CoverMail', $queue_id);
        } else {
            $template = $templates->loadTemplate('CoverMail');
        }

        return App_Template::evaluateTemplate($params, $template['body_html']);
    }

    function send($params) {
        $response =& $this->getByRef('response');

        if ($this->isDemoMode()) {
            return true;
        }

        if ($params->get('template_text')) {
            $params->set('body', App_Template::evaluateTemplate($params, $params->get('template_text')));
        } else if ($params->get('template')) {
            $params->set('body', $this->loadBodyFromTemplate($params));
        }

        //check mandatory parameters (to, etc.)
        if (!$params->check(array('to', 'subject', 'body'))) {
            $this->state->log('error', var_export($params, true), 'SendMail');
            $response->set('error', $this->state->lang->get('mailNotSent'));
            return false;
        }

        $oMail = QUnit::newObj('App_Mail_Outbox');
        $oMail->state = $this->state;
        
        if (!($mailAccount = $this->loadMailAccount($params->get('account_id'), $params->get('queue_email')))) {
            $this->state->log('error', var_export($params, true), 'SendMail');
            $response->set('error', $this->state->lang->get('NoMailAccountSelected'));
            return false;
        }

        $paramsSmtp = array();
        if ($mailAccount['use_smtp'] == 'y') {
            $paramsSmtp['host'] = ($mailAccount['smtp_ssl'] == 'y' && $mailAccount['smtp_tls'] != 'y' ? 'tls://' : '') . $mailAccount['smtp_server'];
            $paramsSmtp['port'] = $mailAccount['smtp_port'];
            $paramsSmtp['auth'] = ($mailAccount['smtp_require_auth'] == 'y');
            $paramsSmtp['username'] = $mailAccount['smtp_username'];
            $paramsSmtp['password'] = $mailAccount['smtp_password'];
            $paramsSmtp['tls'] = $mailAccount['smtp_tls'] == 'y';
            
            //compute localhost hostname
            $url = parse_url($this->state->config->get('applicationURL'));
            if (strlen($url['host'])) {
                $paramsSmtp['localhost'] = $url['host'];
            } else {
                $paramsSmtp['localhost'] = 'localhost';
            }
        }

        $headers = array();

        $headers['Date'] = date('j M Y H:i:s O');
        if (!($from = trim($params->get('from')))) {
            $from = $mailAccount['account_email'];
        }


        if ($mailAccount['from_name_format'] == 'c' && strlen(trim($mailAccount['from_name']))) {
            $from = '"' . $mailAccount['from_name'] . '" <' . $from . '>';
        } else if ($mailAccount['from_name_format'] == 'a' && strlen($params->get('from_name'))) {
            $from = '"' . $params->get('from_name') . '" <' . $from . '>';
        }

        if (!$this->state->config->get('mailClientIdentification')) {
            $headers['User-Agent'] = 'www.QualityUnit.com SupportCenter';
        } else {
            $headers['User-Agent'] = $this->state->config->get('mailClientIdentification');
        }
        $headers['To'] = $params->get('to');

        if ($params->get('Reply-To')) {
            $headers['Reply-To'] = $params->get('Reply-To');
        } else {
            $headers['Reply-To'] = $from;
        }


        if ($params->get('Auto-Submitted')) {
            $headers['Auto-Submitted'] = $params->get('Auto-Submitted');
        }

        if ($params->get('Thread-Index')) {
            $headers['Thread-Index'] = $params->get('Thread-Index');
        }
        if ($params->get('Message-ID')) {
            $headers['Message-ID'] = $params->get('Message-ID');
        }


        if ($params->get('cc')) $headers['Cc'] = $params->get('cc');
        if ($params->get('bcc')) {
            $headers['Bcc'] = $params->get('bcc');
        }

        $oMail->set('from', $from);
        
        $headers['Return-Path'] = $mailAccount['account_email'];
        
        if ($this->state->config->get('preventLoops') == 'y') {
            $to_addresses = QUnit_Net_Mail::prepareEmail($headers['To']);
            static $arrAccounts;
            if (empty($arrAccounts)) {
                $objAccounts = QUnit::newObj('App_Service_MailAccounts');
                $objAccounts->state = $this->state;
                $objAccounts->response = $response;
                $arrAccounts = $objAccounts->getMailAccountsMailsArray();
            }
            foreach ($arrAccounts as $account) {
                foreach ($to_addresses as $idx => $to_address) {
                    $to_mail_value = QUnit_Net_Mail::getEmailAddress($to_addresses, $idx);
                    if ($to_mail_value == $account['account_email']) {
                        return true;
                    }
                }
            }
        }
         
        $params->set('subject', str_replace(array("\n", "\r"), array(' ', ''), App_Template::evaluateTemplate($params, $params->get('subject'))));
         
        $oMail->set('subject', $params->get('subject'));
        $oMail->set('body', $this->coverMailWithGlobalBodyTemplate($params));
        $oMail->set('recipients', $params->get('to'));
        $oMail->set('headers', $headers);


        //attachments handling
        $current_mail_size = 0;
        if (is_array($att_ids = $params->get('attachment_ids'))) {
            foreach($att_ids as $id) {
                if (strlen($id)) {
                    $paramsFile = $this->createParamsObject();
                    $paramsFile->set('file_id', $id);
                    if ($this->callService('Files', 'getFilesList', $paramsFile)) {
                        $res = & $response->getByRef('result');
                        if ($res['count'] > 0) {
                            $file = $res['rs']->getRows($res['md']);
                            $file = $file[0];
                            $attachment = array();
                            $attachment['filename'] = $file['filename'];
                            $attachment['filetype'] = $file['filetype'];

                            $current_mail_size += $file['filesize'];
                            if ($this->state->config->getIniSize('memory_limit') > 0) {
                                if ($this->state->config->getIniSize('memory_limit') < 2 * $current_mail_size) {
                                    $response->set('error', $this->state->lang->get('sizeOfMailTooBig'));
                                    return false;
                                }
                            }

                            $objFile = QUnit::newObj('App_Service_Files');
                            $objFile->state = $this->state;
                            $objFile->response = $this->response;
                            $attachment['content'] = $objFile->getFileContent($id);
                            $oMail->addAttachment($attachment);
                        }
                    }
                }
            }
        }
        $oMail->set('ticket_id', $params->get('ticket_id'));
        $oMail->set('t_id', $params->get('t_id'));

        $oMail->set('mail_id', $params->get('mail_id'));
        $method = $mailAccount['use_smtp'] == 'y' ? 'smtp' : 'mail';
        if ($this->state->config->get('sendSystemMails') == 'y') {
            if(!$oMail->send($method, $paramsSmtp, $this->state->config->get('useOutbox') != 'y')) {
                $response->set('error', $this->state->lang->get('mailNotSent') . ': ' . $oMail->get('error'));
                return false;
            } else {
                $response->set('info', $this->state->lang->get('emailSent') . $params->get('to') . ' - ' . $oMail->get('subject'));
            }
        }
        return true;
    }
}
?>
