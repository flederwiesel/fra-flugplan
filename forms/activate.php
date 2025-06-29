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

  - activate -----

  (error|message|...)

  (token          [ token ])
  (username       [ user ])

          [ sumbit ]
 *
 ******************************************************************************/

?>
<form class="stretched" method="post" action="?req=activate"
	onsubmit="document.getElementById('submit').disabled=true;">
	<fieldset>
		<legend><?php echo $STRINGS['activation']; ?></legend>
<?php if ($error) { ?>
		<div id="notification" class="error">
			<?php echo $error; ?>
		</div>
<?php } else if ($message) { ?>
		<div id="notification" class="success">
			<?php echo $message; ?>
		</div>
<?php } ?>
		<div class="explainatory">
			<p><?php echo $STRINGS['snailmail_1']; ?></p>
			<p><?php echo $STRINGS['snailmail_2']; ?></p>
			<p><?php echo $STRINGS['snailmail_3']; ?>
				<a href="content/emil.php?subject=<?php echo urlencode($STRINGS["activation-trouble"]); ?>">
					<img class="emil" alt="email" src="content/mkpng.php?font=verdana&size=10&bg=white&fg=%2300007f&res=ADMIN_EMAIL">
				</a>
			</p>
		</div>
		<div class="table">
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
		</div>
	</fieldset>
	<div class="center">
		<input type="submit" id="submit" value="<?php echo $STRINGS['submit']; ?>">
	</div>
</form>
