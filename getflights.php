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

/******************************************************************************
 *
 *  Debug options: debug=[url,arrival,departure,airports,awk,query]
 *                 fmt=[htm|html|...]
 *
 *   Test options: baseurl=www.frankfurt-airport.de
 *                 baseurl=localhost/fra-schedule/fra-schedule-trunk/check
 *                 now={localtime as '%Y-%m-%d %H:%M:%S'}
 *
 ******************************************************************************/

$script_start = microtime(TRUE);

/* Suppress warning "Declaration of awkFlights::execute() should be compatible with awk::execute($text)" */
error_reporting(E_ALL & ~E_STRICT);

mb_internal_encoding('UTF-8');

ini_set('max_execution_time', 180);

include ".config";
include "classes/etc.php";
include "classes/awk.php";
include "classes/vector.php";

$errorinfo = NULL;
$warning = NULL;
$info = NULL;

// We need to adjust departure times, make sure we use the correct tz
$tz = date_default_timezone_set('Europe/Berlin');

$baseurl = 'www.frankfurt-airport.de';
$rwyinfo = 'apps.fraport.de';
$now = strftime('%Y-%m-%d %H:%M:%S');
$cycle = '5 min';
$items = 15;

if (isset($_GET['baseurl']))
{
	/* Those may be overridden for testing */
	$baseurl = $_GET['baseurl'];
	$rwyinfo = $_GET['baseurl'];

	if (isset($_GET['now']))
		$now = $_GET['now'];

	$cycle = '60 min';
	$items = 3;
}

if (isset($_GET['debug']))
{
	$flags = explode(",", $_GET['debug']);

	if ('full' == $_GET['debug'])
	{
		$full = 'url,arrival,departure,airports,awk,query';

		foreach (explode(',', $full) as $key)
			$DEBUG[$key] = 1;
	}
	else
	{
		foreach ($flags as $key)
			$DEBUG[$key] = 1;
	}

	unset($flags);

	$DEBUG['any'] = 1;
}

if (isset($_GET['help']) ||
	isset($_GET['debug']))
{
	if (isset($_GET['fmt']))
		$DEBUG['fmt'] = $_GET['fmt'];
	else if (isset($_GET['format']))
		$DEBUG['fmt'] = $_GET['format'];
	else
		$DEBUG['fmt'] = 'txt';
}

if (isset($DEBUG['fmt']))
{
	if ('htm'  == $DEBUG['fmt'] ||
		'html' == $DEBUG['fmt'])
	{
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>getflights &middot; debug output</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<meta name="author" content="Tobias Kühne">
</head>
<body>
<pre>
<?php
	}
}

if (isset($_GET['help']))
{
    echo <<<EOF
debug=
    url
    awk
    arrival
    departure
    airports
    query

fmt=
    htm|html

EOF;

	goto fin;
}

function geterrorinfo()
{
	global $error;
	return $error;
}

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
		$error .= sprintf("[%d] %s\n", $e['type'], $e['message']);
	}

	$errorinfo .= $error;

	return $error;
}

function warn_once($line, $info)
{
	global $warning;

	if (!strstr($warning, $info))
	{
		if (!$warning)
			$warning = '';

		$warning .= __FILE__."($line): $info\n";
	}
}

function info($line, $text)
{
	global $info;

	if (!$info)
		$info = '';

	$info .= __FILE__."($line): $text\n";
}

function query_style($query)
{
	// Cleanup query for single line display with 1 space separator
	$query = preg_replace('/[ \t\r]*\n[ \t]*/', ' ', $query);
	$query = preg_replace('/[ \t]*(;?)$/', '\\1', $query);
	$query = preg_replace('/^[ \t]*/', '', $query);

	$query = preg_replace('/\([ \t]+/', '(', $query);
	$query = preg_replace('/[ \t]+\)/', ')', $query);

	return $query."\n";
}

function patchreg($reg)
{
	$regdb = array(

		'/^3[ABCDX]/', 2,
		'/^4[KLORWX]/', 2,
		'/^5[ABHNRTUVWXY]/', 2,
		'/^6[VWY]/', 2,
		'/^6O[^S]/', 2,
		'/^6OS/', 3,
		'/^7[OPQT]/', 2,
		'/^8[PQR]/', 2,
		'/^9[AGHJKLMNOQUVY]/', 2,
		'/^9XR/', 3,
		'/^A[^2345679NP]/', 1,
		'/^A[23567NP]/', 2,
		'/^A4O/', 3,
		'/^A9C/', 3,
		'/^B[^R]/', 1,
		'/^BR/', 2,
		'/^C[^23569BCHNPRSUVX]/', 1,
		'/^C[23569BCHNPRSUVX]/', 2,
		'/^CCCP/', 4,
		'/^D[^246MQ]/', 1,
		'/^D[246MQ]/', 2,
		'/^E[35CIKLPRSTWXYZ]/', 2,
		'/^F/', 1,
		'/^G/', 1,
		'/^H[4ABCHIKPRSVZ]/', 2,
		'/^I/', 1,
		'/^JA/', 0,
		'/^J[235678Y]/', 2,
		'/^K[^W]/', 1,
		'/^KW/', 2,
		'/^L[GINQRVXYZ]/', 2,
		'/^M[^T]/', 1,
		'/^MT/', 2,
		'/^N/', 0,
		'/^O[ABDEHKMOY]/', 2,
		'/^P[^24HIJKPRTZ]/', 1,
		'/^P[24HIJKPRTZ]/', 2,
		'/^R[^ACPVX]/', 1,
		'/^R[ACPVX]/', 2,
		'/^RDPL/', 4,
		'/^S[23579ELNPTUX]/', 2,
		'/^T[379CFGIJLNRSTUYZ]/', 2,
		'/^U[HKLNR]/', 2,
		'/^V[234578HNPQRT]/', 2,
		'/^X[ABCHTUVWYZ]/', 2,
		'/^Y[AEIJKLNRSUV]/', 2,
		'/^Z[^3AKLPSTU]/', 1,
		'/^Z[3AKLPSTU]/', 2,
	);

	for ($i = 0; $i < count($regdb); $i += 2)
	{
		if (preg_match($regdb[$i], $reg))
		{
			if ($regdb[$i + 1])
				return substr($reg, 0, $regdb[$i + 1]).'-'.substr($reg, $regdb[$i + 1]);
		}
	}

	return $reg;
}

class flight
{
	public $airline;
	public $code;		// flight number
	public $carrier;
	public $scheduled;
	public $expected;
	public $model;
	public $reg;

