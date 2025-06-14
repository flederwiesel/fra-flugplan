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
<script type="text/javascript">
$(function()
{
	$('#date-from').datepicker({
		dateFormat: 'dd.mm.yy',
		firstDay: 1,
		minDate: new Date(2012, 6 - 1, 6),
		maxDate: 0,
		changeMonth: true,
		changeYear: true
	});

	$('#date-until').datepicker({
		dateFormat: 'dd.mm.yy',
		firstDay: 1,
		minDate: new Date(2012, 6 - 1, 6),
		maxDate: '0',
		changeMonth: true,
		changeYear: true,
		onSelect: function(selectedDate) { $('#from').datepicker('option', 'maxDate', selectedDate);  }
	});

	$('#form').submit(function() {
		$('#submit').attr('disabled', 'disabled');
	});
});
</script>
<form method="post" action="content/getfile.php?session=<?php echo session_id(); ?>">
	<fieldset>
		<legend><?php echo $STRINGS['dlflights']; ?></legend>
<?php if (isset($error)) { ?>
		<div id="notification" class="error"><?php echo $error; ?></div>
<?php } else if (isset($notice)) { ?>
		<div id="notification" class="notice"><?php echo $notice; ?></div>
<?php } else if (isset($message)) { ?>
		<div id="notification" class="success"><?php echo $message; ?></div>
<?php } ?>

		<div class="table">
			<div class="row">
				<div class="cell"></div>
				<div class="cell">
					<label><input type="radio" name="direction" value="arrival" <?php if (!('departure' == $dir)) echo ' checked="checked" '; ?>/><?php echo $STRINGS['arrival']; ?></label>
					<label><input type="radio" name="direction" value="departure" <?php if ('departure' == $dir) echo ' checked="checked" '; ?>/><?php echo $STRINGS['departure']; ?></label>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $STRINGS['from']; ?></div>
				<div class="cell">
					<input type="text" name="date-from" id="date-from"
					 value="<?php Input_SetValue('date-from', INP_FORCE, date('d.m.Y', strtotime('-1 day'))); ?>"/>
				</div>
				<div style="display: inline;">
					<input type="text" name="time-from" id="time-from" style="margin-right: 0.5em;"
						value="<?php Input_SetValue('time-from', INP_FORCE, '00:00'); ?>"/>HH:MM (<?php echo $STRINGS['local']; ?>)
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $STRINGS['until']; ?></div>
				<div class="cell">
					<input type="text" name="date-until" id="date-until"
					 value="<?php Input_SetValue('date-until', INP_FORCE, date('d.m.Y')); ?>"/>
				</div>
				<div style="display: inline;">
					<input type="text" name="time-until" id="time-until" style="margin-right: 0.5em;"
						value="<?php Input_SetValue('time-until', INP_FORCE, '00:00'); ?>"/>HH:MM (<?php echo $STRINGS['local']; ?>)
				</div>
			</div>
		</div>
	</fieldset>
	<div class="center"><input id="submit" type="submit" name="submit"/></div>
</form>
