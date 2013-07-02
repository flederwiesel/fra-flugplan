<?php

/******************************************************************************
 *
 *       project: FRA-flights Live Schedule
 *
 *       $Author$
 *         $Date$
 *          $Rev$
 *
 ******************************************************************************
 *
 * Copyright © Tobias Kühne
 *
 * You may use and distribute this software free of charge for non-commercial
 * purposes. The software must be distributed in its entirety, i.e. containing
 * ALL binary and source files without modification.
 * Publication of modified versions of the source code provided herein,
 * is permitted only with the author's written consent. In this case the
 * copyright notice must not be removed or altered, all modifications to the
 * source code must be clearly marked as such.
 *
 ******************************************************************************/

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

<form id="form" method="post" action="?req=reqtok"
	onsubmit="document.getElementById('submit').disabled=true;">
	<fieldset>
		<legend><?php echo $lang['forgotpassword']; ?></legend>
<?php if ($error) { ?>
		<div id="notification" class="auth-error">
			<?php echo $error; ?>
		</div>
<?php } else if ($message) { ?>
		<div id="notification" class="auth-ok">
			<?php echo $message; ?>
		</div>
<?php } else { ?>
		<div id="notification" class="auth-hint">
			<?php echo $lang['passwdencrypted']; ?>
		</div>
<?php } ?>
		<div class="table">
			<div class="row">
				<div class="cell label"><?php echo $lang['username']; ?></div>
				<div class="cell">
					<input type="text" id="user" name="user"
					 value="<?php Input_SetValue('user', INP_POST, 'flederwiesel'); ?>">
					<div class="hint"></div>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $lang['emailaddress']; ?></div>
				<div class="cell">
					<input type="text" id="email" name="email"
					 value="<?php Input_SetValue('email', INP_POST, 'etc@flederwiesel.com'); ?>">
					<div class="hint"><?php echo $lang['onefieldmandatory']; ?></div>
				</div>
			</div>
		</div>
	</fieldset>
	<div class="center">
		<input type="submit" id="submit" value="<?php echo $lang['submit']; ?>">
	</div>
</form>
