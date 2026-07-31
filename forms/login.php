<?php

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
<form class="stretched" method="post" action="?req=login">
	<fieldset>
		<legend><?= $STRINGS['authentication'] ?></legend>
<?php if ($error) { ?>
		<div id="notification" class="error">
			<?= $error ?>
		</div>
<?php } else if ($message) { ?>
		<div id="notification" class="success">
			<?= $message ?>
		</div>
<?php } ?>
		<div class="table">
			<div class="row">
				<div class="cell label"><?= ucfirst($STRINGS['username']) ?></div>
				<div class="cell">
					<input type="text" id="user" name="user" tabindex="1"
					 value="<?= valueFromRequest('user', INP_POST | INP_GET) ?>" autofocus>
					<div class="hint">
						<a href="?req=register"  tabindex="5"><?= $STRINGS['notamember'] ?></a>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?= $STRINGS['password'] ?></div>
				<div class="cell">
					<input type="password" id="passwd" name="passwd" tabindex="2"
					 value="">
					<div class="hint">
						<a href="?req=reqtok"  tabindex="6"><?= $STRINGS['forgotpassword'] ?></a>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="cell">&nbsp;</div>
				<div class="cell">
						<label>
							<input type="checkbox" id="autologin" name="autologin"  tabindex="3" checked>
								<?= $STRINGS['rememberme'] ?>
						</label>
				</div>
			</div>
		</div>
	</fieldset>
	<input type="hidden" name="CSRFToken" value="<?= CsrfToken::get() ?>">
	<div class="center">
		<input type="submit" id="submit"  tabindex="4" value="<?= $STRINGS['submit'] ?>">
	</div>
</form>
