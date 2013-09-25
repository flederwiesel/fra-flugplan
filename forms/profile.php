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

?>
<link rel="stylesheet" href="script/<?php echo $jqueryui; ?>/themes/base/<?php echo $jquerymin; ?>jquery.ui.base.css">
<link rel="stylesheet" href="script/<?php echo $jqueryui; ?>/themes/base/<?php echo $jquerymin; ?>jquery.ui.theme.css">
<link rel="stylesheet" href="script/<?php echo $jqueryui; ?>/themes/base/<?php echo $jquerymin; ?>jquery.ui.slider.css">
<script src="script/<?php echo $jqueryui; ?>/ui/<?php echo $jquerymin; ?>jquery.ui.core.js"></script>
<script src="script/<?php echo $jqueryui; ?>/ui/<?php echo $jquerymin; ?>jquery.ui.widget.js"></script>
<script src="script/<?php echo $jqueryui; ?>/ui/<?php echo $jquerymin; ?>jquery.ui.mouse.js"></script>
<script src="script/<?php echo $jqueryui; ?>/ui/<?php echo $jquerymin; ?>jquery.ui.slider.js"></script>
<script>
$(function()
{
	$.each(["phone", "tablet"], function(index, value) {
		var min = $("#" + value + "-min");
		var max = $("#" + value + "-max");
		var divider = $("option", min).size();
		var slider = $("<div id=\"" + value + "-slider\"></div>").insertAfter($(max)).slider({
			min: 1,
			max: 7,
			range: true,

			values: [$(min)[0].selectedIndex + 1,
					 $(max)[0].selectedIndex + 1 + $("option", min).size()],

			slide: function(event, ui)
			{
				/* Don't let min and max overlap! */
				if (ui.values[0] > divider)
					return false;

				if (ui.values[1] < divider + 1)
					return false;

				$(min)[0].selectedIndex = ui.values[0] - 1;
				$(max)[0].selectedIndex = ui.values[1] - 1 - $("option", min).size();
			}
		});
	});

	$("#phone-min").change(function()
	{
		$("#phone-slider").slider("values", 0, this.selectedIndex + 1);
	});

	$("#phone-max").change(function()
	{
		$("#phone-slider").slider("values", 1,
			$("#phone-min option").size() + this.selectedIndex + 1);
	});

	$("#tablet-min").change(function()
	{
		$("#tablet-slider").slider("values", 0, this.selectedIndex + 1);
	});

	$("#tablet-max").change(function()
	{
		$("#tablet-slider").slider("values", 1,
			$("#tablet-min option").size() + this.selectedIndex + 1);
	});
});
</script>
<form method="post" action="?req=profile"
	onsubmit="document.getElementById('submit').disabled=true;">
	<?php
	/* At this point `user` is always set */
	$error_interval = null;

	if (isset($_POST['interval']))
	{
		$query = sprintf("UPDATE `users` ".
						 "SET `tm-`=%ld, `tm+`=%ld, `tt-`=%ld, `tt+`=%ld WHERE `id`=%lu",
						 $_POST['tm-'], $_POST['tm+'], $_POST['tt-'], $_POST['tt+'],
						  $user->id());

		$result = mysql_query($query);

		if (!$result)
		{
			$error = mysql_error();
		}
		else
		{
			$user->opt('tm-', $_POST['tm-']);
			$user->opt('tm+', $_POST['tm+']);
			$user->opt('tt-', $_POST['tt-']);
			$user->opt('tt+', $_POST['tt+']);
		}
	}
	?>
	<fieldset>
		<legend><?php echo $lang['displayinterval']; ?></legend>
<?php
		if (isset($_POST['interval']))
		{
			if ($error)
			{
?>
		<div id="notification" class="error">
			<?php echo $error; ?>
		</div>
<?php
			}
			else
			{
?>
		<div id="notification" class="success">
			<?php echo $lang['settingsssaved']; ?>
		</div>
<?php
			}
		}
?>
		<div class="explainatory"><?php echo $lang['displayintervaldesc']; ?></div>
		<div class="table">
			<div class="row">
				<div class="cell label"><?php echo $lang['cellphone']; ?></div>
				<div class="cell">
					<select name="tm-" id="phone-min">
						<option value="-75"<?php if (-75 == $user->opt('tm-')) echo ' selected'; ?>>-00:15 h</option>
						<option value="0"<?php if (0 == $user->opt('tm-')) echo ' selected'; ?>>00:00 h</option>
					</select>
					<select name="tm+" id="phone-max">
						<option value="3600"<?php if (3600 == $user->opt('tm+')) echo ' selected'; ?>>01:00 h</option>
						<option value="7200"<?php if (7200 == $user->opt('tm+')) echo ' selected'; ?>>02:00 h</option>
						<option value="14400"<?php if (14400 == $user->opt('tm+')) echo ' selected'; ?>>04:00 h</option>
						<option value="28800"<?php if (28800 == $user->opt('tm+')) echo ' selected'; ?>>08:00 h</option>
						<option value="86400"<?php if (86400 == $user->opt('tm+')) echo ' selected'; ?>>24:00 h</option>
					</select>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $lang['tablet']; ?></div>
				<div class="cell">
					<select name="tt-" id="tablet-min">
						<option value="-75"<?php if (-75 == $user->opt('tt-')) echo ' selected'; ?>>-00:15 h</option>
						<option value="0"<?php if (0 == $user->opt('tt-')) echo ' selected'; ?>>00:00 h</option>
					</select>
					<select name="tt+" id="tablet-max">
						<option value="3600"<?php if (3600 == $user->opt('tt+')) echo ' selected'; ?>>01:00 h</option>
						<option value="7200"<?php if (7200 == $user->opt('tt+')) echo ' selected'; ?>>02:00 h</option>
						<option value="14400"<?php if (14400 == $user->opt('tt+')) echo ' selected'; ?>>04:00 h</option>
						<option value="28800"<?php if (28800 == $user->opt('tt+')) echo ' selected'; ?>>08:00 h</option>
						<option value="86400"<?php if (86400 == $user->opt('tt+')) echo ' selected'; ?>>24:00 h</option>
					</select>
				</div>
			</div>
		</div>
	</fieldset>
	<div class="center">
		<input type="hidden" name="interval">
		<input type="submit" id="submit" name="submit" value="<?php echo $lang['submit']; ?>">
	</div>
</form>
<?php include('forms/changepw.php'); ?>
