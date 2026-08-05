<?php

$notice = null;

if (!$user)
{
	$error = $STRINGS['notloggedin'];

	session_regenerate_id();
}
else
{
	if (isset($_SESSION['error']))
	{
		$error = $_SESSION['error'];
		unset($_SESSION['error']);
	}
	else
	{
		if (isset($_SESSION['message']))
		{
			$message = $_SESSION['message'];
			unset($_SESSION['message']);
		}
	}
}

?>
<!--meta http-equiv="refresh" content="0; url=getfile.php"-->
<script type="text/javascript" nonce="<?= $nonce; ?>" src="<?= Asset::src('script/download.js') ?>" defer></script>
<form id="download" method="post" action="content/getfile.php?session=<?= session_id() ?>">
	<fieldset>
		<legend><?= $STRINGS['dlflights'] ?></legend>
<?php if (isset($error)) { ?>
		<div id="notification" class="error"><?= $error ?></div>
<?php } else if (isset($notice)) { ?>
		<div id="notification" class="notice"><?= $notice ?></div>
<?php } else if (isset($message)) { ?>
		<div id="notification" class="success"><?= $message ?></div>
<?php } ?>

		<div class="table">
			<div class="row">
				<div></div>
				<div class="cell">
					<label><input type="radio" name="direction" value="arrival" <?= $dir == 'arrival' ? ' checked="checked" ' : '' ?>/><?= $STRINGS['arrival'] ?></label>
					<label><input type="radio" name="direction" value="departure" <?= $dir == 'departure' ? ' checked="checked" ' : '' ?>/><?= $STRINGS['departure'] ?></label>
				</div>
			</div>
			<div class="row">
				<div class="label"><?= ucFirst($STRINGS['from']) ?></div>
				<div class="cell">
					<input type="text" name="date-from" id="date-from"
						placeholder="dd.mm.YYYY"/>
				</div>
				<div>
					<input type="text" name="time-from" id="time-from"
						placeholder="HH:MM"/>(<?= $STRINGS['local'] ?>)
				</div>
			</div>
			<div class="row">
				<div class="label"><?= ucFirst($STRINGS['until']) ?></div>
				<div class="cell">
					<input type="text" name="date-until" id="date-until"
						placeholder="dd.mm.YYYY"/>
				</div>
				<div>
					<input type="text" name="time-until" id="time-until"
						placeholder="HH:MM"/>(<?= $STRINGS['local'] ?>)
				</div>
			</div>
		</div>
	</fieldset>
	<input type="hidden" name="CSRFToken" value="<?= CsrfToken::get() ?>">
	<div class="center"><input id="submit" type="submit" name="submit"/></div>
</form>
