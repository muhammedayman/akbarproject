<?php
$cL = 0;
?>
<div id="batchWizardFinish" class="form_wrapper_outer" >
  <form id="wizard" name="wizardForm" action="?" method="Post">
    <input type="hidden" style="display:none;" name="mdl" value="<?php echo $this->
    target?>">
    <input type="hidden" style="display:none;" name="step" value="<?php echo $this->
    name?>">
    <input type="hidden" style="display:none;" name="script" value="<?php echo $this->script?>"/>
    <p>Congratulations, you succesfully finished installation of Support Center. 
    Now you can log in and review your other settings, create mail accounts, 
    qeues, parsing rules and users.</p>
    <h4 style="color: red;">There is one more action to finish the installation:</h4>
    <p>Set up cron on unix/linux systems or scheduled task on windows systems to execute command below every minute. 
    This command will run job that is necessary for receiving emails:</p>
    <code>php -q <?php echo  realpath('../')?>/jobs.php</code>
    <p>The cron record will look like this:</p>
    <code>* * * * * php -q <?php echo  realpath('../')?>/jobs.php</code>
    <p>Read more how to setup <a href="http://support.qualityunit.com/knowledgebase/supportcenter/installation-upgrade/how-to-setup-cron-job-on-linux.html" target="_blank">cron on linux systems</a> or <a href="http://support.qualityunit.com/knowledgebase/supportcenter/installation-upgrade/how-to-setup-scheduled-task-on-windows.html" target="_blank">scheduled task on windows machines</a> here.</p>
    <fieldset>
    <legend>Finish</legend>
      <p>Installation was successfull. Click 'Finish' to start application.</p>
    </fieldset>
    </p>
    <p>
    <input class="button" type="submit" name="submit" value="Finish">
    </p>
  </form>

  <form method="post" action="http://www.aweber.com/scripts/addlead.pl">
	<fieldset>
    <legend>SupportCenter newsletter signup</legend>
	<input type="hidden" name="meta_web_form_id" value="1958743625">
	<input type="hidden" name="meta_split_id" value="">
	<input type="hidden" name="unit" value="supportcenter_f">
	<input type="hidden" name="redirect" value="http://<?php echo str_replace('//', '/', $_SERVER['HTTP_HOST'] . dirname(dirname(dirname($_SERVER['PHP_SELF']))) . '/') ?>">
	<input type="hidden" name="meta_redirect_onlist" value="">
	<input type="hidden" name="meta_adtracking" value="">
	<input type="hidden" name="meta_message" value="1">
	<input type="hidden" name="meta_required" value="from">
	<input type="hidden" name="meta_forward_vars" value="1">
	<p>Signup for SupportCenter newsletter.<br/>We will keep you informed about updates, new versions (we constantly add new features), tips and product discounts.<br/><b>NO SPAM</b> - we value your privacy </p>
	<table width="400">
	<tr><td>Name:</td><td><input type="text" name="name" value="" size="20"></td></tr>
	<tr><td>Email:</td><td><input type="text" name="from" value="" size="20"></td></tr>
	<tr><td align="left" colspan="2"><input class="button" type="submit" name="submit" value="Signup and Start Application"></td></tr>
	</table>
	</fieldset>
  </form>

</div>

<?php 
if ($cL != 1) {
?>
<script id="pap_x2s6df8d" src="http://install.supportcenterpro.com/scripts/sale.js" type="text/javascript"></script>
<script type="text/javascript"><!--
var TotalCost="0";
var OrderID="paid";
var ProductID="paid";
papSale();
--></script>
<?php } else { ?>
<script id="pap_x2s6df8d" src="http://install.supportcenterpro.com/scripts/sale.js" type="text/javascript"></script>
<script type="text/javascript"><!--
var TotalCost="0";
var OrderID="free";
var ProductID="free";
papSale();
--></script>
<?php } ?>
<?php
if ($this->checkl()) {
	?><img src="http://install.supportcenterpro.com/scripts/sale.php?ProductID=scslinstall" style="display: none;width: 1px; height: 1px;"/><?php
}
?>