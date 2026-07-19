<?php

$error = null;
$message = null;

include 'photodb.php';

if ($user)
	$photodb = $user->opt('photodb');
else
	$photodb = 'airliners.net';

/* Update watchlist from posted values */
if (isset($_POST['add']) ||
	isset($_POST['upd']) ||
	isset($_POST['del']))
{
	if (!$user)
	{
		$error = $STRINGS['unexpected'];
	}
	else
	{
		if ($db)
		{
			try
			{
				$uid = $user->id();

				// If at least one notification is set active,
				// warn if notification times need to be set
				$CheckNotifTimes = false;

				if (isset($_POST['del']))
				{
					$stNotif = $db->prepare(<<<SQL
						/*[Q16]*/
						DELETE `watchlist-notifications`
						FROM `watchlist-notifications`
						INNER JOIN (
							SELECT `id`
							FROM `watchlist`
							WHERE `user` = :uid
								AND `reg` = :reg
						) AS `watchlist`
							ON `watchlist`.`id` = `watchlist-notifications`.`watch`
						SQL
					);

					$stWatch = $db->prepare(<<<SQL
						/*[Q17]*/
						DELETE FROM `watchlist`
						WHERE
							`user` = :uid AND
							`reg` = :reg
						SQL
					);

					$del = explode("\n", $_POST['del']);

					foreach ($del as $reg)
					{
						$reg = strtoupper(trim($reg));

						$stNotif->execute([
							"uid" => $uid,
							"reg" => $reg,
						]);

						$stWatch->execute([
							"uid" => $uid,
							"reg" => $reg,
						]);
					}
				}

				if (isset($_POST['upd']))
				{
					$st = $db->prepare(<<<SQL
						/*[Q20]*/
						UPDATE `watchlist`
						SET
							`reg` = :new,
							`comment` = :comment,
							`notify` = :notify
						WHERE
							`user` = :uid AND
							`reg` = :reg
						SQL
					);

					$upd = explode("\n", $_POST['upd']);

					foreach ($upd as $line)
					{
						list($reg, $new, $comment, $notify) = explode("\t", $line);

						$reg = strtoupper(trim($reg));
						$new = strtoupper(trim($new));

						if (!$reg)
							$reg = $new;

						$notify = trim($notify);

						if ($notify)
							$CheckNotifTimes = true;

						$st->execute([
							"uid" => $uid,
							"reg" => $reg,
							"new" => $new,
							"comment" => $comment,
							"notify" => $notify,
						]);
					}
				}

				if (isset($_POST['add']))
				{
					$st = $db->prepare(<<<SQL
						/*[Q18]*/
						INSERT INTO `watchlist`(
							`user`,
							`reg`,
							`comment`,
							`notify`
						)
						VALUES(
							:uid,
							:reg,
							:comment,
							:notify
						)
						ON DUPLICATE KEY UPDATE
							`user` = :uid,
							`reg` = :reg,
							`comment` = :comment,
							`notify` = :notify

						SQL
					);

					$add = explode("\n", $_POST['add']);

					foreach ($add as $line)
					{
						list($reg, $comment, $notify) = explode("\t", $line);

						$reg = strtoupper(trim($reg));

						if ($reg)
						{
							$notify = trim($notify);

							if ($notify)
								$CheckNotifTimes = true;

							$st->execute([
								"uid" => $uid,
								"reg" => $reg,
								"comment" => $comment,
								"notify" => $notify,
							]);
						}
					}
				}

				if (isset($_POST['add']) ||
					isset($_POST['upd']))
				{
					if ($CheckNotifTimes)
					{
						if ($user->opt('notification-from') == $user->opt('notification-until'))
							$message = $STRINGS['notif-setinterval'];
					}
				}
			}
			catch (PDOException $ex)
			{
				$error = PDOErrorInfo($ex, $STRINGS['dberror']);
			}
		}
	}
}

?>
<script type="text/javascript">
	wl_img_open = "<?= Asset::src("img/wl-open-{$lang}.png") ?>";
	wl_img_close = "<?= Asset::src("img/wl-close-{$lang}.png") ?>";
</script>
<?php if ($user && (!$mobile || $tablet)) { ?>
<script type="text/javascript" src="<?= Asset::src('script/watchlist.js') ?>"></script>
<?php } ?>
<script type="text/javascript" src="<?= Asset::src('script/sorttable.js') ?>"></script>
<script type="text/javascript">
	$(function()
	{
		$(document).tooltip( { position: { my: "left top", at: "right top", collision: "flipfit" } } );
	});

	$(document).keydown(function(event)
	{
		if (27 == event.keyCode)
			watchlist("hide");
	});
