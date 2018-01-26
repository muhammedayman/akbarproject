			<div class="kb_content">
				<div class="kb_pad_top"><div class="kb_pad_top_in">&nbsp;</div></div>
					<div class="kb_pad_submit">
					<h1><?php echo $state->lang->get('SearchResults'); ?></h1>
					<div class="p_content_submit">
							<?php
								$paramsSearch = $oService->createParamsObject();
								$paramsSearch->set('query', $_REQUEST['query']);
								$paramsSearch->set('limit', 50);
								if ($oService->callService('KBItems', 'searchItems', $paramsSearch)) {
									if ($result = $response->get('result')) {
										if (count($result['rs']->rows)>0){
											foreach ($result['rs']->rows as $id => $row) {
												echo "<div class='search_result'>" . 
												($id+1) . 
												". <a href='" . $params['knowledgeBaseURL'] . $row[6] . "'>" . 
												(strlen($row[7]) ? $row[7] . ' / ' : '') . 
												'<span class="search_result_subject">' . 
												$row[2] . "</span></a></div>";
											}
										} else {
											echo "No results found.";
										}
									}
								} else {
									echo $response->get('error');
								}
							?>
					</div>
				</div>
				<div class="kb_pad_bottom"><div class="kb_pad_bottom_in">&nbsp;</div></div>
				<div class="kb_pad_separator">&nbsp;</div>		
			</div>