<div id="batchWizardStart" class="form_wrapper_outer" >
  <div id="errorMessages"><?php echo $this->get('errorMessage') ?></div>
  <h4></h4>
  <p>Please fill out the database configuration details requested below.
    Note that the database you install into should already exist.</p>

  <h4>Requirements needed to complete this step</h4>
  <ol>
  <li>The database must already exist
    <p>You can to create the database and database user by starting MySql prompt or some MySql
    administration tool by executing the commands below:</p>

    <code>
    CREATE DATABASE database_name;<br>
    GRANT ALL PRIVILEGES ON database_name.* TO database_user@localhost IDENTIFIED BY "database_password" WITH GRANT OPTION;
    </code>

    <p>Customize it to match your database_name, database_user, and database_password.</p>
   </li>

    <li>Installer needs to have write enabled access to directory server/settings

    <p>On unix/linux systems you can grant access to it as follows: </p>

    <code>
    chmod 777 server/settings
    </code>

    <p>After installation you can change it back to more safe permissions (chmod 755).</p>

    <p>This step is not necessary on Windows systems.</p>

    </li>
  </ol>
  <form id="wizard" name="wizardForm" action="?" method="Post">
    <input type="hidden" style="display:none;" name="mdl" value="<?php echo $this->target?>" />
    <input type="hidden" style="display:none;" name="step" value="<?php echo $this->name?>" />
    <input type="hidden" style="display:none;" name="script" value="<?php echo $this->script?>"/>
    <fieldset id="Database">
        <legend>Database Configuration</legend>
        <label for="Host">Database Server Hostname:</label>
        <input type="text" id="Host" name="Host" value="<?php echo strlen($_REQUEST['Host']) ? $_REQUEST['Host'] : "localhost"?>"/><br/>
        <label for="Database">Your Database Name:</label>
        <input type="text" id="Database" name="Database" value="<?php echo $_REQUEST['Database']?>"/><br/>
        <label for="Database Username">Username:</label>
        <input type="text" id="User" name="User" value="<?php echo $_REQUEST['User']?>"/><br/>
        <label for="Password">Database Password:</label>
        <input type="password" id="Password" name="Password" value="<?php echo $_REQUEST['Password']?>"/><br/>
    </fieldset>
    <p>
        <input class="button" type="submit" name="submit" value="Next" />
    </p>
  </form>
</div>

