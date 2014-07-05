<?php

/******************************************************************************
 *
 *       project: FRA-flights Live Schedule
 *
 *       $Author$
 *         $Date$
 *          $Rev$
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

$error = NULL;
$message = NULL;

function ordinal($number, $lang)
{
	if ('en' == $lang)
	{
		$suffix = array('th','st','nd','rd','th','th','th','th','th','th');

		if (($number % 100) >= 11 && ($number % 100) <= 13)
			$ordinal = "${number}th";
		else
			$ordinal = "${number}".$suffix[$number % 10];
	}
	else
	{
		$ordinal = "${number}.";
	}

	return $ordinal;
}

/* Update watchlist from posted values */
if (isset($_POST['del']) ||
	isset($_POST['reg']))
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
					foreach ($_POST['del'] as $reg => $comment)
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

					if (!$error)
					{
						if (isset($_POST['reg']))
						{
							$notif_req = 0;

							if (isset($_POST['notify']))
								$notifications = $_POST['notify'];

							foreach ($_POST['reg'] as $reg => $comment)
							{
								$watch[$reg] = $comment;
								$reg = strtoupper(trim($reg));

								if (!get_magic_quotes_gpc())
								{
									// escape backslashes and single quotes
									$reg     = str_replace("\\", "", $reg);
									$reg     = str_replace("'", "", $reg);
									$comment = str_replace("\\", "\\\\", $comment);
									$comment = str_replace("'", "\\'", $comment);
								}

								if (isset($notifications[$reg]))
								{
									$notif_req = TRUE;
									$notify = 'TRUE';
								}
								else
								{
									$notify = 'FALSE';
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

	$(document).keydown(function(event)
	{
		if (27 == event.keyCode)
			watchlist("hide");
	});

	$(function()
	{
		$("#watch").submit(function(event) {
			$("#submit").attr("disabled", "disabled");
			PreparePostData(this);
		});
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

$file = @file("data/betriebsrichtung.html");

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
				<form id="watch" method="post" action="?"
					onsubmit="document.getElementById('submit').disabled=true;">
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
										<td><img src="img/a-net-ina.png" alt=""></td>
										<td class="reg"><input type="text" value=""></td>
										<td class="comment"><input type="text" value=""></td>
										<td class="comment"><input type="checkbox" value=""></td>
										<td class="button"><input type="button" class="del" onclick="RemoveRow(this);"></td>
										<td class="button"><input type="button" class="add" onclick="CloneRow(this);"></td>
									</tr>
<?php
		}
		else
		{
			 //+while (list($reg, $comment, $notify) = each($watch))
			 //-foreach ($watch as list($reg, $comment, $notify))
			 foreach ($watch as $reg => $entry)
			 {
			 	 $comment = $entry['comment'];
			 	 $notify = $entry['notify'];
			 	 $watch[$reg] = $comment;
?>
									<tr>
										<td><a href="http://www.airliners.net/search/photo.search?q=<?php echo $reg; ?>&sort_order=year+desc" target="a-net"><img src="img/a-net.png" alt="www.airliners.net"></a></td>
										<td class="reg"><input type="text" value="<?php echo $reg; ?>"></td>
										<td class="comment"><input type="text" value="<?php echo htmlspecialchars($comment); ?>"></td>
										<td class="comment"><input type="checkbox" value=""<?php if ($notify) echo " checked"; ?>></td>
										<td class="button"><input type="button" class="del" onclick="RemoveRow(this);"></td>
										<td class="button"><input type="button" class="add" onclick="CloneRow(this);"></td>
									</tr>
<?php
			 }
		}
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
				<th class="sep"><?php echo 'arrival' == $dir ? $lang['from'] : $lang['to']; ?>
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
		if (strpbrk($reg, "?*"))
		{
			$watch['wildcards'][$reg] = $comment;
			unset($watch[$reg]);
		}
	}
}

// Make sure we use the correct timezone
$tz = date_default_timezone_set('Europe/Berlin');

if (isset($_GET['now']))
	$now = $_GET['now'];
else
	$now = strftime('%Y-%m-%d %H:%M:%S');

$query = "SELECT `type`,".
	" IFNULL(`expected`,`scheduled`) AS `expected`,".
	"  CASE".
	"   WHEN `expected` IS NULL THEN 0".
	"   WHEN `expected` < `scheduled` THEN -1".
	"   WHEN `expected` > `scheduled` THEN 1".
	"   ELSE 0 end AS `timediff`,".
	" `airlines`.`code` AS `fl_airl`,".
	" `flights`.`code` AS `fl_code`,".
	($mobile ? "" :
		" `airlines`.`name` AS `airline`,".
		" `airports`.`iata` AS `airport_iata`,".
		" `airports`.`icao` AS `airport_icao`,".
		" `airports`.`name` AS `airport_name`,").
	" `models`.`icao` AS `model`,".
	" `aircrafts`.`reg` AS `reg`, ".
	" `visits`.`num` AS `vtf` ".
	"FROM `flights`".
	" LEFT JOIN `airlines` ON `flights`.`airline` = `airlines`.`id` ".
	" LEFT JOIN `airports` ON `flights`.`airport` = `airports`.`id` ".
	" LEFT JOIN `models` ON `flights`.`model` = `models`.`id` ".
	" LEFT JOIN `aircrafts` ON `flights`.`aircraft` = `aircrafts`.`id` ".
	" LEFT JOIN `visits` ON `flights`.`aircraft` = `visits`.`aircraft` ".
	"WHERE `flights`.`direction`='$dir'".
	" AND TIME_TO_SEC(TIMEDIFF(IFNULL(`expected`, `scheduled`), '$now')) >= $lookback ".
	" AND TIME_TO_SEC(TIMEDIFF(IFNULL(`expected`, `scheduled`), '$now')) <= $lookahead ".
	"ORDER BY `expected` ASC, `airlines`.`code`, `flights`.`code`";

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

		switch ($row['type'])
		{
		case 'cargo':
		case 'ferry':
			echo "<td><img src='img/$row[type].png'>$row[fl_airl]$row[fl_code]</td>";
			break;

		default:
			echo "<td>$row[fl_airl]$row[fl_code]</td>";
		}

		if (!$mobile)
		{
			echo "<td><div>$row[airline]</div></td>";
			echo "<td>$row[airport_iata]</td>";
			echo "<td>$row[airport_icao]</td>";
			echo "<td><div>$row[airport_name]</div></td>";
		}

		echo "<td>$row[model]</td>";

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
						if (fnmatch($key, $reg))
						{
							$hilite = sprintf(' class="watch" title="%s"', htmlspecialchars($comment));
							$sortkey = str_replace('%', '0:'.$reg.$day.$hhmm, $sortkey);
							break;
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
			if ($mobile)
			{
				echo "<td$hilite>$reg";
			}
			else
			{
				echo "<td$hilite$sortkey>";
?>
				<a href = "http://www.airliners.net/search/photo.search?q=<?php echo $reg ?>&sort_order=year+desc" target="a-net">
					<img src="img/a-net.png" alt="www.airliners.net">
				</a>
<?php
				echo "$reg";
			}
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
