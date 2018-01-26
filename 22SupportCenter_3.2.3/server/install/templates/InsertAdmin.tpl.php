<div id="batchWizardStart" class="form_wrapper_outer" >
  <div id="errorMessages"><?php echo  $this->get('errorMessage') ?></div>
  <p>Specify username/email and password for administrator's account. </p>
  <p>Use these data for first login to application.</p>
  <form id="wizard" name="wizardForm" action="?" method="Post">
    <input type="hidden" style="display:none;" name="mdl" value="<?php echo $this->target?>" />
    <input type="hidden" style="display:none;" name="step" value="<?php echo $this->name?>" />
    <input type="hidden" style="display:none;" name="script" value="<?php echo $this->script?>"/>
    <fieldset id="insertAdmin">
        <legend>Insert Admin</legend>
        <label for="Name">Name:</label>
        <input type="text" id="Name" name="Name" value="<?php echo $_REQUEST['Name']?>"/><br/>
        <label for="Username">Username/Email:</label>
        <input type="text" id="Username" name="Username" value="<?php echo $_REQUEST['Username']?>"/><br/>
        <label for="Password">Password:</label>
        <input type="password" id="Password" name="Password" value="<?php echo $_REQUEST['Password']?>"/><br/>
        <label for="RePassword">Repeat Password:</label>
        <input type="password" id="RePassword" name="RePassword" value="<?php echo $_REQUEST['RePassword']?>"/><br/>
    </fieldset>
    <p>
        <input class="button" type="submit" name="submit" value="Next" />
    </p>
  </form>
</div>

