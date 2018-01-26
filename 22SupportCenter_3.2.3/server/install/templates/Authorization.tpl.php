<div id="batchWizardStart" class="form_wrapper_outer" >
  <div id="errorMessages"><?php echo $this->get('errorMessage') ?></div>
  <h4></h4>
  <p>Please fill out the Authorization requested below.</p>

  <h4>Requirements needed to complete this step</h4>
  <ol>
	  <li>You need your product ID
	    <p>Get your Product ID from our members area - Purchased
			Products section (see image bellow).
			<br /> 
			<img src="templates/product_code.png">
		</p>
	  </li>
  </ol>

  <form id="wizard" name="wizardF
  orm" action="?" method="Post">
    <input type="hidden" style="display:none;" name="mdl" value="<?php echo $this->
    target?>" />
    <input type="hidden" style="display:none;" name="step" value="<?php echo $this->
    name?>" />
    <input type="hidden" style="display:none;" name="script" value="<?php echo $this->script?>"/>
    <fieldset id="Config">
        <legend>Support Center Authorization</legend>
        <label for="productCode">Product Code:</label>
        <input type="text" size="50" id="productCode" name="productCode" value="" /><br/>
	</fieldset>
    <p>
        <input class="button" type="submit" name="submit" value="Next" />
    </p>
  </form>
</div>