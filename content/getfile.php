<?php

/******************************************************************************
 *
 *       project: FRA-flights Live Schedule
 *
 *       $Author: flederwiesel $
 *         $Date: 2013-08-26 21:01:54 +0200 (Mo, 26 Aug 2013) $
 *          $Rev: 413 $
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

mb_internal_encoding('UTF-8');

@require_once '../.config';
@require_once '../classes/etc.php';

session_start();

$error = null;
$hdbc = @mysql_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD);

if (!$hdbc)
{
	$error = mysql_error();
}
else
{
	if (mysql_select_db(DB_NAME, $hdbc))
	{
		mysql_set_charset('utf8');
	}
	else
	{
		$error = mysql_error();
		mysql_close($hdbc);
		$hdbc = null;
	}
}

if ($error)
{
	header("$_SERVER[SERVER_PROTOCOL] 503 Service Unavailable", true, 500);
	header("Location: ../index.php?page=download");
}
else
{
	header('Refresh: 0; url="?page=download"');

	header('Content-Type: text/comma-separated-values');
	header('Content-Disposition: inline; filename="FRA-2013-08-23-arrival.csv"');
	header("Content-Transfer-Encoding: binary\n");
	header("Content-Length: 12345");

	echo "expected;scheduled;flight;airline;airport iata;airport icao;airport;model;model;reg\n";

	$query = <<<QUERY
SELECT `flights`.`expected`, `flights`.`scheduled`,
CONCAT(`airlines`.`code`, `flights`.`code`) AS `flight`,
`airlines`.`name` AS `airline`,
`airports`.`iata` AS `airport iata`,
`airports`.`icao`  AS `airport icao`,
`airports`.`name`  AS `airport`,
`models`.`icao`  AS `model`,
`models`.`name`  AS `model`,
`aircrafts`.`reg` AS `reg`
FROM `flights`
LEFT JOIN `airlines` ON `airlines`.`id` = `flights`.`airline`
LEFT JOIN `airports` ON `airports`.`id` = `flights`.`airport`
LEFT JOIN `models` ON `models`.`id` = `flights`.`model`
LEFT JOIN `aircrafts` ON  `aircrafts`.`id` = `flights`.`aircraft`
WHERE `flights`.`direction` = 'arrival'
AND IFNULL(`flights`.`expected`, `flights`.`scheduled`)
BETWEEN '2013-08-23 00:00:00' AND '2013-08-24 00:00:00'
ORDER BY IFNULL(`flights`.`expected`, `flights`.`scheduled`) ASC,
`scheduled` ASC
QUERY;

	echo $query."\n";
	echo $_POST['direction']."\n";
	echo mktime_c($_POST['from'])."\n";
	echo mktime_c($_POST['until'])."\n";

	if ($hdbc)
		mysql_close($hdbc);
}
