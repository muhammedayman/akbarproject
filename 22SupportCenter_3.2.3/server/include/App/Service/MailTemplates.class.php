<?php
/**
 *   Mail templates handling
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("QUnit_Rpc_Service");

/* register translation:
 * $this->state->lang->get('VariablesCoverMail')
 * $this->state->lang->get('VariablesRegistrationMail')
 * $this->state->lang->get('VariablesRequestNewPasswordMail')
*/

class App_Service_MailTemplates extends QUnit_Rpc_Service {

	function initMethod($params) {
		$method = $params->get('method');

		switch($method) {
			case 'insertTemplate':
			case 'deleteTemplate':
			case 'updateTemplate':
			case 'getTemplatesList':
			case 'getTemplatesFormList':
				return $this->callService('Users', 'authenticateAdmin', $params);
				break;
			default:
				return false;
				break;
		}
	}

	/*
	 * Return list of templates
	 */
	function getTemplatesFormList($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');
		
		$params->set('full_sql', "(SELECT v1.template_id, v1.queue_id, v1.name, mt2.template_id as mt_template_id, 'n' as is_system
									FROM
									(SELECT mt.template_id, q.queue_id, q.name
									FROM mail_templates mt, queues q
									WHERE mt.is_queuebased = 'y'
									) v1 LEFT JOIN mail_templates mt2 ON (mt2.queue_id = v1.queue_id AND mt2.template_id = v1.template_id)
									)
									UNION (SELECT template_id, queue_id, null, template_id, 'y' as is_system FROM mail_templates WHERE is_system = 'y')");
		$params->set('count', 'no');
		$params->set('table', 'mail_templates');
		return $this->callService('SqlTable', 'select', $params);
	}
	
	
	/*
	 * Return list of templates
	 */
	function getTemplatesList($params) {
		$db =& $this->state->getByRef('db');
		$response =& $this->getByRef('response');

		$columns = "*";
		$from = "mail_templates";
		$where = "1";
		
		
		if($id = $params->get('queue_id')) {
			$where .= " AND queue_id = '".$db->escapeString($id)."'";
		} else {
			$where .= " AND queue_id = 'all'";
		}

		if($id = $params->get('template_id')) {
			$where .= " AND template_id = '".$db->escapeString($id)."'";
		}
		
		$params->set('columns', $columns);
		$params->set('from', $from);
		$params->set('where', $where);
		$params->set('table', 'mail_templates');
		return $this->callService('SqlTable', 'select', $params);
	}
	
    /**
     * Load Template row and store it into array 
     */
    function loadTemplate($template_id, $queue_id = 'all') {
    	static $templatesCache;
    	
    	if (empty($templatesCache)) {
    		$templatesCache = array();
    	}
    	if (isset($templatesCache[$template_id][$queue_id])) {
    		return $templatesCache[$template_id][$queue_id];
    	}
    	
    	$response =& $this->getByRef('response');
   	   	$db = $this->state->get('db'); 
        $template = false;
   		$paramsTemplate = $this->createParamsObject(); 
   		$paramsTemplate->set('queue_id', $queue_id);
   		$paramsTemplate->set('template_id', $template_id);
   		if ($ret = $this->callService('MailTemplates', 'getTemplatesList', $paramsTemplate)) {
   			$res = & $response->getByRef('result');
   			if ($res['count'] > 0) {
				$template = $res['rs']->getRows($res['md']);
   				$template = $template[0];
   			}
   		}
   		
   		if (!$template && $queue_id != 'all') {
	   		$paramsTemplate->set('queue_id', 'all');
   			$paramsTemplate->set('template_id', $template_id);
	   		if ($ret = $this->callService('MailTemplates', 'getTemplatesList', $paramsTemplate)) {
	   			$res = & $response->getByRef('result');
	   			if ($res['count'] > 0) {
					$template = $res['rs']->getRows($res['md']);
	   				$template = $template[0];
	   			}
	   		}
   		}
   		
   		//set cache entry
   		if ($template) {
   			$templatesCache[$template_id][$queue_id] = $template;
   		}
   		
   		if (!$template) {
   			$template = array();
   			$template['subject'] = '${subject}';
   			$template['body_html'] = '${body}';
   			$template['body_text'] = '${body}';
   		}
   		
    	return $template;
    }
	
	
	/**
	 * Create mail template
	 */
	function insertTemplate($params) {
		$response =& $this->getByRef('response');
		$db = $this->state->get('db');
		$session = QUnit::newObj('QUnit_Session');
   		
		$params->set('table', 'mail_templates');
		
		if (!strlen($params->getField('queue_id'))) {
			$params->setField('queue_id', 'all');
		}
		if (!$params->getField('is_system')) {
			$params->setField('is_system', 'n');
		}
		if (!$params->getField('is_queuebased')) {
			$params->setField('is_queuebased', 'n');
		}
		
		return $this->callService('SqlTable', 'insert', $params);
	}
	
 	/**
     *  delete template
     *
     *  @return boolean
     */
    function deleteTemplate($params) {
    	$response =& $this->getByRef('response');
    	$db = $this->state->get('db');
    	
    	if(!$params->check(array('template_id', 'queue_id'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}

    	$ids = explode('|',$params->get('template_id'));
    	$qids = explode('|',$params->get('queue_id'));
    	
    	$where_ids = '';
    	foreach ($ids as $idx => $id) {
    		if (strlen(trim($id))) {
    			$where_ids .= (strlen($where_ids) ? ' OR ': '');
    			$where_ids .= "(template_id = '" . $db->escapeString(trim($id)) . "' AND queue_id='" . $db->escapeString(trim($qids[$idx])) . "')";
    		}
    	}
    	
    	$params->set('table', 'mail_templates');
    	$params->set('where', "is_system ='n' AND ($where_ids)");
    	return $this->callService('SqlTable', 'delete', $params);
    }

    /**
     * Update Template
     *
     * @param QUnit_Rpc_Params $params
     * @return unknown
     */
    function updateTemplate($params) {
    	$response =& $this->getByRef('response');
    	$db =& $this->state->getByRef('db');
    	
    	if(!$params->check(array('template_id', 'queue_id'))) {
    		$response->set('error', $this->state->lang->get('noIdProvided'));
    		return false;
    	}
    	
    	$params->set('table', 'mail_templates');
    	$params->set('where', "template_id = '" . 
    		$db->escapeString($params->get('template_id')) . "' AND queue_id='" . 
    		$db->escapeString($params->get('queue_id')) . "'");
    	return $this->callService('SqlTable', 'update', $params);
    }
		
}
?>