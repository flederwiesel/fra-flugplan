<?php

/******************************************************************************
 *
 *       project: FRA-flights Live Schedule
 *
 *       $Author: flederwiesel $
 *         $Date: 2013-08-26 21:01:54 +0200 (Mo, 26 Aug 2013) $
 *          $Rev: 413 $
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

$notice = null;

if ($_POST)
	header('Refresh: 0; url="content/getfile.php"');

?>
<html>
<head>
<link type="text/css" rel="stylesheet" href="css/jquery.ui.datepicker.css">
<script type="text/javascript" src="script/ui/jquery.ui.core.js"></script>
<script type="text/javascript" src="script/ui/jquery.ui.widget.js"></script>
<script type="text/javascript" src="script/ui/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="script/ajax.js"></script>
<script type="text/javascript">
$(function()
{
	$('#from').datepicker({
		dateFormat: 'dd.mm.yy',
		firstDay: 1,
		minDate: '-1Y',
		maxDate: 0,
		changeMonth: true,
		changeYear: true
	});

	$('#until').datepicker({
		dateFormat: 'dd.mm.yy',
		firstDay: 1,
		minDate: '-1Y',
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
</head>
<body>
<?php

if ($_POST)
{
	$message = "<div>your download should begin shortly.</div>";

	echo '<span style="color: #dddddd;">';

		for ($i = 0; $i < 50000; $i++)	// remove
		{
echo <<<EOF
Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.
EOF;
		echo "<hr>";
		ob_flush();
		flush();
		}

	echo '</span>';
}

?>
	<form id="form" method="post" action="content/getfile.php">
		<fieldset>
			<legend><?php echo $lang['addflight']; ?></legend>
<?php if (isset($error)) { ?>
			<div id="notification" class="auth-error"><?php echo $error; ?></div>
<?php } else if (isset($notice)) { ?>
			<div id="notification" class="auth-notice"><?php echo $notice; ?></div>
<?php } else if (isset($message)) { ?>
			<div id="notification" class="auth-ok"><?php echo $message; ?></div>
<?php } ?>
			<div class="table">
				<div class="row">
					<div class="cell"></div>
					<div class="cell">
						<label><input type="radio" name="direction" value="arrival" <?php if (!('departure' == $dir)) echo ' checked="checked" '; ?>/><?php echo $lang['arrival']; ?></label>
						<label><input type="radio" name="direction" value="departure" <?php if ('departure' == $dir) echo ' checked="checked" '; ?>/><?php echo $lang['departure']; ?></label>
					</div>
				</div>
				<div class="row">
					<div class="cell label">From:</div>
					<div class="cell">
						<input type="text" name="from" id="from"
						 value="<?php Input_SetValue('from', INP_ALWAYS, date('d.m.Y', strtotime('-1 day'))); ?>"/>
					</div>
				</div>
				<div class="row">
					<div class="cell label">Until:</div>
					<div class="cell">
						<input type="text" name="until" id="until"
						 value="<?php Input_SetValue('until', INP_ALWAYS, date('d.m.Y')); ?>"/>
					</div>
				</div>
			</div>
		</fieldset>
		<div class="center"><input id="submit" type="submit" name="submit"/></div>
	</form>
</body>
</html>
