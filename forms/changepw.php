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

  - changepasswd -----

  (error|message|...)

  (token          [ token ])
  (username       [ user ])
  newpassword     [ passwd ]
  confirmpassword [ passwd-confirm ]

          [ sumbit ]
 *
 ******************************************************************************/

?>
<form class="stretched" method="post" action="?req=changepw"
	onsubmit="document.getElementById('submit').disabled=true;">
	<fieldset>
		<legend><?php echo $STRINGS['changepasswd']; ?></legend>
<?php
		if (isset($_POST['submit']))
		{
			if ($_POST['submit'] != 'changepw')
			{
				$error = null;
				$message = null;
			}
		}

		if ($error)
		{
?>
		<div id="notification" class="error">
			<?php echo $error; ?>
		</div>
<?php
		}

		if ($message)
		{
?>
		<div id="notification" class="success">
			<?php echo $message; ?>
		</div>
<?php
		}
?>
		<div class="table">
<?php if (!$user) { ?>
			<div class="row">
				<div class="cell label"><?php echo ucfirst($STRINGS['username']); ?></div>
				<div class="cell">
					<input type="text" id="user" name="user"
					 value="<?php Input_SetValue('user', INP_POST | INP_GET, 'flederwiesel'); ?>" autofocus>
					<div class="hint"></div>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $STRINGS['token']; ?></div>
				<div class="cell">
					<input type="text" id="token" name="token"
					 value="<?php Input_SetValue('token', INP_GET, ''); ?>">
					<div class="hint"><?php echo $STRINGS['tokenemail']; ?></div>
				</div>
			</div>
<?php } ?>
			<div class="row">
				<div class="cell label"><?php echo $STRINGS['newpassword']; ?></div>
				<div class="cell">
					<input type="password" id="passwd" name="passwd"
					 value="<?php Input_SetValue('passwd', 0, 'elvizzz'); ?>">
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $STRINGS['confirmpassword']; ?></div>
				<div class="cell">
					<input type="password" id="passwd-confirm" name="passwd-confirm"
					 value="<?php Input_SetValue('passwd-confirm', 0, 'elvizzz'); ?>">
					<div class="hint"><?php echo PasswordHint(); ?></div>
				</div>
			</div>
		</div>
	</fieldset>
	<input type="hidden" name="CSRFToken" value="<?php echo CsrfToken::get(); ?>">
	<div class="center">
		<input type="hidden" name="submit" value="changepw">
		<input type="submit" id="submit" value="<?php echo $STRINGS['submit']; ?>">
	</div>
</form>
