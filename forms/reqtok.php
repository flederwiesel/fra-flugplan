<?php

/******************************************************************************
 *
 * <form> layout:
 *

  - forgotpassword -----

  (error|message|passwdencrypted)

  username        [ user ]
  emailaddress    [ email ]   onefieldmandatory

          [ sumbit ]
 *
 ******************************************************************************/

?>
<form class="stretched" method="post" action="?req=reqtok"
	onsubmit="document.getElementById('submit').disabled=true;">
	<fieldset>
		<legend><?php echo $STRINGS['forgotpassword']; ?></legend>
<?php if ($error) { ?>
		<div id="notification" class="error">
			<?php echo $error; ?>
		</div>
<?php } else if ($message) { ?>
		<div id="notification" class="success">
			<?php echo $message; ?>
		</div>
<?php } else { ?>
		<div id="notification" class="explain">
			<?php echo $STRINGS['passwdencrypted']; ?>
		</div>
<?php } ?>
		<div class="table">
			<div class="row">
				<div class="cell label"><?php echo ucfirst($STRINGS['username']); ?></div>
				<div class="cell">
					<input type="text" id="user" name="user"
					 value="<?php Input_SetValue('user', INP_POST, 'flederwiesel'); ?>" autofocus>
					<div class="hint"></div>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo ucfirst($STRINGS['emailaddress']); ?></div>
				<div class="cell">
					<input type="text" id="email" name="email"
					 value="<?php Input_SetValue('email', INP_POST, 'etc@flederwiesel.com'); ?>">
					<div class="hint"><?php echo $STRINGS['onefieldmandatory']; ?></div>
				</div>
			</div>
		</div>
	</fieldset>
	<input type="hidden" name="CSRFToken" value="<?php echo CsrfToken::get(); ?>">
	<div class="center">
		<input type="submit" id="submit" value="<?php echo $STRINGS['submit']; ?>">
	</div>
</form>
