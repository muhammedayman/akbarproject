			<div class="kb_content">
				<div class="kb_pad_top"><div class="kb_pad_top_in"><div class="width_keeper_top">&nbsp;</div></div></div>
					<div class="kb_pad_submit">
					<h1>SupportCenter - <?php echo $state->lang->get('UserRegistration');?></h1>
					<div class="p_content_submit">
					<div class="width_keeper_topcat">&nbsp;</div>
					<div style="color: red;margin-bottom: 20px;"><?php echo $params['message']; ?></div>
					
						<FORM action="<?php echo $params['applicationURL']; ?>server/register_user.php" name="RegisterUser" method="post" enctype="multipart/form-data">
							<TABLE style="width: 100%">
								<TR>
									<TD style="vertical-align: top;"><?php echo $state->lang->get('name'); ?>:
									</TD>
									<TD style="width: 100%;">							
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
									<TD style="vertical-align: top; white-space: nowrap;"><?php echo $state->lang->get('password'); ?>:
									</TD>
									<TD>							
										<INPUT type="password" name="password" id="password" style="width: 50%;">
									</TD>
								</TR>
                                <TR>
                                    <TD style="vertical-align: top; white-space: nowrap;"><?php echo $state->lang->get('passwordAgain'); ?>:
                                    </TD>
                                    <TD>                            
                                        <INPUT type="password" name="password_again" id="password_again" style="width: 50%;">
                                    </TD>
                                </TR>
							</TABLE>
							<br/>	
							
							<INPUT type="submit" name="Submit" value="<?php echo $state->lang->get('register'); ?>" class="button">
						</FORM>	

						<div class="topcat_end">&nbsp;</div>
					</div>
				</div>
				<div class="kb_pad_bottom"><div class="kb_pad_bottom_in"><div class="width_keeper_top">&nbsp;</div></div></div>
				<div class="kb_pad_separator">&nbsp;</div>		
			</div>
		