	public function __construct($airline, $code)
	{
		$this->airline   = $airline;
		$this->code      = $code;
		$this->carrier   = array();
		$this->scheduled = NULL;
		$this->expected  = NULL;
		$this->model     = NULL;
		$this->reg       = NULL;

		return $this;
	}
}

class AirportInfo
{
	public $id;
	public $fid;	// db id of flight to this particular airport
	public $iata;
	public $icao;
	public $name;

	public function __construct($fid)
	{
		$this->fid  = $fid;
		$this->iata = '';
		$this->icao = '';
		$this->name = '';

		return $this;
	}
}

class awkFlights extends awk
{
	private $flights = NULL;
	private $top = NULL;		/* on vector `$flights` */
	private $page = 0;

	public function execute($text, &$flights, &$page)
	{
		$this->flights = $flights;
		$this->page = $page;

		awk::execute($text);

		$page = $this->page;
	}

	static function code($obj, $fields)
	{
		global $DEBUG;

		if (preg_match('/<h3>/', $fields[0], $match))
		{
			if ($obj->flights->count())
				if (isset($DEBUG['awk']))
					print_r($obj->top);

			if (0 == strncmp('<h3>Leider ', $fields[0], 11))
			{
				// Leider liegen keine Daten aktueller Abflüge vor.
				// Bitte versuchen Sie es zu einem späteren Zeitpunkt erneut oder
				// wenden Sie sich an das Fraport Communication Center unter der Telefonnummer 01805-3 72 46 36 (FRAINFO).
			}
			else
			{
				$obj->top = $obj->flights->push(
								new flight(str_replace('<h3>', '', $fields[1]), $fields[2]));

				if (NULL == $obj->top)
					$error = seterrorinfo(__LINE__, NULL);
			}
		}

		$obj->next();
	}

	static function airline($obj, $fields)
	{
		// "a-z.html#OZ\">Asiana Airlines</"
		$fields = explode("#", $fields[0]);

		if (count($fields) > 1)
		{
			$code = explode("\"", $fields[1]);
			$name = explode(">", $fields[1]);

			if (count($name) > 1)
				$name   = explode("<", $name[1]);

			if ($obj->top)
			{
				$obj->top->carrier['name'] = $name[0];
				$obj->top->carrier['code'] = $code[0];
			}

			$obj->next();
		}
	}

	static function scheduled($obj, $fields)
	{
		if (count($fields) > 2)
		{
			if ($obj->top)
				$obj->top->scheduled = $fields[2];

			// IMPORTANT: Do not call `$obj->next()`
			//  since date will follow on same line!!!
		}
	}

	static function expected($obj, $fields)
	{
		if (count($fields) > 2)
		{
			if ($obj->top)
				$obj->top->expected = $fields[2];

			$obj->next();
		}
	}

	static function date($obj, $fields)
	{
		if (count($fields) > 2)
		{
			// different for arrival and departure
			if (preg_match('/[0-9][0-9].[0-9][0-9].[0-9][0-9][0-9][0-9]/', $fields[2]))
				$col = 2;
			else
				$col = 5;

			$date = explode(".", $fields[$col]);

			if ($obj->top)
			{
				// prepend "YYYY-mm-dd" to "HH:MM"
				if ($obj->top->expected)
					$obj->top->expected = "$date[2]-$date[1]-$date[0] ".$obj->top->expected;
				else if ($obj->top->scheduled)
					$obj->top->scheduled = "$date[2]-$date[1]-$date[0] ".$obj->top->scheduled;
			}
		}
	}

	static function model($obj, $fields)
	{
		if (count($fields) > 2)
		{
			if ($obj->top)
				$obj->top->model = $fields[2];

			$obj->next();
		}
	}

	static function reg($obj, $fields)
	{
		if (count($fields) > 2)
		{
			if ($obj->top)
				$obj->top->reg = patchreg($fields[2]);

			$obj->next();
		}
	}

	static function remark($obj, $fields)
	{
		global $tz;
		global $now;
		global $cycle;

		if ($obj->top)
		{
			$remark = $obj->getline();

			/*
				$remark is one of the following:

				<p>annulliert</p>
				--
				<p>im Anflug</p>
				<p>gelandet</p>
				<p>Gepäckausgabe beendet</p>
				<p>Gepäckausgabe</p>
				--
				<p>Gate offen</p>
				<p>geschlossen</p>
				<p>gestartet</p>
			*/

			if (preg_match('/<p>annulliert<\/p>/', $remark))
			{
				$obj->top->expected = 'NULL';
			}
			else if (preg_match('/<p>Gepäckausgabe( beendet)?<\/p>/', $remark))
			{
				// Don't update any more
				$obj->top->scheduled = NULL;
			}
			else if (preg_match('/<p>gestartet<\/p>/', $remark))
			{
				// Don't update flight any more, unless $flight->expected is
				// in the future (respecting an offset of $cycle)
				if ($obj->top->expected)
					if (strtotime($obj->top->expected) < strtotime("-$cycle", strtotime($now)))
						$obj->top->scheduled = NULL;
			}
			else if (preg_match('/<p>Gate offen<\/p>/', $remark) ||
					 preg_match('/<p>geschlossen<\/p>/', $remark))
			{
				// Don't tamper with times, if we could not
				// ensure that timezone is correct
				if ($tz === true)
				{
					// Waiting for departure, but expected may not have been updated
					// If `expected` is NULL here, but a timestamp in the DB exists,
					// the latter will be overwritten by $now!
					if (NULL == $obj->top->expected)
					{
						if (strtotime($obj->top->scheduled) < strtotime($now))
							$obj->top->expected = strftime('%Y-%m-%d %H:%M', strtotime("+$cycle", strtotime($now)));
					}
					else
					{
						if (strtotime($obj->top->expected) < strtotime($now))
							$obj->top->expected = strftime('%Y-%m-%d %H:%M', strtotime("+$cycle", strtotime($now)));
					}
				}
			}
		}

		$obj->next();
	}

	static function page($obj, $fields)
	{
		global $DEBUG;

		if (count($fields) < 2)
		{
			$obj->page = 0;
		}
		else
		{
			$fields = explode("#", $fields[0]);

			if (count($fields) < 2)
				$obj->page = 0;
			else
				$obj->page = 0 + $fields[1];

			$obj->next();
		}

		if (isset($DEBUG['awk']))
		{
			if ($obj->top)
			{
				print_r($obj->top);
				echo "page=".$obj->page."\n";
			}
		}
	}
}

class awkAirports extends awk
{
	private $airport;
	private $previous;	/* upon match for iata, `$previous` contains airport name */

	public function execute($text, &$airport)
	{
		if ($airport)
		{
			$this->airport = $airport;
			$previous = '';
			awk::execute($text);
		}
	}

