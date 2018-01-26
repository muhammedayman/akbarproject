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
require_once 'PEAR.php';
require_once 'Mail.php';
require_once 'Mail/mime.php';
require_once 'Mail/RFC822.php';

class QUnit_Net_Mail extends QUnit_Object {

    function _init() {
        parent::_init();
        $this->attrAccessor('subject');
        $this->attrAccessor('body');
        $this->attrAccessor('txt_mail');
        $this->attrAccessor('recipients');
        $this->attrAccessor('from');
        $this->attrAccessor('headers');
        $this->attrAccessor('attachments');
        $this->attrAccessor('images');
        $this->attrAccessor('error');

        $this->body = '';
        $this->headers = array();
        $this->attachments = array();
        $this->images = array();
    }

    function &createMethod($method, $params = '') {
        if(Qunit::existsClass('QUnit_Net_Mail_'.$method)) {
            $method =& Qunit::newObj('QUnit_Net_Mail_'.$method, $params);
            return $method;
        }
        return false;
    }

    function addHeader($name, $value) {
        $this->headers[$name] = $value;
    }

    function addAttachment($path) {
        $this->attachments[] = $path;
    }

    function addImage($path) {
        $this->images[] = $path;
    }

    function getImageContentType($filename) {
        $path = pathinfo($filename);
        switch(strtolower($path['extension'])) {
            case 'gif':
                $type = IMAGETYPE_GIF;
                break;
            case 'png':
                $type = IMAGETYPE_PNG;
                break;
            default:
                $type = IMAGETYPE_JPEG;
                break;
        }
        return image_type_to_mime_type($type);
    }

    function htmlToTxt($body) {
        $txtconverter = QUnit::newObj('QUnit_Txt_Html2Text');
        $txtconverter->set_html($body);
        return $txtconverter->get_text();
    }

    function send($method, $params = '') {
        if(!($method =& $this->createMethod($method, $params))) {
            $this->set("error", "Cannot instantiate mail method: ".$method);
            return false;
        }

        if(!strlen($this->get('recipients'))) {
            $this->set("error", "Recipients empty");
            return false;
        }

        $mime = new Mail_mime("\n");
        $mime->_build_params['html_charset'] = 'UTF-8';
        $mime->_build_params['text_charset'] = 'UTF-8';
        $mime->_build_params['head_charset'] = 'UTF-8';

        if ($this->get('txt_mail')) {
            $mime->setTXTBody($this->get('body'));
        } else {
            $mime->setHTMLBody($this->get('body'));
            $mime->setTXTBody($this->htmlToTxt($this->get('body')));
        }

        foreach($this->get('attachments') as $attachment) {
            if (is_array($attachment)) {
                if (strlen($attachment['content'])) {
                    $ret = $mime->addAttachment($attachment['content'],
                    $attachment['filetype'],
                    $attachment['filename'],
                    false);
                } else {
                    $ret = $mime->addAttachment($attachment['filename'],
                    $attachment['filetype'],
                    $attachment['filename'],
                    true);
                }
            } else {
                $ret = $mime->addAttachment($attachment, 'text/plain');
            }
            if(PEAR::isError($ret)) {
                $this->set("error", "Adding attachment error: ".$ret->getMessage());
                return false;
            }
        }
        foreach($this->get('images') as $image) {
            $ret = $mime->addHTMLImage($image, $this->getImageContentType($image));
            if(PEAR::isError($ret)) {
                $this->set("error", "Adding image error: ".$ret->getMessage());
                return false;
            }
        }

        $body = $mime->get();

        $this->addHeader('From', $this->get('from'));
        $this->addHeader('Subject', $this->get('subject'));

        if (is_object($this->state->config) && strlen($this->state->config->get('blindCarbonCopy'))) {
            $headers = $this->get('headers');
            $this->set('recipients', $this->get('recipients') . ',' . $this->state->config->get('blindCarbonCopy'));
            if (!is_a($method, 'QUnit_Net_Mail_Smtp')) {
                if (isset($headers['Bcc']) && strlen($headers['Bcc'])) {
                    $headers['Bcc'] .= ',' . $this->state->config->get('blindCarbonCopy');
                } else {
                    $headers['Bcc'] = $this->state->config->get('blindCarbonCopy');
                }
            }

            $this->set('headers', $headers);
        }


        $ret = $method->send($this->get('recipients'), $mime->headers($this->get('headers')), $body);
        if(PEAR::isError($ret)) {
            $this->set("error", $ret->getMessage());
            return false;
        }
        return true;
    }

    function prepareEmail($inValue) {
        $obj = QUnit::newObj('Mail_RFC822');
        $obj->validate = false;
        return $obj->parseAddressList($inValue);
    }

    function getEmailAddress($email, $index = 0) {
        if (is_array($email) &&
        count($email) > 0 &&
        isset($email[$index]->mailbox) &&
        isset($email[$index]->host) &&
        strlen($email[$index]->mailbox) &&
        strlen($email[$index]->host)) {
            return trim($email[$index]->mailbox) . '@' . trim($email[$index]->host);
        } else {
            return '';
        }
    }

    function getPersonalName($email, $index = 0) {
        if (is_array($email) &&
        count($email) > 0 &&
        isset($email[$index]->personal) &&
        strlen($email[$index]->personal)) {
            return str_replace('"', '', $email[$index]->personal);
        } else {
            return '';
        }
    }

}

?>