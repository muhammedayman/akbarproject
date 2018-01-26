<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
$paramsSearch = $oService->createParamsObject();
$paramsSearch->set('limit', 50000);
if ($oService->callService('KBItems', 'siteMapItems', $paramsSearch)) {
	if ($result = $response->get('result')) {
		if (count($result['rs']->rows)>0){
			foreach ($result['rs']->rows as $id => $row) {
				echo "<url><loc>" . 
				$state->config->get('knowledgeBaseURL') . $row[5] . 
				"</loc><lastmod>" . date('c', strtotime($state->config->get('lastKBUpdate'))) . "</lastmod>" . 
                "<changefreq>weekly</changefreq></url>";
			}
		}
	}
}
?></urlset>