	static function iata($obj, $fields)
	{
		if ($obj->airport)
		{
			$obj->airport->iata = $fields[2];
			$obj->airport->name = $obj->previous;
		}
	}

	static function icao($obj, $fields)
	{
		global $DEBUG;

		if ($obj->airport)
		{
			$obj->airport->icao = $fields[2];

			if (isset($DEBUG['awk']))
				print_r($obj->airport);
		}
	}

	static function all($obj, $fields)
	{
		/* remember last line for rule awk_airports_iata() */
		$obj->previous = $fields[0];
	}
}

function CURL_GetFlights($curl, $dir, &$flights)
{
	global $DEBUG;
	global $baseurl;
	global $items;

	$f = NULL;
	$flights = new vector;
	$action = 'init';
	$page = 1;
	$date = 0;
	$previous = 0;

	/* Create $flights[$n<flight>] from pager html */

	/* Actions can be declared as anonymous function for PHP versions >= 5.3.0 */
	$awk = new awkFlights(
		array(
			'/<h3>/,/<\/h3>/'                                      => 'awkFlights::code',
			'/airlines_a-z/'                                       => 'awkFlights::airline',
			'/Planm\xc3\xa4\xc3\x9fig, [0-9][0-9]:[0-9][0-9] Uhr/' => 'awkFlights::scheduled',
			'/Erwartet: [0-9][0-9]:[0-9][0-9] Uhr/'                => 'awkFlights::expected',
			'/[0-9][0-9].[0-9][0-9].[0-9][0-9][0-9][0-9][\r]*$/'   => 'awkFlights::date',
			'/Flugzeugtyp:/'                                       => 'awkFlights::model',
			'/Registrierung:/'                                     => 'awkFlights::reg',
			'/Bemerkung:/'                                         => 'awkFlights::remark',
			'/>weiter</'                                           => 'awkFlights::page',
		));

	if (NULL == $awk)
	{
		$error = seterrorinfo(__LINE__, NULL);
	}
	else
	{
		$error = NULL;

		do
		{
			/* Build request URL */
			$url = "http://$baseurl/flugplan/airportcity?".
				   "type=$dir&typ=p&context=0&sprache=de&items=$items&$action=true&page=$page";

			if (isset($DEBUG['url']))
				echo "$url\n";

			/* Fetch HTML data */
			$retry = 3;

			do
			{
				$htm = curl_download($curl, $url, 10);

				if (!$htm || strstr($htm, "Error 404"))
				{
					$page = 0;
				}
				else
				{
					curl_setopt($curl, CURLOPT_COOKIESESSION, FALSE);	// reuse session cookie

					if (isset($DEBUG[$dir]))
					{
						echo str_replace(array('<', '>'), array('&lt;', '&gt;'), $htm);
						echo "\n";
					}

					/* Interpret HTML into `$flights` vector */
					if ($awk)
						$awk->execute($htm, $flights, $page);

					$action = 'usepager';
				}

				/* Set script execution limit. If set to zero, no time limit is imposed. */
				set_time_limit(0);
			}
			while (!$htm && --$retry);

			if (!$htm)
			{
				if (curl_errno($curl))
					$error = curl_error($curl);
				else
					$error = "(nil)";

				$error = seterrorinfo(__LINE__, "$error: $url");
			}
		}
		while ($page > 0);
	}

	return $error;
}

/* Get airport IATA/ICAO from flight details page */
function CURL_GetFlightAirports($curl, $flights, &$airports)
{
	global $DEBUG;
	global $baseurl;
	global $now;

	/* Actions can be declared as anonymous function for PHP versions >= 5.3.0 */
	$awk = new awkAirports(
		array(
			'/IATA-Code:/' => 'awkAirports::iata',
			'/ICAO-Code:/' => 'awkAirports::icao',
			'//'           => 'awkAirports::all',
		));

	if (NULL == $awk)
	{
		$error = seterrorinfo(__LINE__, NULL);
	}
	else
	{
		$error = NULL;

		while ($fi = $flights->pop())
		{
			$date = substr($fi[3], 0, 4).substr($fi[3], 5, 2).substr($fi[3], 8, 2);
			$url = "http://$baseurl/flugplan/airportcity?fi".
						substr($fi[4], 0, 1)."=".	// 'a'/'d' -> arrival/departure
						$fi[1].$fi[2].				// LH1234
						$date;						// 20120603

			if (isset($DEBUG['url']))
				echo "$url\n";

			$retry = 3;

			do
			{
				$htm = curl_download($curl, $url, 5);

				if ($htm)
				{
					if (isset($DEBUG['airports']))
						echo "$htm\n";
				}

				/* Set script execution limit. If set to zero, no time limit is imposed. */
				set_time_limit(0);
			}
			while (!$htm && --$retry);

			if (!$htm)
			{
				if (curl_errno($curl))
					$error = curl_error($curl);
				else
					$error = "(nil)";

				$error = seterrorinfo(__LINE__, "$error: $url");
			}

			if ($htm)
			{
				$airport = new AirportInfo($fi[0]);

				$awk->execute($htm, $airport);

				if ($airport->iata && $airport->icao)
					$airports->push($airport);
				else
					unset($airport);
			}

			unset($fi);
		}
	}

	return $error;
}

function SQL_GetAirlineId(/* in */ $f, /* out */ &$airline)
{
	global $DEBUG;
	global $dir;
	global $uid;

	$error = NULL;
	$airline = NULL;

	// Is airline already in database?
	$query = <<<SQL
		SELECT `id`
		FROM `airlines`
		WHERE `code`='$f->airline';
SQL;

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), query_style($query)));
	}
	else
	{
		if (isset($DEBUG['query']))
			echo query_style($query);

		if (1 == mysql_num_rows($result))
		{
			// Yes
			$row = mysql_fetch_row($result);

			if ($row)
			{
				$airline = $row[0];
				unset($row);
			}

			if (isset($DEBUG['query']))
				echo "=$airline\n";
		}
		else
		{
			if (isset($DEBUG['query']))
				echo "=<empty>\n";

			// No, insert airline
			if (strlen($f->carrier['code']) &&
				strlen($f->carrier['name']))
			{
				$query = <<<SQL
					INSERT INTO `airlines`(`uid`, `code`, `name`)
					VALUES($uid, '{$f->carrier["code"]}', '{$f->carrier["name"]}');
SQL;

				if (!mysql_query($query))
				{
					$airline = NULL;
					$error = seterrorinfo(__LINE__,
								sprintf("[%d] %s: %s",
									mysql_errno(), mysql_error(), query_style($query)));
				}
				else
				{
					$airline = mysql_insert_id();

					if (isset($DEBUG['query']))
					{
						echo query_style($query);
						echo "=$airline\n";
					}

					info(__LINE__, "Inserted airline $f->airline as \"".$f->carrier['name']."\"".
								   " ($dir: flight $f->airline$f->code \"$f->scheduled\").");
				}
			}
		}

		mysql_free_result($result);
	}

	return $error;
}

