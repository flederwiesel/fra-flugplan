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
	header("Location: ../index.php?page=download");	//&& strcat("&error=")
}
else
{
	$direction = $_POST['direction'];
	$airport = 'arrival' == $direction ? 'from' : 'to';

	$from = mktime_c($_POST['date-from'], $_POST['time-from']);
	$until = mktime_c($_POST['date-until'], $_POST['time-until']);

	$filename = sprintf("FRA-%s-%s-$direction.csv",
						strftime('%Y%m%d%H%M', $until),
						strftime('%Y%m%d%H%M', $from));

	$file = tmpfile();

	if (!$file)
	{
		//&&
		header("$_SERVER[SERVER_PROTOCOL] 503 Service Unavailable", true, 500);
		header("Location: ../index.php?page=download");	//&& strcat("&error=")
	}
	else
	{
		$from = strftime('%Y-%m-%d %H:%M:%S', $from);
		$until = strftime('%Y-%m-%d %H:%M:%S', $until);

		$query = <<<QUERY
SELECT `flights`.`expected`, `flights`.`scheduled`,
CONCAT(`airlines`.`code`, `flights`.`code`) AS `flight`,
CONCAT(`airports`.`iata`, '/', `airports`.`icao`)  AS `$airport`,
`models`.`icao`  AS `model`,
`aircrafts`.`reg` AS `reg`
FROM `flights`
LEFT JOIN `airlines` ON `airlines`.`id` = `flights`.`airline`
LEFT JOIN `airports` ON `airports`.`id` = `flights`.`airport`
LEFT JOIN `models` ON `models`.`id` = `flights`.`model`
LEFT JOIN `aircrafts` ON  `aircrafts`.`id` = `flights`.`aircraft`
WHERE `flights`.`direction` = '$direction'
AND IFNULL(`flights`.`expected`, `flights`.`scheduled`)
BETWEEN '$from' AND '$until'
ORDER BY IFNULL(`flights`.`expected`, `flights`.`scheduled`) ASC,
`scheduled` ASC
QUERY;

		$result = mysql_query($query);

		if (!$result)
		{
			$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
		}
		else
		{
			$columns = mysql_num_fields($result);

			for ($col = 0; $col < $columns; $col++)
				fwrite($file, ($col > 0 ? ";" : "").mysql_field_name($result, $col));

			while ($row = mysql_fetch_row($result))
			{
				for ($col = 0; $col < $columns; $col++)
					fwrite($file, (0 == $col ? "\n" : ";").$row[$col]);
			}

			fwrite($file, "\n");
		}

		$size = ftell($file);

		header('Refresh: 0; url="?page=download"');	//&& strcat("&from=&until&")

		header("Content-Disposition: inline; filename=\"$filename\"");
		header("Content-Type: text/comma-separated-values");
		header("Content-Transfer-Encoding: binary\n");
		header("Content-Length: $size");

		fseek($file, 0, SEEK_SET);

		do
		{
			$line = fgets($file);

			if ($line)
				echo $line;
		}
		while ($line);

		fflush($file);
		fclose($file);
	}

	mysql_close($hdbc);
}
