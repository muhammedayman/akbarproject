<?php
/**
 *   Handler class for WorkReporting
 *
 *   @author Viktor Zeman
 *   @copyright Copyright (c) Quality Unit s.r.o.
 *   @package SupportCenter
 */

QUnit::includeClass("QUnit_Rpc_Service");

define('RSS_ENCODING', 'UTF-8');

class App_Service_RssTickets extends QUnit_Rpc_Service {
    
    function initMethod($params) {
        $method = $params->get('method');

        switch($method) {
            default:
                return true;
                break;
        }
    }

    function authenticate($params) {
        return $this->callService('Users', 'hashLogin', $params);
    }

    function getRss($params) {
        $session = QUnit::newObj('QUnit_Session');
        if ($user = $this->authenticate($params)) {
            if ($filter = $this->getFilter($params)) {
                if ($filter['user_id'] == $session->getVar('userId') || $filter['is_global'] == 'y') {
                    $this->printHeaders($params, $filter);
                     
                    $this->printItems($filter, $params);
                     
                    $this->printFooter();
                    return true;
                }
            } else {
                echo "Failed to load filter";
                return false;
            }
        }
        echo "Authentication failed.";
        return false;
    }

    function getFilter($params) {
        $response =& $this->getByRef('response');
        $filtersObj = QUnit::newObj('App_Service_Filters');
        $filtersObj->state = $this->state;
        $filtersObj->response = $response;
        return $filtersObj->loadFilter($params);
    }

    function printHeaders($params, $filter) {
        $now = htmlentities(date("D, d M Y H:i:s O"));
        header("Content-Type: application/rss+xml");
        echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>
            <rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">
                <channel>
                    <title>SupportCenter - " . $filter['filter_name'] . "</title>
                    <description>" . $filter['filter_name'] . "</description>
                    <language>en-us</language>
                    <pubDate>$now</pubDate>
                    <lastBuildDate>$now</lastBuildDate>
                    <docs>" . $this->state->config->get('applicationURL') . "</docs>
                    <atom:link href=\"" . htmlentities($this->state->config->get('applicationURL') . 'server/rss.php?fid=' . $filter['filter_id'] . '&hash=' . $params->get('hash')) . "\" rel=\"self\" type=\"application/rss+xml\" />
                    <link>" . htmlentities($this->state->config->get('applicationURL') . 'server/rss.php?fid=' . $filter['filter_id'] . '&hash=' . $params->get('hash')) . "</link>
                    ";
    }

    function printItems($filter, $params) {
        $response =& $this->getByRef('response');

        $objStatuses = QUnit::newObj('App_Service_Statuses');
        $objStatuses->response = $response;
        $objStatuses->state = $this->state;
        $arrStatuses = $objStatuses->getStatusesArray();

        $objPriorities = QUnit::newObj('App_Service_Priorities');
        $objPriorities->response = $response;
        $objPriorities->state = $this->state;
        $arrPriorities = $objPriorities->getPrioritiesArray();
        
        $json = QUnit::newObj('QUnit_DataExchangeHandler_Json');
        $filterParams = QUnit::newObj('QUnit_Rpc_Params', $json->decode($filter['filter_value']));
        
        if ($filterParams->get('height')) {
            $filterParams->set('limit', $filterParams->get('height'));
        } else {
            $filterParams->set('limit', 10);
        }
        if ($filterParams->get('sort_order')) {
            $filterParams->set('orderDirection', $filterParams->get('sort_order'));
        }
        if ($filterParams->get('sort_column')) {
            $filterParams->set('order', $filterParams->get('sort_column'));
        }
        
        $filterParams->set('offset', 0);
        
        if ($this->callService('Tickets', 'getTicketsList', $filterParams)) {
            $res = & $response->getByRef('result');
            if ($res['count'] > 0) {
                
                
                $tickets = $res['rs']->getRows($res['md']);
                foreach ($tickets as $ticket) {
                    echo "<item>
                    		<title>".$ticket['t_subject_ticket_id'] . ' - ' . str_replace('&', '&amp;',$ticket['t_first_subject']) . ' (' . $ticket['mails_count'] .')'."</title>
                    		<guid isPermaLink=\"false\">".md5($ticket['t_subject_ticket_id'] . ' - ' . $ticket['t_last_update'])."</guid>
                    		<link>".htmlentities($this->state->config->get('applicationURL') . 'client/index.php?tid=' . $ticket['t_subject_ticket_id'] . '&hash=' . $params->get('hash'))."</link>
			                <description>".(
                                $this->state->lang->get('Queue') . ': <b>' . $ticket['queue_name'] . '</b><br/>' .
                                $this->state->lang->get('Customer') . ': <b>' . $ticket['customer_name'] . ' ' . $ticket['customer_email'] . '</b><br/>' .
                                $this->state->lang->get('Agent') . ': <b>' . $ticket['agent_name'] . '</b><br/>' .
                                $this->state->lang->get('Status') . ': <b>' . $arrStatuses[$ticket['t_status']] . '</b><br/>' .
                                $this->state->lang->get('Priority') . ': <b>' . $arrPriorities[$ticket['t_priority']] . '</b><br/>' 
                                )."</description>
                          </item>\n";
                }
            }
        }
    }

    function printFooter() {
        echo "</channel></rss>";
    }
}
?>
