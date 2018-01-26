<div id="batchWizardFinish" class="form_wrapper_outer" >
  <form id="wizard" name="wizardForm" action="?" method="Post">
    <input type="hidden" style="display:none;" name="mdl" value="<?php echo $this->target?>">
    <input type="hidden" style="display:none;" name="step" value="<?php echo $this->name?>">
    <input type="hidden" style="display:none;" name="script" value="<?php echo $this->script?>"/>
    <fieldset>
    <legend>Finish</legend>
      <p>Upgrade was successfull. Click 'Finish' to start application.</p>
    </fieldset>
    </p>
    <p>
    <input class="button" type="submit" name="submit" value="Finish">
    </p>
  </form>
</div>

<?php 
$cL = 0;
if ($cL != 1) {
?>
<script id="pap_x2s6df8d" src="http://install.supportcenterpro.com/scripts/sale.js" type="text/javascript"></script>
<script type="text/javascript"><!--
var TotalCost="0";
var OrderID="upgrade";
var ProductID="paid";
papSale();
--></script>
<?php } else { ?>
<script id="pap_x2s6df8d" src="http://install.supportcenterpro.com/scripts/sale.js" type="text/javascript"></script>
<script type="text/javascript"><!--
var TotalCost="0";
var OrderID="upgrade";
var ProductID="free";
papSale();
--></script>
<?php } ?>