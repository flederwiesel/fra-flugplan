<?php

/******************************************************************************
 *
 * <form> layout:
 *

  - activate -----

  (error|message|...)

  (token          [ token ])
  (username       [ user ])

          [ sumbit ]
 *
 ******************************************************************************/

?>
<form class="stretched" method="post" action="?req=activate">
	<fieldset>
		<legend><?= $STRINGS['activation'] ?></legend>
<?php if ($error) { ?>
		<div id="notification" class="error">
			<?= $error ?>
		</div>
<?php } else if ($message) { ?>
		<div id="notification" class="success">
			<?= $message ?>
		</div>
<?php } ?>
		<div class="explainatory">
			<p><?= $STRINGS['snailmail_1'] ?></p>
			<p><?= $STRINGS['snailmail_2'] ?></p>
			<p><?= $STRINGS['snailmail_3'] ?>
				<a href="content/email.php?subject=<?= urlencode($STRINGS["activation-trouble"]) ?>">
					<img class="email" alt="email" src="content/mkpng.php?font=verdana&size=10&bg=white&fg=%2300007f&res=ADMIN_EMAIL">
				</a>
			</p>
		</div>
		<div class="table">
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
					 value="">
					<div class="hint"><?= $STRINGS['tokenemail'] ?></div>
				</div>
			</div>
		</div>
	</fieldset>
	<input type="hidden" name="CSRFToken" value="<?= CsrfToken::get() ?>">
	<div class="center">
		<input type="submit" id="submit" value="<?= $STRINGS['submit'] ?>">
	</div>
</form>
