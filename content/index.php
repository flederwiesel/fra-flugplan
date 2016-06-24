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
		if ($hdbc)
		{
			if (!mysql_query('SET AUTOCOMMIT=0'))
			{
				$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
			}
			else
			{
				if (!mysql_query('START TRANSACTION'))
				{
					$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
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
												AND `reg`='$reg') AS `watchlist`
									        ON `watchlist`.`id`=`watchlist-notifications`.`watch`
SQL;

								if (!mysql_query($query))
								{
									$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
									break;
								}
								else
								{
									$query = <<<SQL
										DELETE FROM `watchlist`
										WHERE `user`=$uid
											AND `reg`='$reg'
SQL;

									if (!mysql_query($query))
									{
										$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
										break;
									}
								}
							}
						}
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

									if (!get_magic_quotes_gpc())
									{
										// escape backslashes and single quotes
										$reg     = str_replace("\\", "", $reg);
										$reg     = str_replace("'", "", $reg);
										$comment = str_replace("\\", "\\\\", $comment);
										$comment = str_replace("'", "\\'", $comment);
									}

									$query = <<<SQL
										INSERT INTO `watchlist`(`user`,`reg`,`comment`, `notify`)
										VALUES($uid, '$reg', '$comment', $notify)
										ON DUPLICATE KEY UPDATE `comment`='$comment', `notify`=$notify
SQL;

									if (!mysql_query($query))
									{
										$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
										break;
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

					if ($error)
					{
						if (!mysql_query('ROLLBACK'))
							$error .= sprintf("\n%s(%u): %s".__FILE__, __LINE__, mysql_error());
					}
					else
					{
						if (!mysql_query('COMMIT'))
							$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
					}
				}
			}
		}
	}
}

?>
<!--[if IE]>
<style type="text/css">
#list { padding-right: 1.2em; }
</style>
<![endif]-->
<style type="text/css">
/* jQuery UI Tooltip 1.10.3 */
.ui-tooltip
{
	z-index: 9999;
	position: absolute;
	padding: 0.1em 0.2em;
	max-width: 300px;
	background: #FFFFE1;
	-webkit-box-shadow: 0 0 2px #AAA;
	box-shadow: 0 0 2px #AAA;
	border-radius: 0;
	font-size: 0.85em;
}
body .ui-tooltip {
	border: 1px solid black;
}
</style>
<script type="text/javascript">
	wl_img_open = "img/wl-open-<?php echo $_SESSION['lang']; ?>.png";
	wl_img_close = "img/wl-close-<?php echo $_SESSION['lang']; ?>.png";
</script>
<?php if ($user && (!$mobile || $tablet)) { ?>
<script type="text/javascript" src="script/watchlist.js"></script>
<?php } ?>
<script type="text/javascript" src="script/sorttable.js"></script>
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

$datadir = "$_SERVER[DOCUMENT_ROOT]/var/run/fra-schedule";

$file = @file("$datadir/betriebsrichtung.html");

if (!$file)
{
	$rwydir = '??';
}
else
{
	$rwydir = '';
	$oneeight = 0;
	$fmt = '<span>%s</span>';

	foreach ($file as $line)
	{
		if (strstr($line, "Betriebsrichtung"))
		{
			if (preg_match("/<b>[ \t]*(07|25|99)[ \t]*/", $line, $match))
				$rwydir = $match[1];
		}
		else
		{
			if (strstr($line, "18 West"))
				$oneeight = strstr($line, "in Betrieb") ? 1 : 0;
		}
	}

	if (strlen($rwydir))
	{
		if ('departure' == $dir)
		{
			if ('07' == $rwydir)
				$rwydir = sprintf($fmt, $rwydir.($oneeight ? ' | 18' : ''));
			else
				$rwydir = sprintf($fmt, ($oneeight ? '18 | ' : '').$rwydir);
		}
	}
}

?>
<div id="rwy_cont">
	<div id="rwy_div" style="float: <?php echo 'arrival' == $dir ? 'left' : 'right'; ?> ;">
		<span id="rwy_l" style="vertical-align: middle;">
			<img alt="<?php echo $lang['rwydir']; ?>" src="img/<?php echo $dir; ?>-yellow-16x14.png">
		</span>
		<span id="rwy_r"><?php echo $rwydir; ?></span>
	</div>
</div>
<?php
/******************************************************************************
 * Watchlist
 ******************************************************************************/

$watch = array();