function SQL_GetCarrierId($f, &$carrier)
{
	global $DEBUG;
	global $uid;

	$error = NULL;
	$carrier = NULL;

	$query = <<<SQL
		SELECT `id`
		FROM `airlines`
		WHERE `code`='{$f->carrier["code"]}';
SQL;

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), query_style($query)));
	}
	else
	{
		if (isset($DEBUG['query']))
			echo query_style($query);

		if (mysql_num_rows($result))
		{
			$row = mysql_fetch_row($result);

			if ($row)
			{
				$carrier = $row[0];
				unset($row);
			}

			if (isset($DEBUG['query']))
				echo "=$carrier\n";
		}
		else
		{
			if (isset($DEBUG['query']))
				echo "=<empty>\n";

			// Not found, insert airline
			if (strlen($f->carrier['name']))
			{
				$query = <<<SQL
					INSERT INTO `airlines`(`uid`, `code`, `name`)
					VALUES($uid, '{$f->carrier["code"]}', '{$f->carrier["name"]}');
SQL;

				if (!mysql_query($query))
				{
					$error = seterrorinfo(__LINE__,
								sprintf("[%d] %s: %s",
									mysql_errno(), mysql_error(), query_style($query)));
				}
				else
				{
					$carrier = mysql_insert_id();

					if (isset($DEBUG['query']))
					{
						echo query_style($query);
						echo "=$carrier\n";
					}
				}
			}
		}

		mysql_free_result($result);
	}

	return $error;
}

function SQL_GetModelId($f, &$model)
{
	global $DEBUG;
	global $uid;

	$error = NULL;
	$model = NULL;

	$query = <<<SQL
		SELECT `id`
		FROM `models`
		WHERE `icao`='$f->model';
SQL;

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), query_style($query)));
	}
	else
	{
		if (isset($DEBUG['query']))
			echo query_style($query);

		if (mysql_num_rows($result))
		{
			$row = mysql_fetch_row($result);

			if ($row)
			{
				$model = $row[0];
				unset($row);
			}

			if (isset($DEBUG['query']))
				echo "=$model\n";
		}
		else
		{
			if (isset($DEBUG['query']))
				echo "=<empty>\n";

			if (strlen($f->model))
			{
				$query = <<<SQL
					INSERT INTO `models`(`uid`, `icao`,`name`)
					VALUES($uid, '$f->model', '');
SQL;

				if (mysql_query($query))
				{
					if (isset($DEBUG['query']))
						echo query_style($query);

					warn_once(__LINE__, "Aircraft '$f->model' is unknown (flight $f->airline$f->code $f->scheduled).");

					$model = mysql_insert_id();

					if (isset($DEBUG['query']))
						echo "=$model\n";
				}
				else
				{
					$error = seterrorinfo(__LINE__,
								sprintf("[%d] %s: %s",
									mysql_errno(), mysql_error(), query_style($query)));
				}
			}
		}

		mysql_free_result($result);
	}

	return $error;
}

function SQL_GetAircraftId($f, $model, &$reg)
{
	global $DEBUG;
	global $uid;

	$error = NULL;
	$query = <<<SQL
		SELECT `id`
		FROM `aircrafts`
		WHERE `reg`='$f->reg';
SQL;

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), query_style($query)));
	}
	else
	{
		if (isset($DEBUG['query']))
			echo query_style($query);

		if (mysql_num_rows($result))
		{
			$row = mysql_fetch_row($result);

			if ($row)
			{
				$reg = $row[0];
				unset($row);
			}

			if (isset($DEBUG['query']))
				echo "=$reg\n";
		}
		else
		{
			if (isset($DEBUG['query']))
				echo "=<empty>\n";

			if (strlen($f->reg))
			{
				$query = <<<SQL
					INSERT INTO `aircrafts`(`uid`, `reg`,`model`)
					VALUES($uid, '$f->reg', $model);
SQL;

				if (!mysql_query($query))
				{
					$error = seterrorinfo(__LINE__,
								sprintf("[%d] %s: %s",
									mysql_errno(), mysql_error(), query_style($query)));
				}
				else
				{
					if (isset($DEBUG['query']))
						echo query_style($query);

					$reg = mysql_insert_id();

					if (isset($DEBUG['query']))
						echo "=$reg\n";
				}
			}
		}

		mysql_free_result($result);
	}

	return $error;
}

function SQL_GetFlightDetails($dir, $scheduled, $airline, $code, &$details)
{
	global $DEBUG;

	$error = NULL;
	$details = NULL;

	$query = <<<SQL
		SELECT `id`, `aircraft`
		FROM `flights`
		WHERE `direction`='$dir'
		 AND `airline`=$airline
		 AND `code`='$code'
		 AND `scheduled`='$scheduled'
SQL;

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), query_style($query)));
	}
	else
	{
		if (isset($DEBUG['query']))
			echo query_style($query);

		$row = mysql_fetch_assoc($result);

		if (NULL == $row)
		{
			if (isset($DEBUG['query']))
				echo "=<empty>\n";
		}
		else
		{
			$details = $row;

			if (isset($DEBUG['query']))
				echo "=$details[id],$details[aircraft]\n";
		}

		mysql_free_result($result);
	}

	return $error;
}

define('VTF_DECREASE', 0);
define('VTF_INCREASE', 1);

