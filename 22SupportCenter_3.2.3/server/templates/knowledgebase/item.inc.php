			<div class="kb_content">
				<div class="kb_pad_top"><div class="kb_pad_top_in">&nbsp;</div></div>
					<div class="kb_pad">
						<h1><?php echo $item['subject']; ?></h1>
						<div class="p_content"><?php echo $item['body'];?></div>
						<?php
							if ($item['attachments_count'] > 0) {
						?>
						<div class="attachments">
	
							<?php
							
							foreach($item['attachments'] as $attachment ) {
								echo '<a href="' . 
								$params['applicationURL'] . 'server/download.php?attachment_id=' . $attachment['file_id'] . 
								'"><img src="' . $params['knowledgeBaseURL'] . 'img/attachment.gif" class="img_attachment" />' . 
								$attachment['filename'] . ' (' . intval($attachment['filesize']/1024) . 'KB)</a><br/>';
							}
							
							?>
						</div>
					<?php					
						}
					?>
					<?php
					if (count($item['children']) >0) {
					?>
						<div class="topcat">
							<div class="topcat_in">
								<div class="width_keeper_topcat">&nbsp;</div>
								<?php
										echo '<hr /><div class="cat_ul">';
										foreach ($item['children'] as $id => $child) {
											echo '<div class="cat_li"><a href="' . 
											dirname($item['fileurl']) . '/' . $item['url'] . '/' . $child['url'] . $params['fileExtension'] . 
											'">' . $child['subject'] . 
												($child['children_count'] == 0 ? 
													'' : 
													' (' . $child['children_count'] . ')') . 
											'</a></div>';
											
											//add second column in case contains more childs
											if (count($item['children']) > 3 && $id == intval((count($item['children'])-1) /2)) {
												echo '</div><div class="cat_ul">';
											}
										}
										echo '</div>';
								?>
								<div class="topcat_end">&nbsp;</div>
							</div>
						</div>
					<?php
					}
					?>
				</div>
				<div class="kb_pad_bottom"><div class="kb_pad_bottom_in">&nbsp;</div></div>
				<div class="kb_pad_separator">&nbsp;</div>		
			</div>
			<!-- kb_content END-->			

			<div class="kb_content_similar"<?php if (count($item['similar_items']) == 0) echo ' style="display:none;"' ?>>
				<div class="kb_similar_top"><div class="kb_similar_top_in">&nbsp;</div></div>
				<div class="kb_similar">
					<h1><?php echo $state->lang->get('OtherSimilarEntries');?></h1>
	
					<div class="p_content">
					<?php					
						foreach ($item['similar_items'] as $id => $similar_item) {
							
							echo "<div class='search_result'>" . 
							($id+1) . 
							". <a href='" . $params['knowledgeBaseURL'] . $similar_item['full_path'] . "'>" . 
							(strlen($similar_item['full_parent_subject']) ? $similar_item['full_parent_subject'] . ' / ' : '') . 
							'<span class="search_result_subject">' . 
							$similar_item['subject'] . 
							"</span></a></div>";
							
						}
					?>
					</div>
				</div>
				<div class="kb_similar_bottom"><div class="kb_similar_bottom_in">&nbsp;</div></div>
				<div class="kb_similar_separator">&nbsp;</div>
			</div>