</script>
<?php

if ($error)
{
?>
<div id="notification" class="error"><?= $error ?></div>
<?php
}
else
{
	if ($message)
	{
?>
<div id="notification" class="explain"><?= $message ?></div>
<?php
	}
}

/******************************************************************************
 * Runway direction
 ******************************************************************************/

$datadir = "$_SERVER[DOCUMENT_ROOT]/var/run/fra-flugplan";

$rwy = @parse_ini_file("$datadir/betriebsrichtung.ini");

$activerwy = [];

if (isset($rwy['07']))
	if ($rwy['07'] == 'active')
		$activerwy[] = '07';

if (isset($rwy['25']))
	if ($rwy['25'] == 'active')
		$activerwy[] = '25';

/* Used for testing... */
if (isset($rwy['99']))
	if ($rwy['99'] == 'active')
		$activerwy[] = '99';

if ($dir == 'departure')
{
	if (isset($rwy['18']))
		if ($rwy['18'] == 'active')
			$activerwy[] = '18';
}

asort($activerwy);
$activerwy = implode(" | ", $activerwy);

?>
<div id="rwy_cont">
	<div id="rwy_div">
		<div id="rwy_l">
			<img alt="<?= $STRINGS['rwydir'] ?>" width="16" height="14" src="<?= Asset::src("img/{$dir}-yellow-16x14.png") ?>">
		</div>
		<div id="rwy_r"><?= $activerwy ?></div>
	</div>
</div>
<?php

/******************************************************************************
 * Watchlist
 ******************************************************************************/

$watch = [];

if ($user)
{
	if ($db)
	{
		try
		{
			$st = $db->prepare(<<<SQL
				/*[Q19]*/
				SELECT
					`reg`,
					`comment`,
					`notify`
				FROM
					`watchlist`
				WHERE
					`user` = ?
				ORDER BY
					`reg`
				SQL
			);

			$st->execute([$user->id()]);

			while ($row = $st->fetchObject())
				$watch[$row->reg] =
				[
					"comment" => $row->comment,
					"notify" => $row->notify
				];
		}
		catch (PDOException $ex)
		{
			$error = PDOErrorInfo($ex, $STRINGS['dberror']);
		}
	}

	if (!$mobile || $tablet)
	{
?>
<div id="wl_cont">
	<div id="wl_div">
		<div id="wl_handle" class="cell top">
			<img id="wl_img" src="<?= Asset::src("img/wl-open-{$lang}.png") ?>" alt="watchlist">
		</div>
		<div class="cell top">
			<div id="expandable">
				<form id="watch" method="post" action="?">
					<div class="center">
						<div id="list">
							<table>
								<thead>
									<tr>
										<th></th>
										<th><?= $STRINGS['reg'] ?></th>
										<th><?= $STRINGS['comment'] ?></th>
										<th><a href="javascript:ToggleNotifications()"><img src="<?= Asset::src('img/mail.png') ?>" alt="e-mail"></a></th>
										<th></th>
										<th></th>
									</tr>
								</thead>
								<tbody>
<?php
		if (0 == count($watch))
		{
?>
									<tr>
										<!-- inputs do not have names, POST values will be generated upon submit -->
										<td><img src="<?= Asset::src('img/photodb-ina.png') ?>" alt="<?= $photodb ?>"></td>
										<td><input type="text" class="reg" value="" maxlength="31"></td>
										<td><input type="text" class="comment" value="" maxlength="255"></td>
										<td><input type="checkbox" class="notify" value=""></td>
										<td><button type="button" class="del" onclick="RemoveRow(this);">&nbsp;</button></td>
										<td><button type="button" class="add" onclick="CloneRow(this);">&nbsp;</button></td>
									</tr>
<?php
		}
	}

	foreach ($watch as $reg => $entry)
	{
		$comment = $entry['comment'];
		$notify = $entry['notify'];
		$watch[$reg] = $comment;

		if (!$mobile || $tablet)
		{
?>
									<tr>
										<td>
<?php		if (preg_match('/^\/.*\/$|[*?]/', $reg))
			{
?>
											<img src="<?= Asset::src('img/photodb-ina.png') ?>" alt="<?= $photodb ?>">
<?php
			}
			else
			{
?>
											<a href="<?= str_replace([ '&', '{reg}' ], [ '&amp;', $reg ], $URL[$photodb]) ?>" target="<?= $photodb ?>"><img src="<?= Asset::src('img/photodb.png') ?>" alt="<?= $photodb ?>"></a>
<?php
			}
?>
										</td>
										<td><input type="text" class="reg" value="<?= $reg ?>" maxlength="31"></td>
										<td><input type="text" class="comment" value="<?= htmlspecialchars($comment) ?>" maxlength="255"></td>
										<td><input type="checkbox" class="notify" value=""<?= $notify ? " checked" : "" ?>></td>
										<td><button type="button" class="del" onclick="RemoveRow(this);">&nbsp;</button></td>
										<td><button type="button" class="add" onclick="CloneRow(this);">&nbsp;</button></td>
									</tr>
<?php
		}
	}

	if (!$mobile || $tablet)
	{
?>
								</tbody>
							</table>
						</div>
						<input type="hidden" name="CSRFToken" value="<?= CsrfToken::get() ?>">
						<input type="submit" value="<?= $STRINGS['refresh'] ?>">
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php
	}
}

