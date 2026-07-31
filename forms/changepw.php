<?php

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
<form class="stretched" method="post" action="?req=changepw">
	<fieldset>
		<legend><?= $STRINGS['changepasswd'] ?></legend>
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
			<?= $error ?>
		</div>
<?php
		}

		if ($message)
		{
?>
		<div id="notification" class="success">
			<?= $message ?>
		</div>
<?php
		}
?>
		<div class="table">
<?php if (!$user) { ?>
			<div class="row">
				<div class="cell label"><?= ucfirst($STRINGS['username']) ?></div>
				<div class="cell">
					<input type="text" id="user" name="user"
					 value="<?= valueFromRequest('user', INP_POST | INP_GET) ?>" autofocus>
					<div class="hint"></div>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?= $STRINGS['token'] ?></div>
				<div class="cell">
					<input type="text" id="token" name="token"
					 value="<?= valueFromRequest('token', INP_GET) ?>">
					<div class="hint"><?= $STRINGS['tokenemail'] ?></div>
				</div>
			</div>
<?php } ?>
			<div class="row">
				<div class="cell label"><?= $STRINGS['newpassword'] ?></div>
				<div class="cell">
					<input type="password" id="passwd" name="passwd"
					 value="">
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?= $STRINGS['confirmpassword'] ?></div>
				<div class="cell">
					<input type="password" id="passwd-confirm" name="passwd-confirm"
					 value="">
					<div class="hint"><?= PasswordHint() ?></div>
				</div>
			</div>
		</div>
	</fieldset>
	<input type="hidden" name="CSRFToken" value="<?= CsrfToken::get() ?>">
	<div class="center">
		<input type="hidden" name="submit" value="changepw">
		<input type="submit" id="submit" value="<?= $STRINGS['submit'] ?>">
	</div>
</form>
