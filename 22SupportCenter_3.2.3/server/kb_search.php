<?php 
	include('include.php');
  	$params->set('query', $_REQUEST['query']);
	$params->set('limit', 10);
  	$kbItems = QUnit::newObj('App_Service_KBItems');
	$kbItems->state = $state;
	$kbItems->response = $response;
	if ($kbItems->searchItems($params)) {
		$res = $kbItems->response->get('result');
		$rows = $res['rs']->getRows($res['md']);
		foreach ($rows as $id => $row) {

			$title = "<span class='suggestion_item_title'>" . $row['subject'] . "</span>";
			if (strlen($row['full_parent_subject'])) {
				$title = $row['full_parent_subject'] . ' / ' . $title;
			}
			
			echo "<div class='suggestion_row'><a class='suggestion_url' href='" . 
				$config->get('knowledgeBaseURL') . $row["full_path"] . 
				"' target='_blank'>$title</a></div>";			
		}
	} else {
		echo $response->get('error');
	}
?>