if ($error)
{
?>
<div id="notification" class="error">
	<?= $error ?>
</div>
<?php
}
?>
<div id="schedule">
	<table class="sortable">
		<thead>
			<tr>
				<th><?= $STRINGS['time'] ?>
				<th class="sep"><?= $STRINGS['flight'] ?>
<?php
				if (!$mobile || $tablet)
				{
?>
				<th class="sep"><?= $STRINGS['airline'] ?>
				<th class="sep">IATA
				<th class="sep">ICAO
				<th class="sep"><?= ucfirst($dir == 'arrival'  ? $STRINGS['from'] : $STRINGS['to']) ?>
<?php
				}
?>
				<th class="sep sorttable_model"><?= $STRINGS['type'] ?>
				<th class="sep sorttable_reg"><?= $STRINGS['reg'] ?>
			</tr>
		</thead>
		<tbody>
<?php

$watch['wildcards'] = [];

foreach ($watch as $reg => $comment)
{
	if ($reg != 'wildcards')
	{
		if (preg_match('/^\/.*\/$|[*?]/', $reg))
		{
			$watch['wildcards'][$reg] = $comment;
			unset($watch[$reg]);
		}
	}
}

// Make sure we use the correct timezone
$tz = date_default_timezone_set('Europe/Berlin');
$now = new StdClass();

if (isset($_GET['time']))
{
	$now->iso = $_GET['time'];
	$now->unix = strtotime($now->iso);
}
else
{
	$now->iso = date(DATE_ISO8601);
	$now->unix = time();
}

if (!$user)
{
	$lookback = 0;
	$lookahead = 7 * 24 * 3600;	// +7d
}
else
{
	if ($mobile)
	{
		$lookback = $user->opt('tm-');
		$lookahead = $user->opt('tm+');
	}
	else
	{
		if ($tablet)
		{
			$lookback = $user->opt('tt-');
			$lookahead = $user->opt('tt+');
		}
		else
		{
			$lookback = 0;
			$lookahead = 7 * 24 * 3600;	// +7d
		}
	}
}

$from = $now->unix + $lookback;
$until = $now->unix + $lookahead;

/* This might be configurable in the future... */
/* Variable: */
$columns = <<<EOF
	`type`,
	`airlines`.`name` AS `airline`,
	`airports`.`iata` AS `airport_iata`,
	`airports`.`icao` AS `airport_icao`,
	`airports`.`name` AS `airport_name`,
	EOF;

$columns .= sprintf('`countries`.`%s` AS `country`,', 'de');	//$STRINGS['$id']);

$join = 'LEFT JOIN `countries` ON `airports`.`country` = `countries`.`id`';

/* Fixed: */
$columns .= <<<EOF
	`expected`,
	CASE
		WHEN `expected` < `scheduled` THEN -1
		WHEN `expected` > `scheduled` THEN 1
		ELSE 0 end AS `timediff`,
	`airlines`.`code` AS `fl_airl`,
	`flights`.`code` AS `fl_code`,
	`models`.`icao` AS `model`,
	`aircrafts`.`reg` AS `reg`,
	`visits`.`num` AS `vtf`
	EOF;

$query = <<<EOF
	/*[Q7]*/
	SELECT $columns
	FROM `flights`
		LEFT JOIN `airlines` ON `flights`.`airline` = `airlines`.`id`
		LEFT JOIN `airports` ON `flights`.`airport` = `airports`.`id`
		LEFT JOIN `models` ON `flights`.`model` = `models`.`id`
		LEFT JOIN `aircrafts` ON `flights`.`aircraft` = `aircrafts`.`id`
		LEFT JOIN `visits` ON `flights`.`aircraft` = `visits`.`aircraft`
		$join
	WHERE
		`flights`.`direction` = :dir AND
		`expected` BETWEEN FROM_UNIXTIME(:from) AND FROM_UNIXTIME(:until)
	ORDER BY
		`expected` ASC, `airlines`.`code`, `flights`.`code`;
	EOF;

