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

?>
<script type="text/javascript">
$(function()
{
<?php
if ('dispinterval' == $item)
{
?>
	$.each(["phone", "tablet"], function(index, value)
	{
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
<?php
}

if ('notifinterval' == $item)
{
?>
	$.each(["notification"], function(index, value)
	{
		var min = $("#" + value + "-from");
		var max = $("#" + value + "-until");
		var divider = $("option", min).size();
		var slider = $("<div id=\"" + value + "-slider\"></div>").insertAfter($(max)).slider({
			min: 1,
			max: 25,
			range: true,

			values: [$(min)[0].selectedIndex + 1,
					 $(max)[0].selectedIndex + 1],

			slide: function(event, ui)
			{
				$(min)[0].selectedIndex = ui.values[0] - 1;
				$(max)[0].selectedIndex = ui.values[1] - 1;
			}
		});
	});

	$("#notification-from").change(function()
	{
		if ($("#notification-until").prop("selectedIndex") <= this.selectedIndex)
			this.selectedIndex = $("#notification-until").prop("selectedIndex");

		$("#notification-slider").slider("values", 0, this.selectedIndex + 1);
	});

	$("#notification-until").change(function()
	{
		if ($("#notification-from").prop("selectedIndex") >= this.selectedIndex)
			this.selectedIndex = $("#notification-from").prop("selectedIndex");

		$("#notification-slider").slider("values", 1, this.selectedIndex + 1);
	});
<?php
}
?>
});
</script>
<ul class="menu left">
	<li><?php navitem('dispinterval', 'dispinterval' == $item ? NULL : '?req=profile&amp;dispinterval'); ?></li>
	<li class="sep"><?php navitem('notifinterval', 'notifinterval' == $item ? NULL : '?req=profile&amp;notifinterval'); ?></li>
	<li class="sep"><?php navitem('changepw', 'changepw' == $item ? NULL : '?req=profile&amp;changepw'); ?></li>
</ul>
<div style="clear: both;">
<?php
if ('dispinterval' == $item)
{
?>
<form method="post" action="?req=profile&amp;dispinterval"
	onsubmit="document.getElementById('submit').disabled=true;">
	<fieldset>
		<legend><?php echo $lang['dispinterval']; ?></legend>
<?php
	/* At this point `user` is always set */
	if (isset($_POST['submit']))
	{
		if ('interval' == $_POST['submit'])
		{
			if (isset($_POST['tm-']) &&
				isset($_POST['tm+']) &&
				isset($_POST['tt-']) &&
				isset($_POST['tt+']))
			{
				$query = <<<SQL
					UPDATE `users`
					SET `tm-`=:tmm,
						`tm+`=:tmp,
						`tt-`=:ttm,
						`tt+`=:ttp
					WHERE `id`=:uid
SQL;

				$st = $db->prepare($query);

				if (!$st)
				{
					$error = sprintf($lang['dberror'], $db->errorCode());
				}
				else
				{
					$st->bindValue(':tmm', $_POST['tm-']);
					$st->bindValue(':tmp', $_POST['tm+']);
					$st->bindValue(':ttm', $_POST['tt-']);
					$st->bindValue(':ttp', $_POST['tt+']);
					$st->bindValue(':uid', $user->id());

				if (!$st->execute())
				{
					$error = $st->errorCode();
				}
				else
				{
					$user->opt('tm-', $_POST['tm-']);
					$user->opt('tm+', $_POST['tm+']);
					$user->opt('tt-', $_POST['tt-']);
					$user->opt('tt+', $_POST['tt+']);

					$message = $lang['settingsssaved'];
				}
				}
			}

			if ($error)
			{
?>
		<div id="notification" class="error"><?php echo $error; ?></div>
<?php
			}

			if ($message)
			{
?>
		<div id="notification" class="success"><?php echo $message; ?></div>
<?php
			}
		}
	}
?>
		<div class="explainatory"><?php echo $lang['dispintervaldesc']; ?></div>
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
		<input type="hidden" name="submit" value="interval">
		<input type="submit" id="submit" name="submit" value="<?php echo $lang['submit']; ?>">
	</div>
</form>
<?php
}
else
if ('notifinterval' == $item)
{
?>
<form method="post" action="?req=profile&amp;notifinterval"
	onsubmit="document.getElementById('submit').disabled=true;">
	<fieldset>
		<legend><?php echo $lang['notifinterval']; ?></legend>
<?php
	/* At this point `user` is always set */
	if (isset($_POST['submit']))
	{
		if ('notifications' == $_POST['submit'])
		{
			if (isset($_POST['from']) &&
				isset($_POST['until']))
			{
				if (!isset($_POST['timefmt']))
				{
					$_POST_timefmt = NULL;
				}
				else
				{
					if (!$_POST['timefmt'])
					{
						$_POST_timefmt = NULL;
					}
					else
					{
						if (0 == strlen($_POST['timefmt']))
							$_POST_timefmt = NULL;
						else
							$_POST_timefmt = $_POST['timefmt'];
					}
				}

				if (NULL == $_POST_timefmt)
				{
					$time = strftime('+0 %H:%M');
				}
				else
				{
					$timefmt = preg_replace('/%\+/', '+0', $_POST_timefmt);
					$time = strftime($timefmt);
				}

				if (FALSE === $time)
				{
					$error = sprintf($lang['strftime-false'], $_POST_timefmt);
				}
				else
				{
					$query = <<<SQL
						UPDATE `users`
						SET `notification-from`=:from,
							`notification-until`=:until,
							`notification-timefmt`=:fmt
						WHERE `id`=:uid
SQL;

					$st = $db->prepare($query);

					if (!$st)
					{
						$error = sprintf($lang['dberror'], $sb->errorCode());
					}
					else
					{
						$st->bindValue(':from', $_POST['from']);
						$st->bindValue(':until', $_POST['until']);
						$st->bindValue(':fmt', $_POST_timefmt ? "'$_POST_timefmt'" : NULL);
						$st->bindValue(':uid', $user->id());

					if (!$st->execute())
					{
						$error = sprintf($lang['dberror'], $st->errorCode());
					}
					else
					{
						$user->opt('notification-from', $_POST['from']);
						$user->opt('notification-until', $_POST['until']);
						$user->opt('notification-timefmt', $_POST_timefmt);

						$message = "$lang[settingsssaved] ".
									sprintf($lang['strftime-true'], $time);
					}
					}
				}
			}

			if ($error)
			{
?>
		<div id="notification" class="error"><?php echo $error; ?></div>
<?php
			}

			if ($message)
			{
?>
		<div id="notification" class="success"><?php echo $message; ?></div>
<?php
			}
		}
	}
?>
		<div class="explainatory"><?php echo $lang['notifintervaldesc']; ?></div>
		<div class="table">
			<div class="row">
				<div class="cell label"><?php echo $lang['notif-from-until']; ?></div>
				<div class="cell">
					<select name="from" id="notification-from">
<?php
		$from = $user->opt('notification-from');

		if ($from)
			$from = intval($from);
		else
			$from = 8;

		for ($i = 0; $i <= 24; $i++)
		{
			echo sprintf('<option%s value="%02u:00">%02u:00</option>',
					$from == $i ? " selected" : "", $i, $i)."\n";
		}
?>
					</select>
					<select name="until" id="notification-until">
<?php
		$until = $user->opt('notification-until');

		if ($until)
			$until = intval($until);
		else
			$until = 22;

		for ($i = 0; $i <= 24; $i++)
		{
			echo sprintf('<option%s value="%02u:00">%02u:00</option>',
					$until == $i ? " selected" : "", $i, $i)."\n";
		}
?>
					</select>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?php echo $lang['notification-timefmt']; ?></div>
				<div class="cell">
					<div>
						<input type="text" name="timefmt" id="timefmt" style="width: 90%;"
						 value="<?php echo isset($_POST['timefmt']) ? $_POST['timefmt'] : $user->opt('notification-timefmt'); ?>"
						 maxlength="31">
						<sup>*</sup>
					</div>
					<div>
		<div class="explainatory">
			<div style="font-size: smaller;">
				<sup>*</sup>
<?php
				echo sprintf($lang['notification-strftime_1'],
					'de' == $_SESSION['lang'] ?
						'<a href="http://php.net/manual/de/function.strftime.php">strftime()</a>' :
						'<a href="http://php.net/manual/en/function.strftime.php#refsect1-function.strftime-parameters">strftime()</a>');
?>
				<div>
					<div style="padding-top: 1.3em; text-decoration: underline;">
						<?php echo $lang['notification-strftime_2']; ?>
					</div>
					<dl class="inline">
						<dt>%a</dt><dd><?php echo $lang['notification-strftime_a']; ?></dd>
						<dt>%A</dt><dd><?php echo $lang['notification-strftime_A']; ?></dd>
						<dt>%b</dt><dd><?php echo $lang['notification-strftime_b']; ?></dd>
						<dt>%B</dt><dd><?php echo $lang['notification-strftime_B']; ?></dd>
						<dt>%c</dt><dd><?php echo $lang['notification-strftime_c']; ?></dd>
						<dt>%d</dt><dd><?php echo $lang['notification-strftime_d']; ?></dd>
						<dt>%e</dt><dd><?php echo $lang['notification-strftime_e']; ?></dd>
						<dt>%H</dt><dd><?php echo $lang['notification-strftime_H']; ?></dd>
						<dt>%I</dt><dd><?php echo $lang['notification-strftime_I']; ?></dd>
						<dt>%m</dt><dd><?php echo $lang['notification-strftime_m']; ?></dd>
						<dt>%p</dt><dd><?php echo $lang['notification-strftime_p']; ?></dd>
						<dt>%S</dt><dd><?php echo $lang['notification-strftime_S']; ?></dd>
					</dl>
					<div style="padding-top: 1.3em; text-decoration: underline; clear: both;">
						<?php echo $lang['notification-strftime_3']; ?>
					</div>
					<dl class="inline">
						<dt>%+</dt><dd><?php echo $lang['notification-strftime_4']; ?></dd>
					</dl>
				</div>
			</div>
		</div>
					</div>
				</div>
			</div>
		</div>
	</fieldset>
	<div class="center">
		<input type="hidden" name="submit" value="notifications">
		<input type="submit" id="submit" value="<?php echo $lang['submit']; ?>">
	</div>
</form>
<?php
}
else
{
	include 'forms/changepw.php';
}
?>
</div>
