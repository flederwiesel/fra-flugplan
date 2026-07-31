<script type="text/javascript" src="<?= Asset::src('script/profile.js') ?>" defer></script>
<?php
include 'photodb.php';
/* At this point `user` is always set */
?>
<ul class="menu left" id="profile-tab" data-active="<?= $item ?>">
	<li><?= navitem('dispinterval', 'dispinterval' == $item ? null : '?req=profile&amp;dispinterval') ?></li>
	<li class="sep"><?= navitem('notifinterval', 'notifinterval' == $item ? null : '?req=profile&amp;notifinterval') ?></li>
	<li class="sep"><?= navitem('photodb', 'photodb' == $item ? null : '?req=profile&amp;photodb') ?></li>
	<li class="sep"><?= navitem('changepw', 'changepw' == $item ? null : '?req=profile&amp;changepw') ?></li>
</ul>
<div>
<?php
if ('dispinterval' == $item)
{
?>
<form method="post" action="?req=profile&amp;dispinterval">
	<fieldset>
		<legend><?= $STRINGS['dispinterval'] ?></legend>
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
		<div class="explainatory"><?= $STRINGS['dispintervaldesc'] ?></div>
		<div class="table">
			<div class="row">
				<div class="cell label"><?= $STRINGS['cellphone'] ?></div>
				<div class="cell">
					<select name="tm-" id="phone-min">
						<option value="-3600"<?= $user->opt('tm-') == -3600 ? ' selected' : '' ?>>-01:00 h</option>
						<option value="-900"<?= $user->opt('tm-') == -900 ? ' selected' : '' ?>>-00:15 h</option>
						<option value="-300"<?= $user->opt('tm-') == -300 ? ' selected' : '' ?>>-00:05 h</option>
						<option value="0"<?= $user->opt('tm-') == 0 ? ' selected' : '' ?>>00:00 h</option>
					</select>
					<select name="tm+" id="phone-max">
						<option value="3600"<?= $user->opt('tm+') == 3600 ? ' selected' : '' ?>>01:00 h</option>
						<option value="7200"<?= $user->opt('tm+') == 7200 ? ' selected' : '' ?>>02:00 h</option>
						<option value="14400"<?= $user->opt('tm+') == 14400 ? ' selected' : '' ?>>04:00 h</option>
						<option value="28800"<?= $user->opt('tm+') == 28800 ? ' selected' : '' ?>>08:00 h</option>
						<option value="86400"<?= $user->opt('tm+') == 86400 ? ' selected' : '' ?>>24:00 h</option>
					</select>
				</div>
			</div>
			<div class="row">
				<div class="cell label"><?= $STRINGS['tablet'] ?></div>
				<div class="cell">
					<select name="tt-" id="tablet-min">
						<option value="-3600"<?= $user->opt('tt-') == -3600 ? ' selected' : '' ?>>-01:00 h</option>
						<option value="-900"<?= $user->opt('tt-') == -900 ? ' selected' : '' ?>>-00:15 h</option>
						<option value="-300"<?= $user->opt('tt-') == -300 ? ' selected' : '' ?>>-00:05 h</option>
						<option value="0"<?= $user->opt('tt-') == 0 ? ' selected' : '' ?>>00:00 h</option>
					</select>
					<select name="tt+" id="tablet-max">
						<option value="3600"<?= $user->opt('tt+') == 3600 ? ' selected' : '' ?>>01:00 h</option>
						<option value="7200"<?= $user->opt('tt+') == 7200 ? ' selected' : '' ?>>02:00 h</option>
						<option value="14400"<?= $user->opt('tt+') == 14400 ? ' selected' : '' ?>>04:00 h</option>
						<option value="28800"<?= $user->opt('tt+') == 28800 ? ' selected' : '' ?>>08:00 h</option>
						<option value="86400"<?= $user->opt('tt+') == 86400 ? ' selected' : '' ?>>24:00 h</option>
					</select>
				</div>
			</div>
		</div>
	</fieldset>
	<input type="hidden" name="CSRFToken" value="<?= CsrfToken::get() ?>">
	<div class="center">
		<input type="hidden" name="submit" value="interval">
		<input type="submit" id="submit" name="submit" value="<?= $STRINGS['submit'] ?>">
	</div>
</form>
<?php
}
else
if ('notifinterval' == $item)
{
?>
<form method="post" action="?req=profile&amp;notifinterval">
	<fieldset>
		<legend><?= $STRINGS['notifinterval'] ?></legend>
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
		<div class="explainatory"><?= $STRINGS['notifintervaldesc'] ?></div>
		<div class="table">
			<div class="row">
				<div class="cell label"><?= $STRINGS['notif-from-until'] ?></div>
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
				<div class="cell label"><?= $STRINGS['notification-timefmt'] ?></div>
				<div class="cell">
					<div>
						<input type="text" name="timefmt" id="timefmt"
						 value="<?= isset($_POST['timefmt']) ? $_POST['timefmt'] : $user->opt('notification-timefmt') ?>"
						 maxlength="31">
						<sup>*</sup>
					</div>
					<div>
		<div class="explainatory">
			<div class="smaller">
				<sup>*</sup>
<?php
				echo sprintf($STRINGS['notification-strftime_1'],
					'de' == $_SESSION['lang'] ?
						'<a href="https://php.net/manual/de/function.strftime.php#refsect1-function.strftime-parameters">strftime()</a>' :
						'<a href="https://php.net/manual/en/function.strftime.php#refsect1-function.strftime-parameters">strftime()</a>');
?>
				<div>
					<h4>
						<?= $STRINGS['notification-strftime_2'] ?>
					</h4>
					<dl class="inline">
						<dt>%a</dt><dd><?= $STRINGS['notification-strftime_a'] ?></dd>
						<dt>%A</dt><dd><?= $STRINGS['notification-strftime_A'] ?></dd>
						<dt>%b</dt><dd><?= $STRINGS['notification-strftime_b'] ?></dd>
						<dt>%B</dt><dd><?= $STRINGS['notification-strftime_B'] ?></dd>
						<dt>%c</dt><dd><?= $STRINGS['notification-strftime_c'] ?></dd>
						<dt>%d</dt><dd><?= $STRINGS['notification-strftime_d'] ?></dd>
						<dt>%e</dt><dd><?= $STRINGS['notification-strftime_e'] ?></dd>
						<dt>%H</dt><dd><?= $STRINGS['notification-strftime_H'] ?></dd>
						<dt>%I</dt><dd><?= $STRINGS['notification-strftime_I'] ?></dd>
						<dt>%m</dt><dd><?= $STRINGS['notification-strftime_m'] ?></dd>
						<dt>%p</dt><dd><?= $STRINGS['notification-strftime_p'] ?></dd>
						<dt>%S</dt><dd><?= $STRINGS['notification-strftime_S'] ?></dd>
					</dl>
					<h4>
						<?= $STRINGS['notification-strftime_3'] ?>
					</h4>
					<dl class="inline">
						<dt>%+</dt><dd><?= $STRINGS['notification-strftime_4'] ?></dd>
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
		<input type="submit" id="submit" value="<?= $STRINGS['submit'] ?>">
	</div>
</form>
<?php
}
else if ('photodb' == $item)
{
?>
<form method="post" action="?req=profile&amp;photodb">
	<fieldset>
		<legend><?= $STRINGS['photodb'] ?></legend>
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
		<div class="explainatory"><?= sprintf($STRINGS['photodbdesc'], "<img src='img/camera.png' alt='{$user->opt('photodb')}'>") ?></div>
		<div class="table">
			<div class="row">
				<div class="cell label"><?= $STRINGS['photodbsel'] ?></div>
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
		<input type="submit" id="submit" name="submit" value="<?= $STRINGS['submit'] ?>">
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