function SQL_UpdateVisitsToFra($scheduled, $reg, $op)
{
	global $DEBUG;

	$error = NULL;
	$query = <<<SQL
		SELECT `num`, `current`, `previous`
		FROM `visits`
		WHERE `aircraft`=$reg;
SQL;
	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), query_style($query)));
	}
	else
	{
		if (isset($DEBUG['query']))
			echo query_style($query);

		$row = mysql_fetch_assoc($result);

		mysql_free_result($result);

		if (!$row)
		{
			if (isset($DEBUG['query']))
				echo "=<empty>\n";

			if (VTF_DECREASE == $op)	// "annulliert"
			{
				warn_once(__LINE__, "No visits found for '$reg'.");
			}
			else
			{
				$query = <<<SQL
					INSERT INTO `visits`(`aircraft`, `num`, `current`, `previous`)
					VALUES($reg, 1, '$scheduled', NULL);
SQL;
			}
		}
		else
		{
			$num = $row['num'];
			$current = $row['current'];
			$previous = $row['previous'];
			$row = NULL;

			if (isset($DEBUG['query']))
				echo "=$num,$current,".($previous ? $previous : "NULL")."\n";

			if (VTF_INCREASE == $op)
			{
				if ($scheduled <= $current)
				{
					$query = NULL;
				}
				else
				{
					$num++;
					$previous = $current ? "'$current'" : "NULL";

					$query = <<<SQL
						UPDATE `visits`
						SET `num`=$num,
							`current`='$scheduled',
							`previous`=$previous
						WHERE `aircraft`=$reg
SQL;
				}
			}
			else
			{
				if ($num < 1)
				{
					$query = NULL;
				}
				else if ($num == 1)
				{
					$query = <<<SQL
						DELETE FROM `visits`
						WHERE `aircraft`=$reg
SQL;
				}
				else
				{
					/*	From bulk INSERT in "fra-schedule.sql" we do not get `previous`
						even for `num` > 1, where normally this would be NOT NULL.
						Need to check for this also... */
					if (!$previous)
					{
						$query = <<<SQL
							SELECT MAX(`scheduled`) AS `scheduled`
							FROM
							(
								SELECT `scheduled`
								FROM `flights`
								WHERE `direction`='arrival' AND `aircraft` = $reg
								UNION ALL
								SELECT `scheduled`
								FROM `history`
								WHERE `direction`='arrival' AND `aircraft` = $reg
							) AS `flights`
SQL;

						$result = mysql_query($query);

						if (!$result)
						{
							$error = seterrorinfo(__LINE__,
										sprintf("[%d] %s: %s",
											mysql_errno(), mysql_error(), query_style($query)));
						}
						else
						{
							if (isset($DEBUG['query']))
								echo query_style($query);

							$row = mysql_fetch_assoc($result);

							mysql_free_result($result);

							if ($row)
							{
								$previous = $row['scheduled'];

								if (isset($DEBUG['query']))
									echo "=$previous\n";
							}
							else
							{
								$previous = NULL;

								if (isset($DEBUG['query']))
									echo "=<empty>\n";
							}
						}
					}

					if ($previous)
					{
						$num--;
						$query = <<<SQL
							UPDATE `visits`
							SET `num`=$num, `current`='$previous'
							WHERE `aircraft`=$reg
SQL;
					}
				}
			}
		}

		if ($query)
		{
			if (!mysql_query($query))
			{
				$error = seterrorinfo(__LINE__, $query.": ".mysql_error());
			}
			else
			{
				if (isset($DEBUG['query']))
				{
					echo query_style($query);
					echo "=OK\n";
				}
			}
		}
	}

	return $error;
}

function SQL_GetAirportId($airport)
{
	global $DEBUG;
	global $uid;

	$error = NULL;
	$query = <<<SQL
		SELECT `airports`.`id`
		FROM `airports`
		WHERE `iata`='$airport->iata'
		 AND `icao`='$airport->icao';
SQL;

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), query_style($query)));
	}
	else
	{
		if (isset($DEBUG['query']))
			echo query_style($query);

		if (mysql_num_rows($result))
		{
			$row = mysql_fetch_row($result);

			if ($row)
				$airport->id = $row[0];

			if (isset($DEBUG['query']))
				echo "=$airport->id\n";
		}
		else
		{
			// Not found, insert airport
			$query = <<<SQL
				INSERT INTO `airports`(`uid`, `iata`, `icao`, `name`)
				VALUES($uid, '$airport->iata',
					'$airport->icao', '$airport->name');
SQL;

			if (!mysql_query($query))
			{
				$error = seterrorinfo(__LINE__,
							sprintf("[%d] %s: %s",
								mysql_errno(), mysql_error(), query_style($query)));
			}
			else
			{
				$airport->id = mysql_insert_id();

				if (isset($DEBUG['query']))
				{
					echo query_style($query);
					echo "=OK\n";
				}
			}
		}

		if ($airport->id)
		{
			// Update flight with airport id
			$query = <<<SQL
				UPDATE `flights`
				SET `airport`=$airport->id
				WHERE `id`=$airport->fid;
SQL;

			if (!mysql_query($query))
			{
				$error = seterrorinfo(__LINE__,
							sprintf("[%d] %s: %s",
								mysql_errno(), mysql_error(), query_style($query)));
			}
			else
			{
				if (isset($DEBUG['query']))
				{
					echo query_style($query);
					echo "=OK\n";
				}
			}
		}

		mysql_free_result($result);
	}

	return $error;
}

function SQL_InsertFlight($dir, $airline, $code,
						  $scheduled, $expected, $model, $reg)
{
	global $DEBUG;
	global $uid;

	$error = NULL;

	$expected = $expected ? "'$expected'" : "NULL";

	if (!$reg)
		$reg = 'NULL';

	if (!$model)
		$model = 'NULL';

	$query = <<<SQL
		INSERT INTO `flights`
		(`uid`, `type`, `direction`, `airline`, `code`,
		 `scheduled`, `expected`, `aircraft`, `model`)
		VALUES(
		 $uid, 'pax-regular', '$dir', $airline, '$code',
		 '$scheduled', $expected, $reg, $model);
SQL;

	if (!mysql_query($query))
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), query_style($query)));
	}
	else
	{
		// Don't bother about id here...
		if (isset($DEBUG['query']))
		{
			echo query_style($query);
			echo "=OK\n";
		}
	}

	return $error;
}

function SQL_UpdateFlight($id, $expected, $model, $reg)
{
	global $DEBUG;
	global $uid;

	$error = NULL;

	// Don't overwrite `expected` with NULL!
	$expected = $expected ? "`expected`='$expected', " : "";

	if (!$reg)
		$reg = 'NULL';

	if (!$model)
		$model = 'NULL';

	$query = <<<SQL
		UPDATE `flights`
		SET $expected
		 `aircraft`=$reg,
		 `model`=$model
		WHERE `id`=$id;
SQL;

	if (!mysql_query($query))
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), query_style($query)));
	}
	else
	{
		if (isset($DEBUG['query']))
		{
			echo query_style($query);
			echo "=OK\n";
		}
	}

	return $error;
}

