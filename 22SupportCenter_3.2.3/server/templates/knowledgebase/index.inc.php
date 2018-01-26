			<div class="kb_content">
					<?php
						foreach ($childs as $child) {
						?>			
					<div class="kb_pad_top"><div class="kb_pad_top_in"><div class="width_keeper_top">&nbsp;</div></div></div>
						<div class="kb_pad">
							<div class="topcat">
								<div class="topcat_in">
									<div class="width_keeper_topcat">&nbsp;</div>
									<h2><?php echo $child['subject'];?></h2>
									<div class="p_content"><?php echo $child['body'];?></div>
									<?php
									if ($child['children_count'] >0) {
										echo '<hr /><div class="cat_ul">';
										foreach ($child['children'] as $id => $subchild) {
											echo '<div class="cat_li"><a href="' . 
											$params['knowledgeBaseURL'] . $child['url'] . '/' . $subchild['url'] . $params['fileExtension'] . 
											'">' . $subchild['subject'] . 
												($subchild['children_count'] == 0 ? 
													'' : 
													' (' . $subchild['children_count'] . ')') . 
											'</a></div>';
											
											//add second column in case contains more childs
											if (count($child['children']) > 3 && $id == intval((count($child['children'])-1) /2)) {
												echo '</div><div class="cat_ul">';
											}
										}
										echo '</div><div class="topcat_end">&nbsp;</div>';
									}
									?>
								</div>
							</div>
						</div>
						<div class="kb_pad_bottom"><div class="kb_pad_bottom_in"><div class="width_keeper_top">&nbsp;</div></div></div>
						<div class="kb_pad_separator">&nbsp;</div>
						<?php			
						}
						?>					
			</div>
			<!-- kb_content END-->