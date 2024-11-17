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

/*
#!./check.sh
 */

/******************************************************************************
 * Fraport server mockup - Generates html paginated schedule based on CSV files
 ******************************************************************************/

mb_internal_encoding('UTF-8');

header("Content-Type: application/json");

function compare_sched($a, $b)
{
	if ($a['sched'] == $b['sched'])
		return 0;
	else
		return ($a['sched'] < $b['sched']) ? -1 : 1;
}

// main()

$dir = null;
$now = null;
$page = 1;
$items = 3;
$html = false;

if (isset($_GET['cargo']))
	die('{"type":"arrival","luops":"0000-00-00T00:00:00+0100","lusaison":"0000-00-00T00:00:00+0100","results":0,"entriesperpage":0,"maxpage":0,"page":0}');	// for now ...

if (isset($_GET['fmt']))
{
	if ('html' == $_GET['fmt'])
		$html = true;
}

if (isset($_GET['flighttype']))
{
	$dir = $_GET['flighttype'];
	$dir = substr($dir, 0, -1);

	if (!('arrival'   == $dir ||
		  'departure' == $dir))
	{
		die('{"error":"`flighttype` needed"}');
	}
}
if (isset($_GET['time']))
	$now =  strtotime($_GET['time']);

if (!$now)
	$now = time();

if (isset($_GET['page']))
	if ($_GET['page'] > 0)
		$page = $_GET['page'];

if (isset($_GET['perpage']))
	if ($_GET['perpage'] > 0)
		$items = $_GET['perpage'];

if ($html)
{
?>
<html>
<head>
</head>
<body>
<pre><?php
}

if ($dir && $now && $page > 0)
{
	$flights = [];

	/* Look for CVS file in arrival/departure directory */
	$flightsdir = opendir("flights/$dir");

	if ($flightsdir)
	{
		$files = [];
		$file = readdir($flightsdir);

		while (false !== $file)
		{
			if ($file != '.' && $file != '..')
				$files[] = $file;

			$file = readdir($flightsdir);
		}

		foreach ($files as $file)
		{
			if (preg_match('/.*\.csv$/i', $file))
			{
				/* Description of the csv */
				$keys = null;
				$lines = file("flights/$dir/$file");

				foreach ($lines as $line)
				{
					$line = rtrim($line);

					if ('#' == $line[0])
					{
						$keys = explode(";", substr($line, 1));
					}
					else if (';' == $line[0])
					{
					}
					else
					{
						if (null == $keys)
						{
							printf("%s(%u): No keys to index array.\n", __FILE__, __LINE__);
							break;
						}
						else
						{
							/* if (strlen($line) == count($keys)) line is empty! */
							if (strlen($line) > count($keys) - 1)
							{
								/* Delete all C-Comments (and surrounding spaces) from line */
								$line = preg_replace('/[ \t]*\/*(\*[^\/]+|[^*]\/)\*\/[ \t]*/', '', $line);

								/* Create an associative array from line */
								$flight = array_combine($keys, explode(';', $line));

								list($day, $time) = explode(' ', $flight['now']);
								$flighttime = strtotime("+{$day} days {$time}");

								if ($flighttime == $now)
								{
									/* Do not output `now` column */
									unset($flight['now']);

									$flight['id'] = strtolower($flight['dir']).date('Ymd').strtolower($flight['al']).$flight['fnr'];

									/* Adjust fnr */
									$flight['fnr'] = $flight['al'].' '.$flight['fnr'];

									/* Adjust sched */
									$d = strtok($flight['sched'], ' ');
									$t = strtok(null);

									$flight['sched'] = date(DATE_ISO8601, strtotime("$d days $t", $now));

									/* Adjust esti */
									if (0 == strlen($flight['esti']))
									{
										unset($flight['esti']);
									}
									else
									{
										$d = strtok($flight['esti'], ' ');
										$t = strtok(null);

										$flight['esti'] = date(DATE_ISO8601, strtotime("$d days $t", $now));
									}

									if (0 == strlen($flight['lu']))
										$flight['lu'] = date(DATE_ISO8601, $now);
									else
										$flight['lu'] = date(DATE_ISO8601, strtotime("$d days $t", $now));

									/* Unset empty strings */
									if (0 == strlen($flight['ac']))
										unset($flight['ac']);

									if (0 == strlen($flight['reg']))
										unset($flight['reg']);

									if (0 == strlen($flight['status']))
										unset($flight['status']);

									unset($flight['icao']);
									unset($flight['apname']);
									unset($flight['country']);

									$flights[] = $flight;
								}
							}
						}
					}
				}
			}
		}

		/* Show at most count($flights) */
		$results = count($flights);

		if ($items > $results)
			$items = $results;

		if ($items < 1)
		{
			$items = 0;
			$start = 0;
			$pages = 0;
			$page = 0;
		}
		else
		{
			/* Validate page index, set start index */
			$pages = (int)($results / $items);

			if ($results % $items > 0)
				$pages++;

			if ($page > $pages)
			{
				$results = 0;
				$start = 0;
				$items = 0;
				$pages = 0;
				$page = 0;
			}
			else
			{
				$start = ($page - 1) * $items;

				/* Limit items */
				if ($start + $items > $results)
					$items = $results - $start;

				/* Sort array by `sched` */
				usort($flights, 'compare_sched');
			}
		}

		if ($results > 0)
			$result['data'] = array_slice($flights, $start, $items);

		$result['type'] = $dir;
		$result['luops'] = date(DATE_ISO8601, $now);
		$result['lusaison'] = date(DATE_ISO8601, $now);
//		$result['filter'] = new StdObject();
		$result['results'] = $results;
		$result['entriesperpage'] = $items;
		$result['maxpage'] = $pages;
		$result['page'] = $page;

		echo json_encode($result);
	}
}

if ($html)
{
?>
</pre>
</body>
</html>
<?php
}
?>
