<div id="batchWizardStart" class="form_wrapper_outer" >
  <div id="errorMessages"><?php echo  $this->get('errorMessage') ?></div>
  <h4></h4>
  <p>Please fill out the config settings requested below.</p>

  <h4>Requirements needed to complete this step</h4>
  <ol>
	  <li>Temporary directory should already exist
	    <p>The temporary directory will be used by Support Center for storing temporary files during the receiving of emails. Immediatelly after processing, each file will be deleted automatically.</p>
	  </li>
	
	  <li>
	  	Support Center needs to have write enabled access to temporary directory you selected
	  </li>
  </ol>
  <form id="wizard" name="wizardForm" action="?" method="Post">
    <input type="hidden" style="display:none;" name="mdl" value="<?php echo $this->target?>" />
    <input type="hidden" style="display:none;" name="step" value="<?php echo $this->name?>" />
    <input type="hidden" style="display:none;" name="script" value="<?php echo $this->script?>"/>
    <fieldset id="Config">
        <legend>Support Center Configuration</legend>
        <label for="applicationURL">Application Url:</label>
        <input type="text" size="100" id="applicationURL"
	name="applicationURL"
	value="http://<?php echo str_replace('//', '/', $_SERVER['HTTP_HOST'] . dirname(dirname(dirname($_SERVER['PHP_SELF']))) . '/') ?>" /><br/>

		<label for="tmpPath">Temporary Directory:</label>
		<input type="text" size="100" id="tmpPath" name="tmpPath" value="<?php
		if (strlen($_ENV['TEMP'])) {
			echo str_replace('//', '/', str_replace('\\', '/', $_ENV['TEMP'] . '/'));
		} else if (strlen($_ENV['TMP'])) {
			echo str_replace('//', '/', str_replace('\\', '/', $_ENV['TMP'] . '/'));
		} else {
			echo '/tmp/';
		}
	?>" /><br />
	</fieldset>
    <p>
        <input class="button" type="submit" name="submit" value="Next" />
    </p>
  </form>
</div>