function SQL_DeleteFlight($dir, $airline, $code, $scheduled, &$aircraft)
{
	global $DEBUG;

	$error = NULL;

	/* Determine whether there is something to be deleted at all */
	$query = <<<SQL
		SELECT `id`, `aircraft`
		FROM `flights`
		WHERE `direction`='$dir'
		 AND `airline`=$airline
		 AND `code`='$code'
		 AND `scheduled`='$scheduled'
SQL;

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), query_style($query)));
	}
	else
	{
		if (isset($DEBUG['query']))
			echo query_style($query);

		$row = mysql_fetch_assoc($result);

		if (NULL == $row)
		{
			$id = NULL;
			$aircraft = NULL;

			if (isset($DEBUG['query']))
				echo "=<empty>\n";
		}
		else
		{
			$id = $row['id'];
			$aircraft = $row['aircraft'];

			if (isset($DEBUG['query']))
				echo "=$row[id],$aircraft\n";
		}

		mysql_free_result($result);

		if ($id)
		{
			/* Delete from `watchlist-notifications` first, which uses
			   `flights`.`is` a foreign key... */
			$query = <<<SQL
				DELETE
				FROM `watchlist-notifications`
				WHERE `flight`=$id
SQL;

			if (!mysql_query($query))
			{
				$error = seterrorinfo(__LINE__,
							sprintf("[%d] %s: %s",
								mysql_errno(), mysql_error(), query_style($query)));
			}
			else
			{
				if (isset($DEBUG['query']))
					echo query_style($query);

				$query = <<<SQL
					DELETE
					FROM `flights`
					WHERE `id`=$id
SQL;

				$result = mysql_query($query);

				if (!$result)
				{
					$error = seterrorinfo(__LINE__,
								sprintf("[%d] %s: %s",
									mysql_errno(), mysql_error(), query_style($query)));
				}
				else
				{
					if (isset($DEBUG['query']))
					{
						echo query_style($query);
						echo "=OK\n";
					}

					if (0 == mysql_affected_rows())
						warn_once(__LINE__, "No flight deleted: $dir-$airline-'$code'-'$scheduled'");
				}
			}
		}
	}

	return $error;
}

function SQL_FlightsToHistory()
{
	$error = NULL;
	$result = mysql_query('START TRANSACTION');

	if (!$result)
	{
		$error = seterrorinfo(__LINE__, sprintf("[%d] %s", mysql_errno(), mysql_error()));
	}
	else
	{
		$result = mysql_query('CREATE TEMPORARY TABLE `move flights`(`id` integer)');

		if (!$result)
		{
			$error = seterrorinfo(__LINE__, sprintf("[%d] %s", mysql_errno(), mysql_error()));
		}
		else
		{
			$query = <<<SQL
							INSERT INTO `move flights`
								SELECT `id`
								FROM `flights`
								WHERE (DATEDIFF(NOW(), IFNULL(`flights`.`expected`, `flights`.`scheduled`)) > 2)
								LIMIT 100
SQL;

			$result = mysql_query($query);

			if (!$result)
			{
				$error = seterrorinfo(__LINE__, sprintf("[%d] %s", mysql_errno(), mysql_error()));
			}
			else
			{
				$result = mysql_query("INSERT INTO `history`".
									  "  SELECT * FROM `flights`".
									  "    INNER JOIN `move flights` USING(`id`);");

				if (!$result)
				{
					$error = mysql_errno();
					$result = mysql_error();

					// [1062] Duplicate entry 'arrival-67-511-2014-04-27 11:15:00'
					if (1062 == $error)
					{
						if (preg_match("/(.*Duplicate entry ')([^']+)('.*)/i", $result, $m))
						{
							$result = sprintf("%s<a href='http://$_SERVER[SERVER_NAME]/".
											  "?page=rmdup&key=%s'>%s</a>%s",
											  $m[1],  urlencode($m[2]), $m[2], $m[3]);
						}
					}

					$error = seterrorinfo(__LINE__, sprintf("[%d] %s", $error, $result));
				}
				else
				{
					$result = mysql_query("DELETE `flights` FROM `flights`".
										  "  INNER JOIN `move flights` USING(`id`)");

					if (!$result)
						$error = seterrorinfo(__LINE__, sprintf("[%d] %s", mysql_errno(), mysql_error()));
				}
			}
		}
	}

	$result = mysql_query($error ? 'ROLLBACK' : 'COMMIT');

	if (!$result)
		$error = seterrorinfo(__LINE__, sprintf("[%d] %s", mysql_errno(), mysql_error()));

	return $error;
}

function SendWatchlistNotification($name, $email, $now, $fmt, $lang, $notifications)
{
	global $DEBUG;

	if ('de' == $lang)
		$lang = setlocale(LC_TIME, 'deu', 'deu_deu');
	else
		$lang = setlocale(LC_TIME, 'eng', 'english-uk', 'uk', 'enu', 'english-us', 'us', 'english', 'C');

	$today = mktime_c(gmstrftime('%d.%m.%Y', $now));

	if (NULL == $fmt)
		$fmt = '%+ %H:%M';

	$n = 0;
	$text = '';
	$update = '';

	if (isset($DEBUG['any']))
		echo "$email:\n";

	foreach ($notifications as $notification)
	{
		$offset = (int)(($notification['expected'] - $today) / 86400);

		$expected = strftime(preg_replace('/%\+/', "+$offset", $fmt),
							 $notification['expected']);

		$text .= "$expected\t$notification[reg]";

		if ($notification['comment'])
			$text .= "\t\"$notification[comment]\"\n";
		else
			$text .= "\n";

		if ($n++ > 0)
			$update .= ',';

		$update .= $notification['id'];
	}

	if (isset($DEBUG['any']))
		echo "$text";

	$to = mb_encode_mimeheader($name, 'ISO-8859-1', 'Q')."<$email>";
	$subject = mb_encode_mimeheader('watchlist', 'ISO-8859-1', 'Q');
	$header = sprintf(
		"From: %s <%s>\n".
		"Reply-To: %s\n".
		"Mime-Version: 1.0\n".
		"Content-type: text/plain; charset=ISO-8859-1\n".
		"Content-Transfer-Encoding: 8bit\n".
		"X-Mailer: PHP/%s\n",
		"FRA schedule",
		ADMIN_EMAIL_FROM,
		ADMIN_EMAIL,
		phpversion());

	$text = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');

	if (!mail($to, $subject, $text, $header))
	{
		$error = seterrorinfo(0, NULL);
	}
	else
	{
		if (isset($_GET['now']))
			$now = $_GET['now'];
		else
			$now = gmstrftime('%Y-%m-%d %H:%M:%S', $now);	/* $now is UTC */

		$query = <<<SQL
			UPDATE `watchlist-notifications`
			LEFT JOIN `watchlist`
				   ON `watchlist`.`id`=`watchlist-notifications`.`watch`
			INNER JOIN `users`
					ON `users`.`id`=`watchlist`.`user`
						AND `users`.`email`='$email'
			SET `watchlist-notifications`.`notified`='$now'
			WHERE `watchlist-notifications`.`id` IN(%s)
SQL;

		$query = sprintf($query, $update);

		if (isset($DEBUG['query']))
			echo query_style($query);

		if (mysql_query($query))
			$error = NULL;
		else
			$error = seterrorinfo(__LINE__, sprintf("[%d] %s", mysql_errno(), mysql_error()));
	}
}

