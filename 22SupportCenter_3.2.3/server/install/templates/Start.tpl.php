<div id="batchWizardStart" class="form_wrapper_outer" >
  <form id="wizard" name="wizardForm" action="?" method="Post">
    <input type="hidden" style="display:none;" name="mdl" value="<?php echo $this->
    target?>">
    <input type="hidden" style="display:none;" name="step" value="<?php echo $this->
    name?>">
    <input type="hidden" style="display:none;" name="script" value="<?php echo $this->script?>"/>
    <fieldset>
    <legend>Start</legend>
      <p>Thank you for choosing Support Center. This wizard will lead you step by step through the installation of SupportCenter.</p>
    </fieldset>
    <p>
    <input class="button" type="submit" name="submit" value="Next">
    </p>
  </form>
</div>
<?php 
$cL = 0;
if ($cL != 1) {
?>
<img src='http://install.supportcenterpro.com/scripts/sb.php?a_aid=123456&amp;a_bid=6aff235d' alt=" " title=" ">
<?php } else { ?>
<img src='http://install.supportcenterpro.com/scripts/sb.php?a_aid=123456&amp;a_bid=be22264f' alt=" " title=" ">	
<?php } ?>