if ($user)
{
	if ($hdbc)
	{
		$result = mysql_query("SELECT `reg`, `comment`, `notify` ".
							  "FROM `watchlist`".
							  " WHERE `user`=".$user->id().
							  " ORDER BY `reg`");

		if (!$result)
		{
			$error .= sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
		}
		else
		{
			while (list($reg, $comment, $notify) = mysql_fetch_row($result))
				$watch[$reg] = array('comment' => $comment, 'notify' => $notify);

			mysql_free_result($result);
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
							<table summary="watchlist">
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
								<tfoot></tfoot>
								<tbody>
<?php
		if (0 == count($watch))
		{
?>
									<tr>
										<!-- inputs do not have names, POST values will be generated upon submit -->
										<td><img src="img/a-net-ina.png" alt=""></td>
										<td class="reg"><input type="text" value="" maxlength="31"></td>
										<td class="comment"><input type="text" value="" maxlength="255"></td>
										<td class="notify"><input type="checkbox" value=""></td>
										<td class="button"><input type="button" class="del" onclick="RemoveRow(this);"></td>
										<td class="button"><input type="button" class="add" onclick="CloneRow(this);"></td>
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
											<img src="img/a-net-ina.png" alt="">
<?php
			}
			else
			{
?>
											<a href="http://www.airliners.net/search?keywords=<?php echo $reg; ?>&sortBy=datePhotographedYear&sortOrder=desc" target="a-net"><img src="img/a-net.png" alt="www.airliners.net"></a>
<?php
			}
?>
										</td>
										<td class="reg"><input type="text" value="<?php echo $reg; ?>" maxlength="31"></td>
										<td class="comment"><input type="text" value="<?php echo htmlspecialchars($comment); ?>" maxlength="255"></td>
										<td class="notify"><input type="checkbox" value=""<?php if ($notify) echo " checked"; ?>></td>
										<td class="button"><input type="button" class="del" onclick="RemoveRow(this);"></td>
										<td class="button"><input type="button" class="add" onclick="CloneRow(this);"></td>
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
	<table class="sortable" summary="schedule">
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
				<th class="sep"><?php echo $lang['type']; ?>
				<th class="sep sorttable_alpha"><?php echo $lang['reg']; ?>
			</tr>
		</thead>
		<tfoot></tfoot>
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
	WHERE `flights`.`direction`='$dir'
	 AND TIMESTAMPDIFF(SECOND, '$now', IFNULL(`expected`, `scheduled`)) >= $lookback
	 AND TIMESTAMPDIFF(SECOND, '$now', IFNULL(`expected`, `scheduled`)) <= $lookahead
	ORDER BY `expected` ASC, `airlines`.`code`, `flights`.`code`;
EOF;

$result = mysql_query($query);

if (!$result)
{
	$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
}
else
{
	while ($row = mysql_fetch_assoc($result))
	{
		if (strtotime($row['expected']) - strtotime($now) < 0)
			echo '<tr class="past">';
		else
			echo '<tr>';

		/* Calculate day offset, considering that when dst changes,
		 * one week is 604800 +/- 3600 ... */
		$t_expected = strtotime(substr($row['expected'], 0, 10));
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

		$early = $row['timediff'] < 0 ? ' class="early"' : '';
		$hhmm = substr($row['expected'], 11, 5);

		/* <td> inherits 'class="left"' from div.box */
		echo "<td$early>$day $hhmm</td>";
		echo "<td>$row[fl_airl]$row[fl_code]</td>";

		if (!$mobile)
		{
			echo "<td><div>$row[airline]</div></td>";
			echo "<td>$row[airport_iata]</td>";
			echo "<td>$row[airport_icao]</td>";
			echo "<td><div>$row[airport_name]</div></td>";
		}

		switch ($row['type'])
		{
		case 'C':
			echo "<td class=\"model cargo\" sorttable_customkey=\"-1:$row[model]\">$row[model]</td>";
			break;

		case 'F':
			echo "<td class=\"model\" sorttable_customkey=\"0:$row[model]\">$row[model]</td>";
			break;

		default:
			echo "<td class=\"model\" sorttable_customkey=\"0:$row[model]\">$row[model]</td>";
		}

		$reg = $row['reg'];
		$vtf = $row['vtf'] ? $row['vtf'] : '9999';
		$hilite = NULL;

		if (0 == strlen($reg))
		{
			$sortkey = ' sorttable_customkey="2"';
		}
		else
		{
			$sortkey = ' sorttable_customkey="%"';
			$hhmm = substr(str_replace(array(' ', '.', ':', '-'), '', $row['expected']), 8, 4);

			if (isset($watch[$reg]))
			{
				$hilite = sprintf(' class="watch" title="%s"', htmlspecialchars($watch[$reg]));
				$sortkey = str_replace('%', '0:'.$reg.$day.$hhmm, $sortkey);
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
								$sortkey = str_replace('%', '0:'.$reg.$day.$hhmm, $sortkey);
								break;
							}
						}
						else
						{
							if (fnmatch($key, $reg))
							{
								/* Wildcard */
								$hilite = sprintf(' class="watch" title="%s"', htmlspecialchars($comment));
								$sortkey = str_replace('%', '0:'.$reg.$day.$hhmm, $sortkey);
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
						$sortkey = str_replace('%', '0:'.$reg.$day.$hhmm, $sortkey);
					}
					else
					{
						$sortkey = str_replace('%', '1:'.$reg.$day.$hhmm, $sortkey);
					}
				}
			}
		}

		$href = NULL;

		if (!$reg)
		{
			echo "<td$sortkey>";
		}
		else
		{
			echo "<td$hilite$sortkey>";

			if ($mobile)
			{
			}
			else
			{
?>
				<a href = "http://www.airliners.net/search?keywords=<?php echo $reg ?>&sortBy=datePhotographedYear&sortOrder=desc" target="a-net">
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
	mysql_free_result($result);
}
?>
		</tbody>
	</table>
</div>
