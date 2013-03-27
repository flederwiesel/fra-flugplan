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

<form id="form" method="post" action="?req=changepw">
	<fieldset>
		<legend><?php echo $lang['changepasswd']; ?></legend>
<?php if ($error) { ?>
		<div id="notification" class="auth-error">
			<?php echo $error; ?>
		</div>
<?php } else if ($message) { ?>
		<div id="notification" class="auth-ok">
			<?php echo $message; ?>
		</div>
<?php }?>
		<div class="table">
<?php if (!$user) { ?>
			<div class="row">
				<div class="cell label"><?php echo $lang['token']; ?></div>
				<div class="cell">
					<input type="text" id="token" name="token"
					 value="<?php if (isset($_GET['token'])) echo $_GET['token']; ?>">
					<div class="hint"><?php echo $lang['tokenemail']; ?></div>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $lang['username']; ?></div>
				<div class="cell">
					<input type="text" id="user" name="user"
					 value="<?php if (isset($_GET['token'])) echo $_GET['user']; else { if (DEBUG) echo 'flederwiesel'; } ?>">
					<div class="hint"></div>
				</div>
			</div>
<?php } ?>
			<div class="row">
				<div class="cell label"><?php echo $lang['newpassword']; ?></div>
				<div class="cell">
					<input type="password" id="passwd" name="passwd"
						<?php if (DEBUG) { ?>value="elvizzz"<?php } ?>>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $lang['confirmpassword']; ?></div>
				<div class="cell">
					<input type="password" id="passwd-confirm" name="passwd-confirm"
						<?php if (DEBUG) { ?>value="elvizzz"<?php } ?>>
					<div class="hint"><?php echo $lang['hintpassword']; ?></div>
				</div>
			</div>
		</div>
	</fieldset>
	<div class="center">
		<input type="submit" id="submit" value="<?php echo $lang['submit']; ?>">
	</div>
</form>
