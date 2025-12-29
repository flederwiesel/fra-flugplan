<?php

?>
<script type="text/javascript">
$(function()
{
<?php
include 'photodb.php';

if ('dispinterval' == $item)
{
?>
	$.each(["phone", "tablet"], function(index, value)
	{
		var min = $("#" + value + "-min");
		var max = $("#" + value + "-max");
		var divider = $("option", min).length;
		var ticks = $("option", min).length + $("option", max).length;
		var slider = $("<div id=\"" + value + "-slider\"></div>").insertAfter($(max)).slider({
			min: 1,
			max: ticks,
			range: true,

			values: [$(min)[0].selectedIndex + 1,
					 $(max)[0].selectedIndex + 1 + $("option", min).length],

			slide: function(event, ui)
			{
				/* Don't let min and max overlap! */
				if (ui.values[0] > divider)
					return false;

				if (ui.values[1] < divider + 1)
					return false;

				$(min)[0].selectedIndex = ui.values[0] - 1;
				$(max)[0].selectedIndex = ui.values[1] - 1 - $("option", min).length;
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
			$("#phone-min option").length + this.selectedIndex + 1);
	});

	$("#tablet-min").change(function()
	{
		$("#tablet-slider").slider("values", 0, this.selectedIndex + 1);
	});

	$("#tablet-max").change(function()
	{
		$("#tablet-slider").slider("values", 1,
			$("#tablet-min option").length + this.selectedIndex + 1);
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
		var divider = $("option", min).lentgh;
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
/* At this point `user` is always set */
?>
});
</script>
<ul class="menu left">
	<li><?= navitem("dispinterval", "dispinterval" == $item ? null : "?req=profile&amp;dispinterval") ?></li>
	<li class="sep"><?= navitem("notifinterval", "notifinterval" == $item ? null : "?req=profile&amp;notifinterval") ?></li>
	<li class="sep"><?= navitem("photodb", "photodb" == $item ? null : "?req=profile&amp;photodb") ?></li>
	<li class="sep"><?= navitem("changepw", "changepw" == $item ? null : "?req=profile&amp;changepw") ?></li>
</ul>
<div style="clear: both;">
<?php
if ('dispinterval' == $item)
{
?>
<form method="post" action="?req=profile&amp;dispinterval"
	onsubmit="document.getElementById('submit').disabled=true;">
	<fieldset>
		<legend><?= $STRINGS["dispinterval"] ?></legend>
<?php
	if (isset($_POST['submit']))
	{
		if ('interval' == $_POST['submit'])
		{
			if (isset($_POST['tm-']) &&
				isset($_POST['tm+']) &&
				isset($_POST['tt-']) &&
				isset($_POST['tt+']))
			{
				try
				{
					$st = $db->prepare(<<<SQL
						/*[Q41]*/
						UPDATE `users`
						SET
							`tm-` = :tmm,
							`tm+` = :tmp,
							`tt-` = :ttm,
							`tt+` = :ttp
						WHERE
							`id` = :uid
						SQL
					);

					$st->execute([
						"tmm" => $_POST['tm-'],
						"tmp" => $_POST['tm+'],
						"ttm" => $_POST['tt-'],
						"ttp" => $_POST['tt+'],
						"uid" => $user->id(),
					]);

					$user->opt('tm-', $_POST['tm-']);
					$user->opt('tm+', $_POST['tm+']);
					$user->opt('tt-', $_POST['tt-']);
					$user->opt('tt+', $_POST['tt+']);

					$message = $STRINGS['settingsssaved'];
				}
				catch (PDOException $ex)
				{
					$error = PDOErrorInfo($ex, $STRINGS['dberror']);
				}
			}

			if ($error)
			{
?>
		<div id="notification" class="error"><?= $error ?></div>
<?php
			}

			if ($message)
			{
?>
		<div id="notification" class="success"><?= $message ?></div>
<?php
			}
		}
	}
?>
		<div class="explainatory"><?= $STRINGS["dispintervaldesc"] ?></div>
		<div class="table">
			<div class="row">
				<div class="cell label"><?= $STRINGS["cellphone"] ?></div>
				<div class="cell">
					<select name="tm-" id="phone-min">
						<option value="-3600"<?= $user->opt('tm-') == -3600 ? " selected" : "" ?>>-01:00 h</option>
						<option value="-900"<?= $user->opt('tm-') == -900 ? " selected" : "" ?>>-00:15 h</option>
						<option value="-300"<?= $user->opt('tm-') == -300 ? " selected" : "" ?>>-00:05 h</option>
						<option value="0"<?= $user->opt('tm-') == 0 ? " selected" : "" ?>>00:00 h</option>
					</select>
					<select name="tm+" id="phone-max">
						<option value="3600"<?= $user->opt("tm+") == 3600 ? " selected" : "" ?>>01:00 h</option>
						<option value="7200"<?= $user->opt("tm+") == 7200 ? " selected" : "" ?>>02:00 h</option>
						<option value="14400"<?= $user->opt("tm+") == 14400 ? " selected" : "" ?>>04:00 h</option>
						<option value="28800"<?= $user->opt("tm+") == 28800 ? " selected" : "" ?>>08:00 h</option>
						<option value="86400"<?= $user->opt("tm+") == 86400 ? " selected" : "" ?>>24:00 h</option>
					</select>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?= $STRINGS["tablet"] ?></div>
				<div class="cell">
					<select name="tt-" id="tablet-min">
						<option value="-3600"<?= $user->opt('tt-') == -3600 ? " selected" : "" ?>>-01:00 h</option>
						<option value="-900"<?= $user->opt('tt-') == -900 ? " selected" : "" ?>>-00:15 h</option>
						<option value="-300"<?= $user->opt('tt-') == -300 ? " selected" : "" ?>>-00:05 h</option>
						<option value="0"<?= $user->opt('tt-') == 0 ? " selected" : "" ?>>00:00 h</option>
					</select>
					<select name="tt+" id="tablet-max">
						<option value="3600"<?= $user->opt("tt+") == 3600 ? " selected" : "" ?>>01:00 h</option>
						<option value="7200"<?= $user->opt("tt+") == 7200 ? " selected" : "" ?>>02:00 h</option>
						<option value="14400"<?= $user->opt("tt+") == 14400 ? " selected" : "" ?>>04:00 h</option>
						<option value="28800"<?= $user->opt("tt+") == 28800 ? " selected" : "" ?>>08:00 h</option>
						<option value="86400"<?= $user->opt("tt+") == 86400 ? " selected" : "" ?>>24:00 h</option>
					</select>
				</div>
			</div>
		</div>
	</fieldset>
	<input type="hidden" name="CSRFToken" value="<?= CsrfToken::get() ?>">
	<div class="center">
		<input type="hidden" name="submit" value="interval">
		<input type="submit" id="submit" name="submit" value="<?= $STRINGS["submit"] ?>">
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
		<legend><?= $STRINGS["notifinterval"] ?></legend>
<?php
	if (isset($_POST['submit']))
	{
		if ('notifications' == $_POST['submit'])
		{
			if (isset($_POST['from']) &&
				isset($_POST['until']))
			{
				if (!isset($_POST['timefmt']))
				{
					$_POST_timefmt = null;
				}
				else
				{
					if (!$_POST['timefmt'])
					{
						$_POST_timefmt = null;
					}
					else
					{
						if (0 == strlen($_POST['timefmt']))
							$_POST_timefmt = null;
						else
							$_POST_timefmt = $_POST['timefmt'];
					}
				}

				if (null == $_POST_timefmt)
				{
					$time = strftime('+0 %H:%M');
				}
				else
				{
					$timefmt = preg_replace('/%\+/', '+0', $_POST_timefmt);
					$time = strftime($timefmt);
				}

				if (false === $time)
				{
					$error = sprintf($STRINGS['strftime-false'], $_POST_timefmt);
				}
				else
				{
					try
					{
						$st = $db->prepare(<<<SQL
							/*[Q42]*/
							UPDATE `users`
							SET
								`notification-from` = :from,
								`notification-until` = :until,
								`notification-timefmt` = :fmt
							WHERE
								`id` = :uid
							SQL
						);

						$st->execute([
							"from" => $_POST['from'],
							"until" => $_POST['until'],
							"fmt" => $_POST_timefmt,
							"uid" => $user->id(),
						]);

						$user->opt('notification-from', $_POST['from']);
						$user->opt('notification-until', $_POST['until']);
						$user->opt('notification-timefmt', $_POST_timefmt);

						$message = "$STRINGS[settingsssaved] " . sprintf($STRINGS['strftime-true'], $time);
					}
					catch (PDOException $ex)
					{
						$error = PDOErrorInfo($ex, $STRINGS['dberror']);
					}
				}
			}

			if ($error)
			{
?>
		<div id="notification" class="error"><?= $error ?></div>
<?php
			}

			if ($message)
			{
?>
		<div id="notification" class="success"><?= $message ?></div>
<?php
			}
		}
	}
?>
		<div class="explainatory"><?= $STRINGS["notifintervaldesc"] ?></div>
		<div class="table">
			<div class="row">
				<div class="cell label"><?= $STRINGS["notif-from-until"] ?></div>
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
				<div class="cell label"><?= $STRINGS["notification-timefmt"] ?></div>
				<div class="cell">
					<div>
						<input type="text" name="timefmt" id="timefmt" style="width: 90%;"
						 value="<?= isset($_POST["timefmt"]) ? $_POST["timefmt"] : $user->opt("notification-timefmt") ?>"
						 maxlength="31">
						<sup>*</sup>
					</div>
					<div>
		<div class="explainatory">
			<div style="font-size: smaller;">
				<sup>*</sup>
<?php
				echo sprintf($STRINGS['notification-strftime_1'],
					'de' == $_SESSION['lang'] ?
						'<a href="http://php.net/manual/de/function.strftime.php">strftime()</a>' :
						'<a href="http://php.net/manual/en/function.strftime.php#refsect1-function.strftime-parameters">strftime()</a>');
?>
				<div>
					<div style="padding-top: 1.3em; text-decoration: underline;">
						<?= $STRINGS["notification-strftime_2"] ?>
					</div>
					<dl class="inline">
						<dt>%a</dt><dd><?= $STRINGS["notification-strftime_a"] ?></dd>
						<dt>%A</dt><dd><?= $STRINGS["notification-strftime_A"] ?></dd>
						<dt>%b</dt><dd><?= $STRINGS["notification-strftime_b"] ?></dd>
						<dt>%B</dt><dd><?= $STRINGS["notification-strftime_B"] ?></dd>
						<dt>%c</dt><dd><?= $STRINGS["notification-strftime_c"] ?></dd>
						<dt>%d</dt><dd><?= $STRINGS["notification-strftime_d"] ?></dd>
						<dt>%e</dt><dd><?= $STRINGS["notification-strftime_e"] ?></dd>
						<dt>%H</dt><dd><?= $STRINGS["notification-strftime_H"] ?></dd>
						<dt>%I</dt><dd><?= $STRINGS["notification-strftime_I"] ?></dd>
						<dt>%m</dt><dd><?= $STRINGS["notification-strftime_m"] ?></dd>
						<dt>%p</dt><dd><?= $STRINGS["notification-strftime_p"] ?></dd>
						<dt>%S</dt><dd><?= $STRINGS["notification-strftime_S"] ?></dd>
					</dl>
					<div style="padding-top: 1.3em; text-decoration: underline; clear: both;">
						<?= $STRINGS["notification-strftime_3"] ?>
					</div>
					<dl class="inline">
						<dt>%+</dt><dd><?= $STRINGS["notification-strftime_4"] ?></dd>
					</dl>
				</div>
			</div>
		</div>
					</div>
				</div>
			</div>
		</div>
	</fieldset>
	<input type="hidden" name="CSRFToken" value="<?= $_SESSION['CSRFToken'] ?>">
	<div class="center">
		<input type="hidden" name="submit" value="notifications">
		<input type="submit" id="submit" value="<?= $STRINGS["submit"] ?>">
	</div>
</form>
<?php
}
else if ('photodb' == $item)
{
?>
<form method="post" action="?req=profile&amp;photodb"
	onsubmit="document.getElementById('submit').disabled=true;">
	<fieldset>
		<legend><?= $STRINGS["photodb"] ?></legend>
<?php
	if (isset($_POST['submit']))
	{
		if ('photodb' == $_POST['submit'])
		{
			if (isset($_POST['photodb']))
			{
				try
				{
					$st = $db->prepare(<<<SQL
						/*[Q43]*/
						UPDATE `users`
						SET `photodb` = :photodb
						WHERE `id` = :uid
						SQL
					);

					$st->execute([
						"photodb" => $_POST['photodb'],
						"uid" => $user->id(),
					]);

					$user->opt('photodb', $_POST['photodb']);

					$message = $STRINGS['settingsssaved'];
				}
				catch (PDOException $ex)
				{
					$error = PDOErrorInfo($ex, $STRINGS['dberror']);
				}
			}

			if ($error)
			{
?>
		<div id="notification" class="error"><?= $error ?></div>
<?php
			}

			if ($message)
			{
?>
		<div id="notification" class="success"><?= $message ?></div>
<?php
			}
		}
	}
?>
		<div class="explainatory"><?= sprintf($STRINGS["photodbdesc"], "<img src='img/photodb.png' alt='{$user->opt('photodb')}'>") ?></div>
		<div class="table">
			<div class="row">
				<div class="cell label"><?= $STRINGS["photodbsel"] ?></div>
				<div class="cell">
					<select name="photodb" id="photodb">
<?php
							foreach ($URL as $domain => $url)
							{
								$sel = $domain == $user->opt('photodb') ? ' selected' : '';
								echo "<option value='$domain'$sel>$domain</option>\n";
							}
?>
					</select>
				</div>
			</div>
		</div>
	</fieldset>
	<input type="hidden" name="CSRFToken" value="<?= $_SESSION['CSRFToken'] ?>">
	<div class="center">
		<input type="hidden" name="submit" value="photodb">
		<input type="submit" id="submit" name="submit" value="<?= $STRINGS["submit"] ?>">
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
