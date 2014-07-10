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
mb_internal_encoding('UTF-8');

@require_once '../.config';
@require_once '../classes/etc.php';

function seterrorinfo($line, $info)
{
	global $errorinfo;

	$error = __FILE__;

	if ($info)
	{
		$error .= "($line): $info\n";
	}
	else
	{
		$e = error_get_last();

		if (!$line)
			$line = $e['line'];

		$error .= sprintf("(%u): " , $line);
		$error .= sprintf("[%d] %s", $e['type'], $e['message']);
	}

	$errorinfo .= $error;

	return $error;
}

session_start();

/******************************************************************************
 * detect/set language and initialise strings
 ******************************************************************************/

/* Set session language from $_POST or $_COOKIE */
if (isset($_GET['lang']))
{
	setcookie('lang', $_GET['lang'], time() + COOKIE_LIFETIME);
	$_SESSION['lang'] = $_GET['lang'];
}
else
{
	if (!isset($_SESSION['lang']))
	{
		if (isset($_COOKIE['lang']))
			$_SESSION['lang'] = $_COOKIE['lang'];
		else
			$_SESSION['lang'] = 'en';
//&& 	$_SERVER[HTTP_ACCEPT_LANGUAGE]
	}

	setcookie('lang', $_SESSION['lang'], time() + COOKIE_LIFETIME);
}

$file = 'language/'.$_SESSION['lang'].'.php';

if (file_exists($file))
	require_once $file;
else
	require_once 'language/en.php';

//&& On error:
//&& header('Content-Type: text/html; charset=UTF-8');
//&& header('Content-Language: '.$_SESSION['lang']);

$error = null;

//
if (!isset($_GET['session']))
{
	$error = $lang['invalidsession'];
}
else
{
	if ($_GET['session'] != session_id())
		$error = $lang['invalidsession'];
}

session_regenerate_id();

if (!$error)
{
	if (!isset($_POST['direction']))
	{
		$error = $lang['badrequest'];
	}
	else
	{
		if (!isset($_POST['date-from']))
		{
			$error = $lang['badrequest'];
		}
		else
		{
			if (!isset($_POST['time-from']))
			{
				$error = $lang['badrequest'];
			}
			else
			{
				if (!isset($_POST['date-until']))
				{
					$error = $lang['badrequest'];
				}
				else
				{
					if (!isset($_POST['time-until']))
						$error = $lang['badrequest'];
				}
			}
		}
	}
}

if (!$error)
{
	$from = mktime_c($_POST['date-from'], $_POST['time-from']);
	$until = mktime_c($_POST['date-until'], $_POST['time-until']);

	if (-1 == $from || -1 == $until)
	{
		$error = $lang['badrequest'];
	}
	else
	{
		$direction = $_POST['direction'];
		$airport = 'arrival' == $direction ? 'from' : 'to';

		$filename = sprintf("FRA-%s-%s-$direction.csv",
							strftime('%Y%m%d%H%M', $until),
							strftime('%Y%m%d%H%M', $from));

		$from = strftime('%Y-%m-%d %H:%M:%S', $from);
		$until = strftime('%Y-%m-%d %H:%M:%S', $until);
	}
}

if (!$error)
{
	$hdbc = @mysql_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD);

	if (!$hdbc)
	{
		$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
	}
	else
	{
		if (!mysql_select_db(DB_NAME, $hdbc))
		{
			$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
		}
		else
		{
			mysql_set_charset('utf8');

			$query = <<<SQL
				SELECT `flights`.`expected`, `flights`.`scheduled`,
				CONCAT(`airlines`.`code`, `flights`.`code`) AS `flight`,
				CONCAT(`airports`.`iata`, '/', `airports`.`icao`)  AS `$airport`,
				`models`.`icao`  AS `model`,
				`aircrafts`.`reg` AS `reg`
				FROM
				(
					SELECT `expected`, `scheduled`, `airline`, `code`, `airport`, `model`, `aircraft`
					FROM `flights`
					USE INDEX (`flights:direction`)
					WHERE `direction` = '$direction'
					AND IFNULL(`expected`, `scheduled`) BETWEEN '$from' AND '$until'
					UNION ALL
					SELECT `expected`, `scheduled`, `airline`, `code`, `airport`, `model`, `aircraft`
					FROM `history`
					USE INDEX (`history:direction`)
					WHERE `direction` = '$direction'
					AND IFNULL(`expected`, `scheduled`) BETWEEN '$from' AND '$until'
				) AS `flights`
				LEFT JOIN `airlines` ON `airlines`.`id` = `flights`.`airline`
				LEFT JOIN `airports` ON `airports`.`id` = `flights`.`airport`
				LEFT JOIN `models` ON `models`.`id` = `flights`.`model`
				LEFT JOIN `aircrafts` ON  `aircrafts`.`id` = `flights`.`aircraft`
				ORDER BY IFNULL(`flights`.`expected`, `flights`.`scheduled`) ASC,
					`scheduled` ASC
SQL;

			$result = mysql_query($query);

			if (!$result)
			{
				$error = sprintf($lang['dberror'], __FILE__, __LINE__, mysql_error());
			}
			else
			{
				$file = tmpfile();

				if (!$file)
				{
					$error = seterrorinfo(__LINE__, NULL);;
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

					$size = ftell($file);
					fseek($file, 0, SEEK_SET);

					header("Content-Disposition: inline; filename=\"$filename\"");
					header("Content-Type: text/comma-separated-values");
					header("Content-Transfer-Encoding: binary\n");
					header("Content-Length: $size");

					do
					{
						$line = fgets($file);

						if ($line)
							echo $line;
					}
					while ($line);

					fflush($file);

					$_SESSION['message'] = 'OK';
				}

				fclose($file);
			}
		}

		mysql_close($hdbc);
	}
}

if (!$error)
{
	header('Refresh: 0; url="?page=download"');
}
else
{
	$_SESSION['error'] = $error;

	header("$_SERVER[SERVER_PROTOCOL] 503 Service Unavailable", true, 500);
	header("Location: ../index.php?page=download");
}
