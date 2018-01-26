<?php
/**
 *   Handler class for PDF Export
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */
include_once(CLASS_PATH . 'QUnit/Pdf/tcpdf.php');

QUnit::includeClass("QUnit_Rpc_Service");
class App_Service_PdfExport extends QUnit_Rpc_Service {

	var $pdf;
	
	function initMethod($params) {
		$method = $params->get('method');

		switch($method) {
			case 'exportTicket':
				return $this->callService('Users', 'authenticate', $params);
				break;
			default:
				return false;
				break;
		}
	}

	function exportTicket($ticket_id, $timeOffset = 0) {
		//load ticket
		if ($objTicket = $this->loadTicket($ticket_id)) {
			
			if ($this->checkTicketPermissions($objTicket)) {
			
				if ($objUser = $this->loadUser($objTicket['customer_id'])) {
					$this->pdfConfig();
					$this->printHeaders($objTicket);
					$this->generatePdfTicket($objTicket, $objUser, $timeOffset);
					return true;
				} else {
					echo $this->state->lang->get('failedToLoadUser');
					return false;
				}
			} else {
				echo $this->state->lang->get('permissionDenied');
				return false;
			}
		} else {
			echo $this->state->lang->get('failedToLoadTicket');
			return false;
		}
	}

	function checkTicketPermissions($ticket) {
		$session = QUnit::newObj('QUnit_Session');
		switch ($session->getVar('userType')) {
			case 'a': 
				return true;
			case 'g':
				return $ticket['agent_owner_id'] == $session->getVar('userId') || $this->queuePermissions($ticket['queue_id']);
			case 'u':
				return $ticket['customer_id'] == $session->getVar('userId');
			default:
				return false;
		}
	}
	
	function queuePermissions($queue_id) {
		$session = QUnit::newObj('QUnit_Session');
		$queue = $this->loadQueue($queue_id);
		if ($queue) {
			if ($queue['public'] == 'y') {
				return true; 
			} else {
				$response =& $this->getByRef('response');
				$params = $this->createParamsObject();
				$params->set('queue_id', $ticket_id);
				$tObj = QUnit::newObj('App_Service_Queues');
				$tObj->state = $this->state;
				$tObj->response = $response;
				if ($tObj->getQueueAgentsList($params)) {
					$result = $tObj->response->getByRef('result');
					$agents = $result['rs']->getRows($result['md']);
					foreach ($agents as $agent) {
						if ($agent['user_id'] == $session->getVar('userId')) {
							return true;
						}
					}
					
				}
			}
		}
		
		return false;
	}

	
	function loadQueue($queue_id) {
		$response =& $this->getByRef('response');
		$params = $this->createParamsObject();
		$params->set('queue_id', $queue_id);
		$tObj = QUnit::newObj('App_Service_Queues');
		$tObj->state = $this->state;
		$tObj->response = $response;
		return $tObj->loadQueue($params);
	}
	
	
	function loadTicket($ticket_id) {
		$response =& $this->getByRef('response');
		$params = $this->createParamsObject();
		$params->set('ticket_id', $ticket_id);
		$tObj = QUnit::newObj('App_Service_Tickets');
		$tObj->state = $this->state;
		$tObj->response = $response;
		return $tObj->loadTicket($params);
	}

	function loadUser($user_id) {
		$response =& $this->getByRef('response');
		$params = $this->createParamsObject();
		$params->set('user_id', $user_id);
		$tObj = QUnit::newObj('App_Service_Users');
		$tObj->state = $this->state;
		$tObj->response = $response;
		return $tObj->loadUser($params);
	}
	
	function printHeaders(&$ticket) {
   	    header("Cache-control: private");
		header('Content-type: application/pdf');
		header('Content-Disposition: attachment; filename="' . $ticket['subject_ticket_id'] . '.pdf"');
		header('Content-Description: SupportCenter Generated PDF Data');
	}

	function setLanguage() {
		$l = Array();
		$l['a_meta_charset'] = "UTF-8";
		$l['a_meta_dir'] = "ltr";
		$l['a_meta_language'] = "en";
		$l['w_page'] = $this->state->lang->get('page');
		$this->pdf->setLanguageArray($l); //set language items
	}
	
	function pdfConfig() {
		
		/**
		 * installation path
		 */
		define ("K_PATH_MAIN", CLASS_PATH . "QUnit/Pdf/");
		
		/**
		 * path for PDF fonts
		 */
		define ("FPDF_FONTPATH", K_PATH_MAIN."fonts/");
		
		/**
		 * main font name
		 */
		define ("PDF_FONT_NAME_MAIN", "FreeSerif"); //vera
		
		/**
		 * main font size
		 */
		define ("PDF_FONT_SIZE_MAIN", 10);
		
		/**
		 * data font name
		 */
		define ("PDF_FONT_NAME_DATA", "FreeSerif"); //verase
		
		/**
		 * data font size
		 */
		define ("PDF_FONT_SIZE_DATA", 8);
		
		/**
		 * height of cell repect font height
		 */
		define("K_CELL_HEIGHT_RATIO", 1.25);
		
		
		/**
		 * reduction factor for small font
		 */
		define("K_SMALL_RATIO", 2/3);
		
		
	}
	
