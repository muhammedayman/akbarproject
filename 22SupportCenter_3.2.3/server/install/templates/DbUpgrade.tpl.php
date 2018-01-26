<div id="batchWizardStart" class="form_wrapper_outer" >

  <form id="wizard" name="wizardForm" action="?" method="Post">
    <input type="hidden" style="display:none;" name="mdl" value="<?php echo $this->target?>" />
    <input type="hidden" style="display:none;" name="step" value="<?php echo $this->name?>" />
    <input type="hidden" style="display:none;" name="script" value="<?php echo $this->script?>"/>
    <fieldset id="Database">
        <legend><?php echo $this->title?></legend>
  	  <?php if($this->request->get('submit')) { 
  	  		if(count($this->update_messages) >0) { 
  	  			echo '<table>';
				foreach($this->update_messages as $msg) {
					echo "<tr><td nowrap>$msg</td></tr>";
				}
				echo '</table>';
	  	  	}  	  
  	  	
  	  	?>
		  <div id="errorMessages"><?php echo $this->get('errorMessage') ?></div>
  	  <?php } else { ?>
  		<p>IMPORTANT!!! Please, backup your database before you continue with next steps!</p>        
        <p>Press 'Next' to perform upgrade.</p>
      <?php } ?>
    </fieldset>
    <p>
        <input class="button" type="submit" name="submit" value="Next" />
    </p>
  </form>
</div>

