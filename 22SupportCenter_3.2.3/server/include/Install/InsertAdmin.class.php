<?php
/**
*
*   @author Juraj Sujan
*   @copyright Copyright (c) Quality Unit s.r.o.
*   @package AddressCorrector_Core
*   @since Version 0.1
*   $Id: Object.class.php,v 1.9 2005/03/21 18:25:58 jsujan Exp $
*/

QUnit::includeClass('QUnit_Ui_Widget_WizardStep');

class Install_InsertAdmin extends QUnit_Ui_Widget_WizardStep {

    function _init() {
        parent::_init();
        $this->set('template', 'InsertAdmin');
        $this->set('title', 'Insert Admin');
    }

    function process() {
        if($this->processForm()) {
            return true;
        }
        return false;
    }

    function render() {
        return parent::render();
    }

    function processForm() {
        $request = $this->state->getByRef('request');
        if($request->get('submit')) {
            if(!$this->insertAdmin()) {
                return false;
            }
            return true;
        }
        return false;
    }

    function insertAdmin() {
        $request =& $this->state->getByRef('request');

        foreach(array('Name', 'Username', 'Password') as $key) {
            if(!strlen($request->get($key))) {
                $this->set('errorMessage', $key.' cannot be empty');
                return false;
            }
        }

        if($request->get('Password') != $request->get('RePassword')) {
        	$this->set('errorMessage', 'Passwords do not match');
        	return false;
        }

        $db =& $this->state->getByRef('db');

        $sql = "INSERT INTO users (user_id, name, email, email_quality, created, password, signature, hash, user_type, picture_id, hide_suggestions, disable_dm_notifications)
				VALUES ('" . md5(uniqid(rand(), true)) . "', '" . $request->get('Name') . "', '" . $request->get('Username') . "', 0, '" . $db->getDateString() . "', '" . md5($request->get('Password')) . "', '&nbsp;', '" . md5(uniqid(rand(), true)) . "', 'a', NULL, 'n', 'n')";        
        
        $ret = $db->execute($sql);
        if(QUnit_Object::isError($ret)) {
        	$this->errorMessage = $ret->get('errorMessage');
        	echo( $this->errorMessage);
        	return false;
        }
        return $this->insertDefaultQueue() && $this->insertDefaultPriorities() && $this->insertDefaultStatuses() && $this->insertMailTemplates();
    }

    function insertDefaultQueue() {
    	$request =& $this->state->getByRef('request');

    	$db =& $this->state->getByRef('db');

    	$sql = "INSERT INTO queues(queue_id, name, ticket_id_prefix, queue_email, answer_time, autorespond_nt, autorespond_nt_subject, autorespond_nt_body, is_default, public, opened_for_users) 
				VALUES ('e78ad75166bfafe4e763590de79b2778', 'DefaultQueue', 'DQ', '', 24, 'n', '', '', 'y', 'y', 'n');";

    	$ret = $db->execute($sql);
    	if(QUnit_Object::isError($ret)) {
            $this->errorMessage = $ret->get('errorMessage');
            echo( $this->errorMessage);
            return false;
        }
        return true;
    }