	function generatePdfTicket(&$ticket, &$user, $timeOffset = 0) {
		//create new PDF document (document units are set by default to millimeters)
		$this->pdf = new TCPDF("P", "mm", "A4", true); 
		
		// set document information
		$this->pdf->SetCreator("SupportCenter");
		$this->pdf->SetAuthor("Quality Unit, s.r.o.");
		$this->pdf->SetTitle($ticket['subject_ticket_id']);
		$this->pdf->SetSubject($ticket['first_subject']);
		$this->pdf->SetKeywords($ticket['first_subject']);
		
		$name = strlen($user['name']) ? ($user['name'] . ' (' . $user['email'] . ')') : $user['email']; 
		
		$this->pdf->SetHeaderData("", 0, $ticket['subject_ticket_id'] . " - " . $ticket['first_subject'], $name);
		
		//set margins
		$this->pdf->SetMargins(20, 20, 20);
		//set auto page breaks
		$this->pdf->SetAutoPageBreak(TRUE, 20);
		$this->pdf->SetHeaderMargin(5);
		$this->pdf->SetFooterMargin(5);
		$this->pdf->setImageScale(4); //set image scale factor
		
		$this->pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$this->pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		
		$this->setLanguage();		
		
		//initialize document
		$this->pdf->AliasNbPages();
		
		$this->pdf->AddPage();
		
		if (!$this->generateMails($ticket, $user, $timeOffset)){
			return false;
		}
		
		//Close and output PDF document
		$this->pdf->Output();

	}
	
	function generateMails($ticket, $user, $timeOffset = 0) {
    	$txtconverter = QUnit::newObj('QUnit_Txt_Html2Text');
		$response =& $this->getByRef('response');
		$objTickets = QUnit::newObj('App_Service_Tickets');
		$objTickets->response = $response;
		$objTickets->state = $this->state;
		$params = $this->createParamsObject();
		$params->set('ticket_id', $ticket['ticket_id']);
		$params->set('body', true);
		$params->set('orderDirection', 'ASC');
		// output some HTML code
    	if ($objTickets->getMailsList($params)) {
    		$mails = $objTickets->response->get('result');
    	} else {
    		$response->set('error', $this->state->lang->get('failedToListTicketMails'));
    		return false;
    	}
    	$mails = $mails['rs']->getRows($mails['md']);
    	
    	
    	
    	foreach($mails as $id => $mail) {
    		$html = '';
    		$html .= "<i>" . strftime('%d/%m/%Y %H:%M:%S', strtotime($mail['created']) + $timeOffset*60) . "</i>: ";
    		$html .= "<b>" . $mail['subject'] . "</b><br>";
    		
			$objMails = QUnit::newObj('App_Service_Mails');
			$objMails->response = $response;
			$objMails->state = $this->state;
			$params = $this->createParamsObject();
			$params->set('mail_id', $mail['mail_id']);
			$params->set('order', 'mail_role');
    		if ($objMails->getMailUsersList($params)) {
				$mail_users = $objMails->response->get('result');
    			$mail_users = $mail_users['rs']->getRows($mail_users['md']);
				
				foreach ($mail_users as $mail_user) {
					if ($mail_user['mail_role'] == 'from_user') {
						$mail_user['mail_role'] = 'wrote';
					}
					$html .= "<b>" . $mail_user['mail_role'] . ':</b> <i>' . 
					(strlen($mail_user['name']) ? $mail_user['name'] . ' &lt;' . $mail_user['email'] . '>' : $mail_user['email']) .
					'</i><br>';
				}
				
    		}
    		
    		if (preg_match('/\<[pP]{1}\>/', $mail['body'])) {
		    	$txtconverter->set_html($mail['body']);
		    	$mail['body'] = $txtconverter->get_text();
    		}
    		$html .= "<hr>" . str_replace("\n", '<br>', str_replace('<', '&lt;', $mail['body']));
    		$this->pdf->writeHTMLCell(0, 0, 0, $this->pdf->getY(), $html, 1, 2, 0);
    		 
    		$this->pdf->writeHTML('<br>');
    		if ($id < count($mails)-1) {
    			$this->pdf->addPage();
    		}
    	}
    	return true;
	}
	
}
?>
