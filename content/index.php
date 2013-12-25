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

$error = null;

function ordinal($number, $lang)
{
	if ('en' == $lang)
	{
		$suffix = array('th','st','nd','rd','th','th','th','th','th','th');

		if (($number % 100) >= 11 && ($number % 100) <= 13)
			$ordinal = "${number}th";
		else
			$ordinal = "$number-".$suffix[$number % 10];
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
		$error = '...';
	}
	else
	{
		if ($hdbc)
		{
			$error = NULL;

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

						$query = "DELETE FROM `watchlist` WHERE `user`=".$user->id().
								 " AND `reg`='$reg'";

						if (!mysql_query($query))
						{
							$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
							break;
						}
					}

					if (!$error)
					{
						if (isset($_POST['reg']))
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

							$query = "INSERT INTO `watchlist`(`user`,`reg`,`comment`) ".
									 "VALUES((SELECT `id` FROM `users` WHERE `name`='".$user->name()."'), '$reg', '$comment')".
									 "ON DUPLICATE KEY UPDATE `comment`='$comment'";

							if (!mysql_query($query))
							{
								$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
								break;
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

/******************************************************************************
 * Watchlist
 ******************************************************************************/

$watch = array();

/******************************************************************************
 * move watchlist cookies into db
 ******************************************************************************/

foreach ($_COOKIE as $key =>$value)
	$watch[$key] = $value;

unset($watch['lang']);
unset($watch['userID']);
unset($watch['hash']);
unset($watch['autologin']);
unset($watch['PHPSESSID']);
unset($watch['DBGSESSID']);
unset($watch['POPUPCHE']);

if ($user)
{
	if ($hdbc)
	{
		foreach ($watch as $reg => $comment)
		{
			$query = "SELECT `id` FROM `watchlist` WHERE `user`=".$user->id()." AND `reg`='$reg'";
			$result = mysql_query($query);

			if (!$result)
			{
				$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
			}
			else
			{
				if (0 == mysql_num_rows($result))
				{
					$query = "INSERT INTO `watchlist`(`user`, `reg`, `comment`)".
							 " VALUES(".$user->id().", '$reg', '$comment')";

					if (mysql_query($query))
					{
						setcookie($reg, NULL, 0);
					}
					else
					{
						$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
						break;
					}
				}

				mysql_free_result($result);
			}

			unset($watch[$reg]);
		}
	}
}

/******************************************************************************
 * get watchlist from db
 ******************************************************************************/

if ($user)
{
	if ($hdbc)
	{
		$result = mysql_query("SELECT `reg`,`comment` FROM `watchlist`".
							  " WHERE `user`=".$user->id().
							  " ORDER BY `reg`");

		if (!$result)
		{
			$error .= sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
		}
		else
		{
			while ($row = mysql_fetch_row($result))
				$watch[$row[0]] = $row[1];

			mysql_free_result($result);
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
<div id="error">
	<h1><?php echo $lang['fatal']; ?></h1>
	<?php echo $error; ?>
</div>
<?php
}

/* Runway direction */
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
if ($user && (!$mobile || $tablet))
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
										<td class="button"><input type="button" class="del" onclick="RemoveRow(this);"></td>
										<td class="button"><input type="button" class="add" onclick="CloneRow(this);"></td>
									</tr>
<?php
								}
								else
								{
									foreach ($watch as $reg => $comment)
									{
?>
										<tr>
											<td><a href="http://www.airliners.net/search/photo.search?q=<?php echo $reg; ?>" target="a-net"><img src="img/a-net.png" alt="www.airliners.net"></a></td>
											<td class="reg"><input type="text" value="<?php echo $reg; ?>"></td>
											<td class="comment"><input type="text" value="<?php echo htmlspecialchars($comment); ?>"></td>
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
	" AND TIME_TO_SEC(TIMEDIFF(IFNULL(`expected`, `scheduled`), now())) >= $lookback ".
	" AND TIME_TO_SEC(TIMEDIFF(IFNULL(`expected`, `scheduled`), now())) <= $lookahead ".
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
		if (strtotime($row['expected']) - time() < 0)
			echo '<tr class="past">';
		else
			echo '<tr>';

		$day = floor((strtotime($row['expected']) - strtotime(date('Y-m-d'))) / 24 / 60 / 60);

		if ($day >= 0)
			$day = '+'.$day;

		$early = $row['timediff'] < 0 ? ' class="early"' : '';
		$hhmm = substr($row['expected'], 11, 5);

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
			echo "<td class='left'><div>$row[airline]</div></td>";
			echo "<td>$row[airport_iata]</td>";
			echo "<td>$row[airport_icao]</td>";
			echo "<td class='left'><div>$row[airport_name]</div></td>";
		}

		echo "<td>$row[model]</td>";

		$reg = $row['reg'];
		$vtf = $row['vtf'] ? $row['vtf'] : '9999';
		$hilite = ' class="left';

		if (0 == strlen($reg))
		{
			$sortkey = ' sorttable_customkey="2"';
		}
		else
		{
			$sortkey = ' sorttable_customkey="%"';
			$dhhmmss = substr(str_replace(array(' ', '.', ':', '-'), '', $row['expected']), -7);

			if (isset($watch[$reg]))
			{
				$hilite .= ' watch" title="'.htmlspecialchars($watch[$reg]);
				$sortkey = str_replace('%', '0:'.$reg.$dhhmmss, $sortkey);
			}
			else if ($vtf < 10)
			{
				$vtf = ordinal($vtf, $_SESSION['lang']);
				$hilite .= ' rare" title="'.htmlspecialchars("$vtf$lang[vtf]");
				$sortkey = str_replace('%', '0:'.$reg.$dhhmmss, $sortkey);
			}
			else
			{
				$sortkey = str_replace('%', '1:'.$reg.$dhhmmss, $sortkey);
			}
		}

		flush();

		$hilite .= '"';
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
				<a href = "http://www.airliners.net/search/photo.search?q=<?php echo $reg ?>" target="a-net">
					<img class="href" src="img/a-net.png" alt="www.airliners.net">
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