function mysql_connect_db(&$hdbc, &$uid)
{
	$hdbc = mysql_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD);

	if (!$hdbc)
	{
		$error = seterrorinfo(__LINE__, sprintf("[%d] %s", mysql_errno(), mysql_error()));
	}
	else
	{
		if (!mysql_select_db(DB_NAME, $hdbc))
		{
			$error = seterrorinfo(__LINE__, sprintf("[%d] %s", mysql_errno(), mysql_error()));
		}
		else
		{
			mysql_set_charset("utf8");

			$result = mysql_query("SELECT `id` FROM `users` WHERE `name`='root'");

			if (!$result)
			{
				$error = seterrorinfo(__LINE__, sprintf("[%d] %s", mysql_errno(), mysql_error()));
			}
			else
			{
				$row = mysql_fetch_row($result);

				if (!$row)
				{
					$error = seterrorinfo(__LINE__, sprintf("[%d] %s", mysql_errno(), mysql_error()));
				}
				else
				{
					$error = NULL;
					$uid = $row[0];
				}

				mysql_free_result($result);
			}
		}
	}

	return $error;
}

$error = NULL;

$curl = curl_setup();

if (NULL == $curl)
{
	$error = geterrorinfo();
}
else
{
	// Iterate through arrival/departure tables awk()ing basic flight info
	$direction = array('arrival', 'departure');

	foreach ($direction as $dir)
	{
		if (isset($DEBUG['any']))
			printf("%s\n", $dir);

		$time_start = microtime(true);

		$error = CURL_GetFlights($curl, $dir, $flights);

		$time_end = microtime(true);
		$time = $time_end - $time_start;

		$n = $flights->count();

		$error = mysql_connect_db($hdbc, $uid);

		if (!$error)
		{
			while ($f = $flights->pop())
			{
				if (0 == strcmp('TRN', $f->model))	// no trains...
				{
					$n--;
				}
				else if (0 == strcmp('NULL', $f->expected))	// "annulliert"
				{
					$error = SQL_GetAirlineId($f, $airline);

					if (!$error)
					{
						$error = SQL_DeleteFlight($dir, $airline, $f->code, $f->scheduled, $reg);

						if (!$error && 'arrival' == $dir && $reg)
							$error = SQL_UpdateVisitsToFra($f->scheduled, $reg, VTF_DECREASE);
					}
				}
				else if ($f->scheduled)
				{
					$airline = NULL;
					$error = SQL_GetAirlineId($f, $airline);

					if ($airline)
					{
						// Get carrier id, if different from flight airline code
						// (operated by someone else)
						if ($airline != $f->carrier['code'])
							$error = SQL_GetCarrierId($f, $airline);
					}

					// model
					$model = NULL;
					$error = SQL_GetModelId($f, $model);

					// aircraft
					$reg = NULL;

					if ($f->reg && $model)
						$error = SQL_GetAircraftId($f, $model, $reg);

					// flight
					$details = NULL;
					$error = SQL_GetFlightDetails($dir, $f->scheduled, $airline, $f->code, $details);

					if (NULL == $details)
					{
						$error = SQL_InsertFlight($dir, $airline, $f->code,
												  $f->scheduled, $f->expected, $model, $reg);
					}
					else
					{
						$error = SQL_UpdateFlight($details['id'], $f->expected, $model, $reg);

						if (!$error && 'arrival' == $dir && $details['aircraft'])
						{
							if ($details['aircraft'] != $reg)
								$error = SQL_UpdateVisitsToFra($f->scheduled, $details['aircraft'], VTF_DECREASE);
						}
					}

					if (!$error && 'arrival' == $dir && $reg)
						$error = SQL_UpdateVisitsToFra($f->scheduled, $reg, VTF_INCREASE);
				}

				if (isset($DEBUG['query']))
					echo "\n/************************************/\n\n";

				unset($f);
			}

			if (isset($DEBUG['any']))
			{
				printf("---------------------------\n");
				printf("%lu Flüge gefunden.\n", $n);
				printf("    %s: %.3fs\n", 'Dauer', $time);
				printf("\n===========================\n\n");
			}

			mysql_close($hdbc);
		}

		unset($flights);
	}

	$error = mysql_connect_db($hdbc, $uid);

	if (!$error)
	{
		$flights = new vector;
		$airports = new vector;

		/* Query flights with NULL airport */
		$query = <<<SQL
			SELECT
			 `flights`.`id`,
			 `airlines`.`code`,
			 `flights`.`code`,
			 `flights`.`scheduled`,
			 `flights`.`direction`
			FROM `flights`
			LEFT JOIN `airlines` ON `flights`.`airline` = `airlines`.`id`
			WHERE `airport` IS NULL
			AND
			 (`scheduled` >= '$now'
			  OR `expected` >= '$now'
			  OR (TIME_TO_SEC(TIMEDIFF('$now', `scheduled`)) / 60 / 60) < 2)
			ORDER BY `scheduled`;
SQL;

		$result = mysql_query($query);

		if (!$result)
		{
			$error = seterrorinfo(__LINE__,
						sprintf("[%d] %s: %s",
							mysql_errno(), mysql_error(), query_style($query)));
		}
		else
		{
			if (isset($DEBUG['query']))
				echo query_style($query);

			while ($row = mysql_fetch_row($result))
			{
				$flights->push($row);

				if (isset($DEBUG['query']))
					echo "=$row[0],...\n";
			}
		}

		mysql_free_result($result);

		/* Get airports from flight details page */
		$error = CURL_GetFlightAirports($curl, $flights, $airports);

		while ($airport = $airports->pop())
		{
			$error = SQL_GetAirportId($airport);
			unset($airport);
		}

		unset($flights);
		unset($airports);

		mysql_close($hdbc);

		/* betriebsrichtung.html */
		$betriebsrichtung = curl_download($curl, "http://$rwyinfo/betriebsrichtung/betriebsrichtung.html", 5);

		$file = @fopen('data/betriebsrichtung.html', 'w');

		if ($file)
		{
			fwrite($file, $betriebsrichtung);
			fclose($file);
		}
	}

	curl_close($curl);
}

