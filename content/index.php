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

$error = NULL;
$message = NULL;

/* Update watchlist from posted values */
if (isset($_POST['add']) ||
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
			{
				if (!$db->beginTransaction())
				{
					$error = sprintf($lang['dberror'], $db->errorCode());
				}
				else
				{
					$uid = $user->id();

					if (isset($_POST['del']))
					{
						$del = explode("\n", $_POST['del']);

						foreach ($del as $reg)
						{
							if (strlen($reg))
							{
								$reg = strtoupper(trim($reg));

								if (!get_magic_quotes_gpc())
								{
									// escape backslashes and single quotes
									$reg = str_replace("\\", "", $reg);
									$reg = str_replace("'", "", $reg);
								}

								$query = <<<SQL
									DELETE `watchlist-notifications`
									FROM `watchlist-notifications`
									INNER JOIN (SELECT `id` FROM `watchlist`
												WHERE `user`=$uid
												AND `reg`=?) AS `watchlist`
									        ON `watchlist`.`id`=`watchlist-notifications`.`watch`
SQL;

								$st = $db->prepare($query);

								if (!$st)
								{
									$error = sprintf($lang['dberror'], $db->errorCode());
								}
								else
								{
									if (!$st->execute(array($reg)))
									{
										$error = sprintf($lang['dberror'], $st->errorCode());
									}
									else
									{
										$query = <<<SQL
											DELETE FROM `watchlist`
											WHERE `user`=$uid
												AND `reg`=?
SQL;

										$st = $db->prepare($query);

										if (!$st)
										{
											$error = sprintf($lang['dberror'], $db->errorCode());
										}
										else
										{
											if (!$st->execute(array($reg)))
												$error = sprintf($lang['dberror'], $st->errorCode());
										}
									}
								}
							}
						}

						if ($error)
							break;
					}

					if (!$error)
					{
						if (isset($_POST['add']))
						{
							$notif_req = FALSE;

							$add = explode("\n", $_POST['add']);

							foreach ($add as $line)
							{
								list($reg, $comment, $notify) = explode("\t", $line);

								if (strlen($reg))
								{
									$reg = strtoupper(trim($reg));
									$notify = trim($notify);

									$watch[$reg] = $comment;

									if ($notify)
										$notif_req = TRUE;

									$query = <<<SQL
										INSERT INTO `watchlist`(`user`,`reg`,`comment`, `notify`)
										VALUES($uid, :reg, :comment, :notify)
										ON DUPLICATE KEY UPDATE `comment`=:comment, `notify`=:notify
SQL;

									$st = $db->prepare($query);

									if (!$st)
									{
										$error = sprintf($lang['dberror'], $db->errorCode());
									}
									else
									{
										$st->bindValue(':reg', $reg);
										$st->bindValue(':comment', $comment);
										$st->bindValue(':notify', $notify);

										if (!$st->execute())
											$error = sprintf($lang['dberror'], $st->errorCode());
									}
								}
							}

							if (!$error)
							{
								if ($notif_req)
								{
									if ($user->opt('notification-from') == $user->opt('notification-until'))
										$message = $lang['notif-setinterval'];
								}
							}
						}
					}

					// TODO: This can be handled mor smartly using exceptions
					if ($error)
					{
						if (!$db->rollBack())
							$error = sprintf($lang['dberror'], $db->errorCode());
					}
					else
					{
						if (!$db->commit())
							$error = sprintf($lang['dberror'], $db->errorCode());
					}
				}
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
			$datadir = "$_SERVER[DOCUMENT_ROOT]/var/run/fra-schedule";

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

$watch = array();

if ($user)
{
	if ($db)
	{
		$query = <<<SQL
			SELECT `reg`, `comment`, `notify`
			FROM `watchlist`
			WHERE `user`=?
			ORDER BY `reg`
SQL;

		$st = $db->prepare($query);

		if (!$st)
		{
			$error = sprintf($lang['dberror'], $db->errorCode());
		}
		else
		{
			if (!$st->execute(array($user->id())))
			{
				$error = sprintf($lang['dberror'], $st->errorCode());
			}
			else
			{
				while ($row = $st->fetch(PDO::FETCH_OBJ))
					$watch[$row->reg] = array('comment' => $row->comment, 'notify' => $row->notify);
			}
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
										<td><img src="img/a-net-ina.png" alt="www.airliners.net"></td>
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
											<img src="img/a-net-ina.png" alt="www.airliners.net">
<?php
			}
			else
			{
?>
											<a href="http://www.airliners.net/search?keywords=<?php echo $reg; ?>&amp;sortBy=datePhotographedYear&amp;sortOrder=desc" target="a-net"><img src="img/a-net.png" alt="www.airliners.net"></a>
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
		$lookahead = 7 * 24 * 3600;	// +24h
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

$watch['wildcards'] = array();

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

if (isset($_GET['time']))
	$now = $_GET['time'];
else
	$now = date(DATE_ISO8601);

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
	IFNULL(`expected`,`scheduled`) AS `expected`,
	 CASE
	  WHEN `expected` IS NULL THEN 0
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
	SELECT $columns
	FROM `flights`
	 LEFT JOIN `airlines` ON `flights`.`airline` = `airlines`.`id`
	 LEFT JOIN `airports` ON `flights`.`airport` = `airports`.`id`
	 LEFT JOIN `models` ON `flights`.`model` = `models`.`id`
	 LEFT JOIN `aircrafts` ON `flights`.`aircraft` = `aircrafts`.`id`
	 LEFT JOIN `visits` ON `flights`.`aircraft` = `visits`.`aircraft`
	 $join
	WHERE `flights`.`direction`='$dir'
	 AND TIMESTAMPDIFF(SECOND, '$now', IFNULL(`expected`, `scheduled`)) >= $lookback
	 AND TIMESTAMPDIFF(SECOND, '$now', IFNULL(`expected`, `scheduled`)) <= $lookahead
	ORDER BY `expected` ASC, `airlines`.`code`, `flights`.`code`;
EOF;

if ($db)
{
	$st = $db->query($query);

	if (!$st)
	{
		$error = sprintf($lang['dberror'], $db->errorCode());
	}
	else
	{
		if (!$st->execute())
		{
			$error = sprintf($lang['dberror'], $st->errorCode());
		}
		else
		{
			while ($row = $st->fetchObject())
			{
				if (strtotime($row->expected) - strtotime($now) < 0)
					echo '<tr class="past">';
				else
					echo '<tr>';

				/* Calculate day offset, considering that when dst changes,
				 * one week is 604800 +/- 3600 ... */
				$t_expected = strtotime(substr($row->expected, 0, 10));
				$t_now = strtotime(substr($now, 0, 10));
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
				$hilite = NULL;

				if (0 == strlen($reg))
				{
				}
				else
				{
					$hhmm = substr(str_replace(array(' ', '.', ':', '-'), '', $row->expected), 8, 4);

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

				$href = NULL;

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
				<a href = "http://www.airliners.net/search?keywords=<?php echo $reg ?>&amp;sortBy=datePhotographedYear&amp;sortOrder=desc" target="a-net">
					<img src="img/a-net.png" alt="www.airliners.net">
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
	}
}
?>
		</tbody>
	</table>
</div>
<script type="text/javascript" src="script/sortable.js<?php rev(); ?>"></script>
