<script type="text/javascript" src="<?= Asset::src('script/sortable.js') ?>" defer></script>
<script type="text/javascript" src="<?= Asset::src('script/sorttable.js') ?>" defer></script>
<?php
if ($user)
{
?>
<script type="text/javascript" src="<?= Asset::src('script/watchlist.js') ?>" defer></script>
<?php
}

$error = null;
$message = null;

include 'photodb.php';

require_once("watchlist.php");

$watchlist = [
	"private" => null,
];

if ($db)
{
	try
	{
		if ($user)
		{
			$watchlist["private"] = new Watchlist($db, $user->id());

			if (isset($_POST['watchlist']))
			{
				// Update watchlist from posted values
				try
				{
					$posted = json_decode(
						$_POST['watchlist'], false, 5, JSON_THROW_ON_ERROR
					);

					foreach ($watchlist as $owner => $list)
					{
						if (!property_exists($posted, $owner))
							continue;

						$postData = new WatchlistPostData($posted->{$owner});

						$list->update($postData);

						// If any added/updated entry sets notifications, check whether
						// the notification times in the user profile are meaningful.
						if ($postData->containsNotifications())
						{
							if ($user->opt('notification-from') == $user->opt('notification-until'))
								$message = $STRINGS['notif-setinterval'];
						}
					}
				}
				catch (JsonException | ValueError $e)
				{
					$error = $STRINGS['invalidrequest'];
				}
			}
		}
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

if ($watchlist["private"])
{
?>
<div id="watchlist-container">
	<div>
		<div id="watchlist" class="<?= $_GET['watchlist'] ?? '' == 'expanded' ? 'expanded' : '' ?>">
			<div id="watchlist-handle">
				<div></div>
			</div>
			<div>
				<form method="post" action="?" class="center">
					<div>
						<section>
<?= $watchlist["private"]->renderTable($photodb, $PhotodbSearchUrl); ?>
						</section>
						<div id="submit-container">
							<input type="hidden" name="CSRFToken" value="<?= CsrfToken::get() ?>">
							<input type="submit" value="<?= $STRINGS['save'] ?>">
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php
}

// Transform the watchlist data to a more suitable format for
// reg comparison against array indices, regexes and wildcards.

$WatchlistMatcher = [
	"private" => $watchlist["private"] ?
		new WatchlistMatcher(
			$watchlist["private"]->data()
		) : null
];

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
				<th><?= $STRINGS['time'] ?></th>
				<th class="sep"><?= $STRINGS['flight'] ?></th>
<?php
				if (!$mobile || $tablet)
				{
?>
				<th class="sep"><?= $STRINGS['airline'] ?></th>
				<th class="sep">IATA</th>
				<th class="sep">ICAO</th>
				<th class="sep"><?= ucfirst($dir == 'arrival'  ? $STRINGS['from'] : $STRINGS['to']) ?></th>
<?php
				}
?>
				<th class="sep sorttable_model"><?= $STRINGS['type'] ?></th>
				<th class="sep sorttable_reg reg"><div></div><?= $STRINGS['reg'] ?></th>
			</tr>
		</thead>
		<tbody>
<?php

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

// This might be configurable in the future...
// In this case, see CSS `#schedule td:nth-child(...)`
// Variable:
$columns = <<<EOF
	`type`,
	`airlines`.`name` AS `airline`,
	`airports`.`iata` AS `airport_iata`,
	`airports`.`icao` AS `airport_icao`,
	`airports`.`name` AS `airport_name`,
	lower(`countries`.`alpha-2`) AS `country`,
	EOF;

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
		LEFT JOIN `countries` ON `airports`.`country` = `countries`.`id`
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
			$dhhmm = "{$day} {$hhmm}";
			$code = "{$row->fl_airl}{$row->fl_code}";
			$airport = $row->airport_name ?? "???";

			switch ($row->type)
			{
			case 'C':
				$cargo = ' cargo';
				break;

			case 'F':
				$cargo = '';
				break;

			default:
				$cargo = '';
			}

			$reg = $row->reg ?? '';
			$title = null;
			$href = null;
			$classes = ["reg"];

			if ($reg)
			{
				foreach ($WatchlistMatcher as $owner => $matcher)
				{
					$comment = $matcher ? $matcher->getComment($reg) : null;

					if ($comment !== null)
					{
						$classes[] = "watch";
						$title = htmlspecialchars($comment);
					}
					else
					{
						$vtf = $row->vtf ?? 9999;

						if ($vtf < 10)
						{
							$classes[] = "rare";
							$vtf = ordinal($vtf, $lang);
							$title = htmlspecialchars("$vtf$STRINGS[vtf]");
						}
					}
				}

				if ($title)
					$title = " title=\"{$title}\"";

				$href = str_replace(['&', '{reg}' ], [ '&amp;', $reg ], $PhotodbSearchUrl);
			}

			/* <td> inherits 'class="left"' from div.box */
			?><td<?= $early ?>><?= $dhhmm ?></td><?php
			?><td><?= $code ?></td><?php

			if (!$mobile)
			{
				?><td><?= $row->airline ?></td><?php
				?><td><?= $row->airport_iata ?></td><?php
				?><td><?= $row->airport_icao ?></td><?php
				?><td><div class="fi<?= $row->country ? " fi-{$row->country}" : "" ?>"></div><?= $airport ?></td><?php
			}

			?><td class="model<?= $cargo ?>"><?= $row->model ?></td><?php
			?><td class="<?= implode(" ", $classes) ?>"<?= $title ?>><?php

			if ($href)
			{
				?><a href="<?= $href ?>" target="<?= $photodb ?>"><div></div></a><?php
			}

			?><?= $reg ?? '' ?></td><?php
			?></tr>
<?php
		}
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
