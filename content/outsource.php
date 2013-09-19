<?php

/******************************************************************************
 *
 *       project: FRA-flights Live Schedule
 *
 *       $Author: flederwiesel $
 *         $Date: 2013-07-02 20:26:27 +0200 (Di, 02 Jul 2013) $
 *          $Rev: 337 $
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

$error = null;

if (isset($_POST['older-than']))
{
	$date = mktime_c($_POST['older-than']);

	if (-1 == $date)
	{
		$error = $lang['datefrom'];
	}
	else
	{
		$date = strftime('%Y-%m-%d %H:%M:%S', $date);

		$result = mysql_query('START TRANSACTION');

		if ($result)
		{
			$result = mysql_query("INSERT INTO `flights:past`".
					 			  "(SELECT * FROM `flights` WHERE `scheduled` < '$date')");

			if ($result)
				$result = mysql_query("DELETE FROM `flights` WHERE `scheduled` < '$date'");
			else
				$error = sprintf("[%d] %s", mysql_errno(), mysql_error());

			$result = mysql_query($result ? 'COMMIT' : 'ROLLBACK');
		}

		if ($result)
		{
			$message = 'OK';
		}
		else
		{
			if (!$error)
				$error = sprintf("[%d] %s", mysql_errno(), mysql_error());
		}
	}
}

?>
<link type="text/css" rel="stylesheet" href="script/<?php echo $jqueryui; ?>/themes/base/<?php echo $jquerymin; ?>jquery.ui.core.css">
<link type="text/css" rel="stylesheet" href="script/<?php echo $jqueryui; ?>/themes/base/<?php echo $jquerymin; ?>jquery.ui.theme.css">
<link type="text/css" rel="stylesheet" href="script/<?php echo $jqueryui; ?>/themes/base/<?php echo $jquerymin; ?>jquery.ui.datepicker.css">
<script type="text/javascript" src="script/<?php echo $jqueryui; ?>/ui/<?php echo $jquerymin; ?>jquery.ui.core.js"></script>
<script type="text/javascript" src="script/<?php echo $jqueryui; ?>/ui/<?php echo $jquerymin; ?>jquery.ui.widget.js"></script>
<script type="text/javascript" src="script/<?php echo $jqueryui; ?>/ui/<?php echo $jquerymin; ?>jquery.ui.datepicker.js"></script>
<script type="text/javascript">
$(function()
{
	$("#older-than").datepicker({
		dateFormat: "dd.mm.yy",
		firstDay: 1,
		minDate: "01.01.2012",
		maxDate: "<?php echo date('01.m.Y', strtotime('-1 month')); ?>",
		changeMonth: true,
		changeYear: true,
	});
});
</script>
<form method="post" action="?page=outsource">
	<fieldset>
		<legend>...</legend>
<?php if (isset($error)) { ?>
		<div id="notification" class="error"><?php echo $error; ?></div>
<?php } else if (isset($message)) { ?>
		<div id="notification" class="success"><?php echo $message; ?></div>
<?php } ?>
		<div class="table">
			<div class="row">
				<div class="cell label">Outsource flights older than</div>
				<div class="cell">
					<input type="text" name="older-than" id="older-than"
					 value="<?php Input_SetValue('older-than', INP_FORCE, date('01.m.Y', strtotime('-1 month'))); ?>"/>
				</div>
			</div>
		</div>
	</fieldset>
	<div class="center"><input id="submit" type="submit" name="submit"/></div>
</form>