if (!$error)
{
	$error = mysql_connect_db($hdbc, $uid);

	if (!$error)
	{
		/* Add watches to `watchlist-notifications` table */
		if (isset($DEBUG['any']))
			echo "\n";

		if (isset($_GET['now']))
			$now = "'$_GET[now]'";
		else
			$now = 'NOW()';

		$query = <<<SQL
			INSERT INTO `watchlist-notifications`(`flight`, `watch`)

			SELECT `flights`.`id`, `watchlist`.`id`
			FROM `watchlist`
			INNER JOIN `aircrafts`
				ON `aircrafts`.`reg` LIKE REPLACE(REPLACE(`watchlist`.`reg`, '*', '%'), '?', '_')
			INNER JOIN
				(SELECT
					`id`,
					`direction`,
					IFNULL(`expected`, `scheduled`) AS `expected`,
					`aircraft`
				 FROM `flights`
				 WHERE `aircraft` IS NOT NULL)
				 	AS `flights`
				ON `aircrafts`.`id` = `flights`.`aircraft`
			LEFT JOIN `watchlist-notifications`
				ON `watchlist-notifications`.`flight` = `flights`.`id`
			WHERE `watchlist`.`notify` = TRUE
				AND `watchlist-notifications`.`flight` IS NULL
				AND 'arrival' = `flights`.`direction`
				AND `expected` > $now
			FOR UPDATE
SQL;

		if (!mysql_query($query))
		{
			$error = seterrorinfo(__LINE__,
						sprintf("[%d] %s: %s",
							mysql_errno(), mysql_error(), query_style($query)));
		}
		else
		{
			/* Check whether or which notifications are to be sent */
			if (isset($DEBUG['query']))
				echo query_style($query);

			$query = <<<SQL
				SELECT
					`watchlist-notifications`.`id` AS `id`,
					UNIX_TIMESTAMP($now) AS `now`,
					UNIX_TIMESTAMP(IFNULL(`flights`.`expected`, `flights`.`scheduled`)) AS `expected`,
					CONCAT(`airlines`.`code`,
						   `flights`.`code`) AS `flight`,
					`aircrafts`.`reg` AS `reg`,
					`watchlist`.`comment` AS `comment`,
					`users`.`name` AS `name`,
					`users`.`email` AS `email`,
					`users`.`notification-timefmt` AS `fmt`,
					`users`.`language` AS `lang`
				FROM `watchlist-notifications`
				LEFT JOIN `watchlist`
					ON `watchlist-notifications`.`watch` = `watchlist`.`id`
				LEFT JOIN `flights`
					ON `watchlist-notifications`.`flight` = `flights`.`id`
				LEFT JOIN `airlines`
					ON `flights`.`airline` = `airlines`.`id`
				LEFT JOIN `aircrafts`
					ON `flights`.`aircraft` = `aircrafts`.`id`
				LEFT JOIN `users`
					ON `watchlist`.`user` = `users`.`id`
				WHERE IFNULL(`flights`.`expected`, `flights`.`scheduled`) > $now
				AND `notified` IS NULL
				AND
				 TIME($now)
				 BETWEEN `users`.`notification-from`
					 AND `users`.`notification-until`
				ORDER BY
					`email` ASC,
					`expected` ASC
SQL;

			$result = mysql_query($query);

			if (!$result)
			{
				$error = seterrorinfo(__LINE__,
							sprintf("[%d] %s: %s",
								mysql_errno(), mysql_error(), query_style($query)));
			}
			else
			{
				if (isset($DEBUG['query']))
					echo query_style($query);

				$text = NULL;
				$name = NULL;
				$email = NULL;
				$watch = NULL;
				$time = NULL;
				$fmt = NULL;
				$lang = 'en';

				$notifications = array();

				$row = mysql_fetch_assoc($result);

				while ($row)
				{
					if ($email != $row['email'])
					{
						/* We get here every time $row['email'] changes, at least */
						/* once at the beginning, i.e. $now and $fmt will be set, */
						/* if one row has been found  */
						if ($email != NULL)
						{
							/* Flush */
							SendWatchlistNotification($name, $email, $time, $fmt, $lang, $notifications);
							$notifications = array();
						}

						/* Remember first ID of new email */
						$email = $row['email'];
						$name = $row['name'];
						$time = $row['now'];
						$fmt = $row['fmt'];
						$lang = $row['lang'];
					}

					$notifications[] = array(
							'id'       => $row['id'],
							'expected' => $row['expected'],
							'flight'   => $row['flight'],
							'reg'      => $row['reg'],
							'comment'  => $row['comment'],
						);

					$row = mysql_fetch_assoc($result);
				}

				if ($email)
					SendWatchlistNotification($name, $email, $time, $fmt, $lang, $notifications);

				unset($notifications);

				mysql_free_result($result);
			}
		}

		/* Delete notifications for flights having been arrived prior to yesterday */
		$query = <<<SQL
			DELETE `watchlist-notifications`
			FROM `watchlist-notifications`
			INNER JOIN `flights`
			        ON `flights`.`id`=`watchlist-notifications`.`flight`
			WHERE (DATEDIFF($now, IFNULL(`flights`.`expected`, `flights`.`scheduled`)) > 1)
SQL;

		if (isset($DEBUG['query']))
			echo query_style($query);

		if (!mysql_query($query))
			$error = seterrorinfo(__LINE__, sprintf("[%d] %s", mysql_errno(), mysql_error()));

		/* Move outdated flights to history table */
		if (!$error)
			$error = SQL_FlightsToHistory();

		mysql_close($hdbc);
	}
}

if ($errorinfo)
{
	echo $errorinfo;

	mail(mb_encode_mimeheader(ADMIN_NAME, 'ISO-8859-1', 'Q').'<'.ADMIN_EMAIL.'>',
		 "fra-schedule - getflights.php: error",
		 "$errorinfo",
		 "From: fra-schedule");
}

if ($warning)
{
	echo $warning;

	mail(mb_encode_mimeheader(ADMIN_NAME, 'ISO-8859-1', 'Q').'<'.ADMIN_EMAIL.'>',
		 "fra-schedule - getflights.php: warning",
		 "$warning",
		 "From: fra-schedule");
}

if ($info)
{
	echo $info;

	mail(mb_encode_mimeheader(ADMIN_NAME, 'ISO-8859-1', 'Q').'<'.ADMIN_EMAIL.'>',
		 "fra-schedule - getflights.php: info",
		 "$info",
		 "From: fra-schedule");
}

if (isset($DEBUG['any']))
	echo "\n\n=== fin. ===\n";

fin:
//printf("\ntotal duration: %f\n", microtime(TRUE) - $script_start);

if (isset($DEBUG['fmt']))
{
	if ('htm'  == $DEBUG['fmt'] ||
		'html' == $DEBUG['fmt'])
	{

?>
</pre>
</body>
</html>
<?php
	}
}

?>