    function insertDefaultPriorities() {
    	$request =& $this->state->getByRef('request');

    	$db =& $this->state->getByRef('db');

    	$sql = "INSERT INTO priorities(priority, priority_name)	VALUES (1, 'Low')";

    	$ret = $db->execute($sql);
    	if(QUnit_Object::isError($ret)) {
            $this->errorMessage = $ret->get('errorMessage');
            echo( $this->errorMessage);
            return false;
        }

    	$sql = "INSERT INTO priorities(priority, priority_name)	VALUES (50, 'Normal')";

    	$ret = $db->execute($sql);
    	if(QUnit_Object::isError($ret)) {
            $this->errorMessage = $ret->get('errorMessage');
            echo( $this->errorMessage);
            return false;
        }
        
    	$sql = "INSERT INTO priorities(priority, priority_name)	VALUES (100, 'High')";

    	$ret = $db->execute($sql);
    	if(QUnit_Object::isError($ret)) {
            $this->errorMessage = $ret->get('errorMessage');
            echo( $this->errorMessage);
            return false;
        }
        
    	$sql = "INSERT INTO priorities(priority, priority_name)	VALUES (250, 'Immediate')";

    	$ret = $db->execute($sql);
    	if(QUnit_Object::isError($ret)) {
            $this->errorMessage = $ret->get('errorMessage');
            echo( $this->errorMessage);
            return false;
        }
        
        return true;
    }
    
    
    function insertDefaultStatuses() {
    	$request =& $this->state->getByRef('request');

    	$db =& $this->state->getByRef('db');

    	$sql = "INSERT INTO statuses(status, status_name, color, img, due_basetime, due) VALUES ('c', 'Customer Reply', '#ffffD0', 'status_customer_reply.png', 'm', 'y')";

    	$ret = $db->execute($sql);
    	if(QUnit_Object::isError($ret)) {
            $this->errorMessage = $ret->get('errorMessage');
            echo( $this->errorMessage);
            return false;
        }
    	$sql = "INSERT INTO statuses(status, status_name, color, img, due_basetime, due) VALUES ('a', 'Awaiting Reply', '#aaffdd', 'status_awaiting_reply.png', 'm', 'n')";

    	$ret = $db->execute($sql);
    	if(QUnit_Object::isError($ret)) {
            $this->errorMessage = $ret->get('errorMessage');
            echo( $this->errorMessage);
            return false;
        }
        $sql = "INSERT INTO statuses(status, status_name, color, img, due_basetime, due) VALUES ('b', 'Bounced', 'gray', 'status_bounced.png', 'm', 'n')";

    	$ret = $db->execute($sql);
    	if(QUnit_Object::isError($ret)) {
            $this->errorMessage = $ret->get('errorMessage');
            echo( $this->errorMessage);
            return false;
        }
        $sql = "INSERT INTO statuses(status, status_name, color, img, due_basetime, due) VALUES ('w', 'Work In Progress', '#c8c8ff', 'status_work.png', 'm', 'y')";

    	$ret = $db->execute($sql);
    	if(QUnit_Object::isError($ret)) {
            $this->errorMessage = $ret->get('errorMessage');
            echo( $this->errorMessage);
            return false;
        }
        
    	$sql = "INSERT INTO statuses(status, status_name, color, img, due_basetime, due) VALUES ('n', 'New', '#ffffA0', 'status_new.png', 'm', 'y')";

    	$ret = $db->execute($sql);
    	if(QUnit_Object::isError($ret)) {
            $this->errorMessage = $ret->get('errorMessage');
            echo( $this->errorMessage);
            return false;
        }

    	$sql = "INSERT INTO statuses(status, status_name, color, img, due_basetime, due) VALUES ('r', 'Resolved', '#cceedd', 'status_resolved.png', 'm', 'n')";

    	$ret = $db->execute($sql);
    	if(QUnit_Object::isError($ret)) {
            $this->errorMessage = $ret->get('errorMessage');
            echo( $this->errorMessage);
            return false;
        }
        
    	$sql = "INSERT INTO statuses(status, status_name, color, img, due_basetime, due) VALUES ('s', 'Spam', 'gray', 'status_spam.png', 'm', 'n')";

    	$ret = $db->execute($sql);
    	if(QUnit_Object::isError($ret)) {
            $this->errorMessage = $ret->get('errorMessage');
            echo( $this->errorMessage);
            return false;
        }
        
    	$sql = "INSERT INTO statuses(status, status_name, color, img, due_basetime, due) VALUES ('d', 'Dead', 'gray', 'status_dead.png', 'm', 'n')";

    	$ret = $db->execute($sql);
    	if(QUnit_Object::isError($ret)) {
            $this->errorMessage = $ret->get('errorMessage');
            echo( $this->errorMessage);
            return false;
        }
        
        return true;
    }
    
    
    function insertMailTemplates() {
    	$request =& $this->state->getByRef('request');

    	$db =& $this->state->getByRef('db');

    	$sql = "INSERT INTO mail_templates(template_id, queue_id, is_system, is_queuebased, subject, body_html, body_text) 
									VALUES ('RegistrationMail', 'all', 'y', 'n', 'Welcome in SupportCenter', '<p>
Dear \${name},<br />
you have been successfully registered in our trouble ticket system.<br />
Your account information:<br />
Username: \${to}<br />
Password: \${password}<br />
<br />
After login you can check status of your tickets or report new tickets here: \${appUrl}<br />
<br />
Regards,<br />
<br />
Quality Unit Support Team<br />
</p>', '')";

    	$ret = $db->execute($sql);
    	if(QUnit_Object::isError($ret)) {
            $this->errorMessage = $ret->get('errorMessage');
            echo( $this->errorMessage);
            return false;
        }

    	$sql = "INSERT INTO mail_templates(template_id, queue_id, is_system, is_queuebased, subject, body_html, body_text) 
									VALUES ('RequestNewPasswordMail', 'all', 'y', 'n', 'You requested new Password from our SupportCenter', '<p>
Dear \${name},<br/>
you have requested new password.<br />
Your new account information:<br />
Username: \${to}<br />
Password: \${password}<br />
<br />
After login you can check status of your tickets or report new tickets here: \${appUrl}<br />
<br />
Regards,<br />
<br />
Quality Unit Support Team<br />
</p>', '')";

    	$ret = $db->execute($sql);
    	if(QUnit_Object::isError($ret)) {
            $this->errorMessage = $ret->get('errorMessage');
            echo( $this->errorMessage);
            return false;
        }
        
    	$sql = "INSERT INTO mail_templates(template_id, queue_id, is_system, is_queuebased, subject, body_html, body_text) 
									VALUES ('CoverMail', 'all', 'y', 'y', '\${subject}', '\${body}
<hr/>
<a href=\"http://liveagent.qualityunit.com/chat/chat/index.html\" target=\"_blank\" style=\"font-size: 10; font-family: sans-serif\">Start Live Chat</a>&nbsp;
<a href=\"http://www.qualityunit.com/supportcenter/\" target=\"_blank\" style=\"font-size: 10; font-family: sans-serif\">Email handled by SupportCenter</a><br/>', '\${body}


--------------------------------------------------------------------------------
Email handled by SupportCenter
URL: http://www.qualityunit.com/supportcenter/')";

    	$ret = $db->execute($sql);
    	if(QUnit_Object::isError($ret)) {
            $this->errorMessage = $ret->get('errorMessage');
            echo( $this->errorMessage);
            return false;
        }
        
        return true;
    }
    
    
    
}

?>