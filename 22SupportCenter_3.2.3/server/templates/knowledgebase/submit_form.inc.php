			<div class="kb_content">
				<div class="kb_pad_top"><div class="kb_pad_top_in"><div class="width_keeper_top">&nbsp;</div></div></div>
					<div class="kb_pad_submit">
					<h1>SupportCenter - <?php echo $state->lang->get('SubmitNewTicket');?></h1>
					<div class="p_content_submit">
					<div class="width_keeper_topcat">&nbsp;</div>
					<?php
			
			//*************************** START: HOW WE HANDLE RESPONSE ********************************
			
			//handle error messages in case of failed request
				if (strlen($_REQUEST['error'])) {
					echo '<span style="font-weight: bolder; color: red;">Failed to submit your request: ' . $_REQUEST['error'] . '</span><br/>';
				}
			
			//handle correctly submitted ticket message
				if (strlen($_REQUEST['message'])) {
					echo '<span style="font-weight: bolder; color: blue;">' . $_REQUEST['message'] . '</span><br/>';
				}
			
			//*************************** END: HOW WE HANDLE RESPONSE **********************************

			
			?>
			
							<FORM action="<?php echo $params['applicationURL']; ?>server/submit_ticket.php" name="SubmitNewTicket" method="post" enctype="multipart/form-data">
								<INPUT type="hidden" name="success_url" value="referer">				
								<INPUT type="hidden" name="failed_url" value="referer">
								<input type="hidden" name="applicationURL" value="<?php echo $params['applicationURL']; ?>">
								<TABLE style="width: 100%">
									<TR>
										<TD style="vertical-align: top;"><?php echo $state->lang->get('name'); ?>:
										</TD>
										<TD>							
											<INPUT type="text" name="name" style="width: 100%;">
										</TD>
									</TR>
									<TR>
										<TD style="vertical-align: top; white-space: nowrap;"><?php echo $state->lang->get('email'); ?>:
										</TD>
										<TD>							
											<INPUT type="text" name="email" style="width: 100%;">
										</TD>
									</TR>
									<TR>
										<TD style="vertical-align: top; white-space: nowrap;"><?php echo $state->lang->get('queue'); ?>:
										</TD>
										<TD>
											<SELECT name="queue_id" style="width: 100%;">
												<OPTION value="" selected="selected"></OPTION>
											<?php
												$paramsQueue = $oService->createParamsObject();
												$paramsQueue->set('order', 'is_default DESC,name');
												if ($oService->callService('Queues', 'getQueueList', $paramsQueue)) {
													$result = $response->get('result');
													foreach ($result['rs']->rows as $row) {
														echo '<option value ="' . $row[0] . '">' . $row[1] . '</option>';
													}
												}
											?>
											</SELECT>
										</TD>
									</TR>
									<TR>
										<TD style="vertical-align: top; white-space: nowrap;"><?php echo $state->lang->get('subject'); ?>:
										</TD>
										<TD>							
											<INPUT type="text" name="subject" id="subject" style="width: 100%;">
										</TD>
									</TR>
									<TR>
										<TD style="vertical-align: top; white-space: nowrap;"><?php echo $state->lang->get('description'); ?>:
										</TD>
										<TD style="width: 100%;">
											<TEXTAREA name="body" id="body" rows="15" style="width: 100%;"></TEXTAREA>
										</TD>
									</TR>
									<TR>
										<TD style="vertical-align: top; white-space: nowrap;"><?php echo $state->lang->get('attachment'); ?>:
										</TD>
										<TD style="width: 100%;">
											<input type="file" name="attachment" size="50">
										</TD>
									</TR>
								</TABLE>
								<br/>	
								
								<div id="suggestionsBlock" style="display: none;" class="suggestions_block">
									<div class="suggestions_title"><?php echo $state->lang->get('KnowledgebaseSuggestions'); ?></div>
									<div id="suggestionsContainer"></div>
								</div>
								<?php if (strlen($publicKey)) {echo recaptcha_get_html($publicKey);} ?>		
								<INPUT type="submit" name="Submit" value="<?php echo $state->lang->get('send'); ?>" class="button">
							</FORM>	

						<div class="topcat_end">&nbsp;</div>
					</div>
				</div>
				<div class="kb_pad_bottom"><div class="kb_pad_bottom_in"><div class="width_keeper_top">&nbsp;</div></div></div>
				<div class="kb_pad_separator">&nbsp;</div>		
			</div>
			<script type="text/javascript">
				startListeners();
			</script>
			