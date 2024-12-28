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
		$error = $lang['unexpected'];
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
							$message = $lang['notif-setinterval'];
					}
				}
			}
			catch (PDOException $ex)
			{
				$error = PDOErrorInfo($ex, $lang['dberror']);
			}
		}
	}
}

?>
<script type="text/javascript">
	wl_img_open = "img/wl-open-<?php echo $_SESSION['lang']; ?>.png";
	wl_img_close = "img/wl-close-<?php echo $_SESSION['lang']; ?>.png";
</script>
<?php if ($user && (!$mobile || $tablet)) { ?>
<script type="text/javascript" src="script/watchlist.js<?php rev(); ?>"></script>
<?php } ?>
<script type="text/javascript" src="script/sorttable.js<?php rev(); ?>"></script>
<script type="text/javascript">
	$(document).ready(function(){
	});

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
<div id="notification" class="error"><?php echo $error; ?></div>
<?php
}
else
{
	if ($message)
	{
?>
<div id="notification" class="explain"><?php echo $message; ?></div>
<?php
	}
}

/******************************************************************************
 * Runway direction
 ******************************************************************************/

?>
<div id="rwy_cont">
	<div id="rwy_div" style="float: <?php echo 'arrival' == $dir ? 'left' : 'right'; ?> ;">
		<span id="rwy_l" style="vertical-align: middle;">
			<img alt="<?php echo $lang['rwydir']; ?>" src="img/<?php echo $dir; ?>-yellow-16x14.png">
		</span>
		<span id="rwy_r"><?php
			$datadir = "$_SERVER[DOCUMENT_ROOT]/var/run/fra-flugplan";

			$rwy = @parse_ini_file("$datadir/betriebsrichtung.ini");

			if (isset($rwy['07']))
				echo "07";

			if ('departure' == $dir)
			{
				if (isset($rwy['18']))
				{
					if (isset($rwy['07']))
						echo " | ";

					echo "18";
				}
			}

			if (isset($rwy['25']))
			{
				if ('departure' == $dir)
					if (isset($rwy['18']))
						echo " | ";

				echo "25";
			}

			/* used whilst testing... */
			if (isset($rwy['99']))
			{
				if ('departure' == $dir)
					if (isset($rwy['18']))
						echo " | ";

				echo "99";
			}
		?></span>
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
			$error = PDOErrorInfo($ex, $lang['dberror']);
		}
	}

	if (!$mobile || $tablet)
	{
?>
<div id="wl_cont">
	<div id="wl_div">
		<div id="wl_handle" class="cell top">
			<img id="wl_img" src="img/wl-open-<?php echo $_SESSION['lang']; ?>.png" alt="watchlist">
		</div>
		<div class="cell top">
			<div id="expandable" style="width: 0; visibility: hidden;">
				<form id="watch" method="post" action="?">
					<div class="center" style="padding: 6px 6px 6px 0;">
						<div id="list">
							<table>
								<thead>
									<tr>
										<th></th>
										<th><?php echo $lang['reg']; ?></th>
										<th><?php echo $lang['comment']; ?></th>
										<th><a href="javascript:ToggleNotifications()"><img src="img/mail.png" alt="e-mail"></a></th>
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
										<td><img src="img/photodb-ina.png" alt="<?php echo "$photodb"; ?>"></td>
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
											<img src="img/photodb-ina.png" alt="<?php echo "$photodb"; ?>">
<?php
			}
			else
			{
?>
											<a href="<?php echo str_replace([ '&', '{reg}' ], [ '&amp;', "$reg" ], $URL["$photodb"]); ?>" target="<?php echo "$photodb"; ?>"><img src="img/photodb.png" alt="<?php echo "$photodb"; ?>"></a>
<?php
			}
?>
										</td>
										<td><input type="text" class="reg" value="<?php echo $reg; ?>" maxlength="31"></td>
										<td><input type="text" class="comment" value="<?php echo htmlspecialchars($comment); ?>" maxlength="255"></td>
										<td><input type="checkbox" class="notify" value=""<?php if ($notify) echo " checked"; ?>></td>
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
						<input type="submit" value="<?php echo $lang['refresh']; ?>"
						 style="margin: 0.5em 0;">
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
	<?php echo $error; ?>
</div>
<?php
}
?>
<div id="schedule">
	<table class="sortable">
		<thead>
			<tr>
				<th><?php echo $lang['time']; ?>
				<th class="sep"><?php echo $lang['flight']; ?>
<?php
				if (!$mobile || $tablet)
				{
?>
				<th class="sep"><?php echo $lang['airline']; ?>
				<th class="sep">IATA
				<th class="sep">ICAO
				<th class="sep"><?php echo ucfirst('arrival' == $dir ? $lang['from'] : $lang['to']); ?>
<?php
				}
?>
				<th class="sep sorttable_model"><?php echo $lang['type']; ?>
				<th class="sep sorttable_reg"><?php echo $lang['reg']; ?>
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
	$lookahead = ($mobile && !$tablet ? 1 : 7 * 24) * 3600;	// +24h
}
else
{
	if (!$mobile)
	{
		$lookback = 0;
		$lookahead = 7 * 24 * 3600;	// +7d
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
			$lookback = $user->opt('tm-');
			$lookahead = $user->opt('tm+');
		}
	}
}

$from = $now->unix - $lookback;
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

$columns .= sprintf('`countries`.`%s` AS `country`,', 'de');	//$lang['$id']);

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
							$vtf = ordinal($vtf, $_SESSION['lang']);
							$hilite = sprintf(' class="rare" title="%s"', htmlspecialchars("$vtf$lang[vtf]"));
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
				<a href="<?php echo str_replace([ '&', '{reg}' ], [ '&amp;', "$reg" ], $URL["$photodb"]); ?>" target="<?php echo "$photodb"; ?>">
					<img src="img/photodb.png" alt="<?php echo "$photodb"; ?>">
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
		$error = PDOErrorInfo($ex, $lang['dberror']);
	}
}
?>
		</tbody>
	</table>
</div>
<script type="text/javascript" src="script/sortable.js<?php rev(); ?>"></script>
