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
		<legend><?= $STRINGS['forgotpassword'] ?></legend>
<?php if ($error) { ?>
		<div id="notification" class="error">
			<?= $error ?>
		</div>
<?php } else if ($message) { ?>
		<div id="notification" class="success">
			<?= $message ?>
		</div>
<?php } else { ?>
		<div id="notification" class="explain">
			<?= $STRINGS['passwdencrypted'] ?>
		</div>
<?php } ?>
		<div class="table">
			<div class="row">
				<div class="cell label"><?= ucfirst($STRINGS['username']) ?></div>
				<div class="cell">
					<input type="text" id="user" name="user"
					 value="<?= Input_SetValue('user', INP_POST, 'uid-1') ?>" autofocus>
					<div class="hint"></div>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?= ucfirst($STRINGS['emailaddress']) ?></div>
				<div class="cell">
					<input type="text" id="email" name="email"
					 value="<?= Input_SetValue('email', INP_POST, 'etc@example.com') ?>">
					<div class="hint"><?= $STRINGS['onefieldmandatory'] ?></div>
				</div>
			</div>
		</div>
	</fieldset>
	<input type="hidden" name="CSRFToken" value="<?= CsrfToken::get() ?>">
	<div class="center">
		<input type="submit" id="submit" value="<?= $STRINGS['submit'] ?>">
	</div>
</form>
