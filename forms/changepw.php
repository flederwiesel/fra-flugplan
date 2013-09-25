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
		<legend><?php echo $lang['changepasswd']; ?></legend>
<?php
		if ('changepw' == $_GET['req'])
		{
			$notification = true;
		}
		else
		{
			if (!$_POST['submit'])
			{
				$notification = false;
			}
			else
			{
				if ('changepw' == $_POST['submit'])
					$notification = true;
				else
					$notification = false;
			}
		}

		if ($notification)
		{
			if ($error)
			{
?>
		<div id="notification" class="error">
			<?php echo $error; ?>
		</div>
<?php
			}
			else if ($message)
			{
?>
		<div id="notification" class="success">
			<?php echo $message; ?>
		</div>
<?php
			}
		}
?>
		<div class="table">
<?php if (!$user) { ?>
			<div class="row">
				<div class="cell label"><?php echo $lang['username']; ?></div>
				<div class="cell">
					<input type="text" id="user" name="user"
					 value="<?php Input_SetValue('user', INP_POST | INP_GET, 'flederwiesel'); ?>" autofocus>
					<div class="hint"></div>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $lang['token']; ?></div>
				<div class="cell">
					<input type="text" id="token" name="token"
					 value="<?php Input_SetValue('token', INP_GET, ''); ?>">
					<div class="hint"><?php echo $lang['tokenemail']; ?></div>
				</div>
			</div>
<?php } ?>
			<div class="row">
				<div class="cell label"><?php echo $lang['newpassword']; ?></div>
				<div class="cell">
					<input type="password" id="passwd" name="passwd"
					 value="<?php Input_SetValue('passwd', 0, 'elvizzz'); ?>">
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $lang['confirmpassword']; ?></div>
				<div class="cell">
					<input type="password" id="passwd-confirm" name="passwd-confirm"
					 value="<?php Input_SetValue('passwd-confirm', 0, 'elvizzz'); ?>">
					<div class="hint"><?php echo $lang['hintpassword']; ?></div>
				</div>
			</div>
		</div>
	</fieldset>
	<div class="center">
		<input type="hidden" name="submit" value="changepw">
		<input type="submit" id="submit" value="<?php echo $lang['submit']; ?>">
	</div>
</form>
