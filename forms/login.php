<?php

/******************************************************************************
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
<form class="stretched" method="post" action="?req=login"
	onsubmit="document.getElementById('submit').disabled=true;">
	<fieldset>
		<legend><?php echo $STRINGS['authentication']; ?></legend>
<?php if ($error) { ?>
		<div id="notification" class="error">
			<?php echo $error; ?>
		</div>
<?php } else if ($message) { ?>
		<div id="notification" class="success">
			<?php echo $message; ?>
		</div>
<?php } ?>
		<div class="table">
			<div class="row">
				<div class="cell label"><?php echo ucfirst($STRINGS['username']); ?></div>
				<div class="cell">
					<input type="text" id="user" name="user" tabindex="1"
					 value="<?php Input_SetValue('user', INP_POST | INP_GET, 'flederwiesel'); ?>" autofocus>
					<div class="hint">
						<a href="?req=register"  tabindex="5"><?php echo $STRINGS['notamember']; ?></a>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $STRINGS['password']; ?></div>
				<div class="cell">
					<input type="password" id="passwd" name="passwd" tabindex="2"
					 value="<?php Input_SetValue('passwd', 0, 'elvizzz'); ?>">
					<div class="hint">
						<a href="?req=reqtok"  tabindex="6"><?php echo $STRINGS['forgotpassword']; ?></a>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="cell">&nbsp;</div>
				<div class="cell">
						<label>
							<input type="checkbox" id="autologin" name="autologin"  tabindex="3" checked>
								<?php echo $STRINGS['rememberme']; ?>
						</label>
				</div>
			</div>
		</div>
	</fieldset>
	<div class="center">
		<input type="submit" id="submit"  tabindex="4" value="<?php echo $STRINGS['submit']; ?>">
	</div>
</form>
