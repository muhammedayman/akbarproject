			<div class="kb_content">
				<div class="kb_pad_top"><div class="kb_pad_top_in"><div class="width_keeper_top">&nbsp;</div></div></div>
					<div class="kb_pad_submit">
					<h1>SupportCenter - <?php echo $state->lang->get('requestNewPassword');?></h1>
					<div class="p_content_submit">
					<div class="width_keeper_topcat">&nbsp;</div>
                    <div style="color: red;margin-bottom: 20px;"><?php echo $params['message']; ?></div>
					<span>${app.i18n.requestNewPasswordDescription}</span><br/><br/>
						<FORM action="<?php echo $params['applicationURL']; ?>server/request_password.php" name="RequestPassword" method="post" enctype="multipart/form-data">
							<TABLE style="width: 100%">
								<TR>
									<TD style="vertical-align: top; white-space: nowrap;"><?php echo $state->lang->get('email'); ?>:
									</TD>
									<TD style="width: 100%;">							
										<INPUT type="text" name="email" style="width: 100%;">
									</TD>
								</TR>
							</TABLE>
							<br/>	
							<INPUT type="submit" name="Submit" value="<?php echo $state->lang->get('requestNewPassword'); ?>" class="button">
						</FORM>	

						<div class="topcat_end">&nbsp;</div>
					</div>
				</div>
				<div class="kb_pad_bottom"><div class="kb_pad_bottom_in"><div class="width_keeper_top">&nbsp;</div></div></div>
				<div class="kb_pad_separator">&nbsp;</div>		
			</div>
		