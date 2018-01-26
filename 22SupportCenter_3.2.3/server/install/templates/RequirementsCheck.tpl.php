<div id="batchWizardStart" class="form_wrapper_outer" >
  <form id="wizard" name="wizardForm" action="?" method="Post">
    <input type="hidden" style="display:none;" name="mdl" value="<?php echo $this->target?>"/>
    <input type="hidden" style="display:none;" name="step" value="<?php echo $this->name?>"/>
    <input type="hidden" style="display:none;" name="script" value="<?php echo $this->script?>"/>
    <fieldset>
    <legend>Requirements Check</legend>



    <p>mbstring extension: <?php echo (($this->mbstringInstalled) ? "OK" : "<b>Please activate mbstring extension in your php</b>")?>
    </p>
    <p>iconv extension: <?php echo (($this->iconvInstalled) ? "OK" : "<b>Please activate iconv extension in your php</b>")?>
    </p>

<?php if(!$this->sessionWork) {?>
<p>php session DOESNT WORK. Please enable php session support</p>
<?php }?>

    <p>PHP version: <?php echo $this->phpVersion?> <?php echo (($this->phpVersionCheck) ? "OK" : "FALSE")?></p>
    <p>MySql Support: <?php echo ($this->mysqlSupport ? "OK" : "<b>MySQL extension Not installed in php</b>")?></p>
    <p>
	    Please note, that you may have to adjust following parameters in php.ini, if you want to run Support Center without restrictions: 
    
    	<dl>
	    <dt>post_max_size = <?php echo $this->post_max_size?></dt><dd>should be set to largest size of mail (attachment) you want to be able to send, e.g. 20 MB</dd>
		<dt>upload_max_filesize = <?php echo $this->upload_max_filesize?></dt><dd>set it to same as post_max_size</dd>
		<dt>max_execution_time = <?php echo $this->max_execution_time?></dt><dd>set this to larger value than default 30 seconds, 180 and more should be enough</dd>
		<dt>max_input_time = <?php echo $this->max_input_time?></dt><dd>set it to same as max_execution_time</dd>
		<dt>memory_limit <?php if (strlen(ini_get('memory_limit'))) echo "= " . ini_get('memory_limit'); ?></dt><dd>should be set to twice as much as post_max_size, in other words 2*size of mails you want to be able to send</dd>
		</dl>
		
    </p>
    </fieldset>
    <p><input class="button" type="submit" name="submit" value="Next"/></p>
  </form>
</div>