if ($db)
{
	try
	{
		$st = $db->prepare($query);

		$st->execute([
			"dir" => $dir,
			"from" => $from,
			"until" => $until,
		]);

		while ($row = $st->fetchObject())
		{
			if (strtotime($row->expected) - strtotime($now->iso) < 0)
				echo '<tr class="past">';
			else
				echo '<tr>';

			/* Calculate day offset, considering that when dst changes,
			 * one week is 604800 +/- 3600 ... */
			$t_expected = strtotime(substr($row->expected, 0, 10));
			$t_now = strtotime(substr($now->iso, 0, 10));
			$diff = 0;

			$tm = localtime($t_expected, true);

			if ($tm['tm_isdst'])
				$diff -= 3600;

			$tm = localtime($t_now, true);

			if ($tm['tm_isdst'])
				$diff += 3600;

			$diff = $t_expected - $t_now - $diff;
			$day = (int)($diff / 24 / 60 / 60);

			/* $day should always be >= 0 ... */
			if ($day >= 0)
				$day = '+'.$day;

			$early = $row->timediff < 0 ? ' class="early"' : '';
			$hhmm = substr($row->expected, 11, 5);

			/* <td> inherits 'class="left"' from div.box */
			echo "<td$early>$day $hhmm</td>";
			echo "<td>{$row->fl_airl}{$row->fl_code}</td>";

			if (!$mobile)
			{
				echo "<td><div>{$row->airline}</div></td>";
				echo "<td>{$row->airport_iata}</td>";
				echo "<td>{$row->airport_icao}</td>";

				if (0 == strlen($row->airport_name))
				{
					echo "<td><div>&nbsp;</div></td>";
				}
				else
				{
					if (0 == strlen($row->country))
						echo "<td><div>{$row->airport_name}</div></td>";
					else
						echo "<td><div>{$row->airport_name}, {$row->country}</div></td>";
				}
			}

			switch ($row->type)
			{
			case 'C':
				echo "<td class=\"model cargo\">{$row->model}</td>";
				break;

			case 'F':
				echo "<td class=\"model\">{$row->model}</td>";
				break;

			default:
				echo "<td class=\"model\">{$row->model}</td>";
			}

			$reg = $row->reg;
			$vtf = $row->vtf ? $row->vtf : '9999';
			$hilite = null;

			if (0 == strlen($reg))
			{
			}
			else
			{
				$hhmm = substr(str_replace([' ', '.', ':', '-'], '', $row->expected), 8, 4);

				if (isset($watch[$reg]))
				{
					$hilite = sprintf(' class="watch" title="%s"', htmlspecialchars($watch[$reg]));
				}
				else
				{
					if (isset($watch['wildcards']))
					{
						foreach ($watch['wildcards'] as $key => $comment)
						{
							if (preg_match('/^\/.*\/$/', $key))
							{
								/* Regex */
								if (preg_match($key, $reg))
								{
									$hilite = sprintf(' class="watch" title="%s"', htmlspecialchars($comment));
									break;
								}
							}
							else
							{
								if (fnmatch($key, $reg))
								{
									/* Wildcard */
									$hilite = sprintf(' class="watch" title="%s"', htmlspecialchars($comment));
									break;
								}
							}
						}
					}

					if (!$hilite)
					{
						if ($vtf < 10)
						{
							$vtf = ordinal($vtf, $lang);
							$hilite = sprintf(' class="rare" title="%s"', htmlspecialchars("$vtf$STRINGS[vtf]"));
						}
					}
				}
			}

			$href = null;

			if (!$reg)
			{
				echo "<td>";
			}
			else
			{
				echo "<td$hilite>";

				if ($mobile)
				{
				}
				else
				{
?>
				<a href="<?= str_replace( ['&', '{reg}' ], [ '&amp;', $reg ], $URL[$photodb]) ?>" target="<?= $photodb ?>">
					<img src="<?= Asset::src('img/photodb.png') ?>" alt="<?= $photodb ?>">
				</a>
<?php
				}

				echo "$reg";
			}

			echo "</td></tr>\n";
		}
?>
<?php
	}
	catch (PDOException $ex)
	{
		$error = PDOErrorInfo($ex, $STRINGS['dberror']);
	}
}
?>
		</tbody>
	</table>
</div>
<script type="text/javascript" src="<?= Asset::src('script/sortable.js') ?>"></script>
