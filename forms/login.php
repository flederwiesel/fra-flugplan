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

  - authentication -----

  (error|message)

  username [ user ]   notamember
  password [ passwd ] forgotpassword

  [ autologin ] rememberme

          [ sumbit ]
 *
 ******************************************************************************/

?>

<form id="form" method="post" action="?req=login"
	onsubmit="document.getElementById('submit').disabled=true;">
	<fieldset>
		<legend><?php echo $lang['authentication']; ?></legend>
<?php if ($error) { ?>
		<div id="notification" class="auth-error">
			<?php echo $error; ?>
		</div>
<?php } else if ($message) { ?>
		<div id="notification" class="auth-ok">
			<?php echo $message; ?>
		</div>
<?php } ?>
		<div class="table">
			<div class="row">
				<div class="cell label"><?php echo $lang['username']; ?></div>
				<div class="cell">
					<input type="text" id="user" name="user"
					 value="<?php Input_SetValue('user', INP_POST | INP_GET, 'flederwiesel'); ?>">
					<div class="hint">
						<a href="?req=register"><?php echo $lang['notamember']; ?></a>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $lang['password']; ?></div>
				<div class="cell">
					<input type="password" id="passwd" name="passwd"
					 value="<?php Input_SetValue('passwd', 0, 'elvizzz'); ?>">
					<div class="hint">
						<a href="?req=reqtok"><?php echo $lang['forgotpassword']; ?></a>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="cell">&nbsp;</div>
				<div class="cell">
						<label>
							<input type="checkbox" id="autologin" name="autologin" checked>
								<?php echo $lang['rememberme']; ?>
						</label>
				</div>
			</div>
		</div>
	</fieldset>
	<div class="center">
		<input type="submit" id="submit" value="<?php echo $lang['submit']; ?>">
	</div>
</form>
