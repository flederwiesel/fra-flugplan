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

/******************************************************************************
 *
 *  Debug options: debug=[url,arrival,departure,airports,awk,query]
 *                 fmt=[htm|html|...]
 *
 *   Test options: baseurl=www.frankfurt-airport.de
 *                 now={localtime as '%Y-%m-%d %H:%M:%S'}
 *
 ******************************************************************************/

error_reporting(E_ALL);

mb_internal_encoding('UTF-8');

include ".config";
include "classes/vector.php";
include "classes/awk.php";

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
<?
	}
}

function geterrorinfo()
{
	global $error;
	return $error;
}

function seterrorinfo($line, $info)
{
	global $error;

	if (!$error)
		$error = '';

	$error .= __FILE__;

	if ($info)
	{
		$error .= "($line): ERROR: $info\n";
	}
	else
	{
		$info = error_get_last();
		$error .= sprintf("(%u): " , $line ? $line : $info['line']);
		$error .= sprintf("[%d] %s", $info['type'], $info['message']);
	}

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

function query_style($query)
{
	// Cleanup query for single line display with 1 space separator
	$query = preg_replace('/[ \t\r]*\n[ \r]*/', ' ', $query);
	$query = preg_replace('/[ \t]*(;?)$/', '\\1', $query);

	return $query."\n";
}

function curl_setup()
{
	// is cURL installed yet?
	if (!function_exists('curl_init'))
	{
		$curl = NULL;
		seterrorinfo(__LINE__, 'cURL is not installed!');
	}
	else
	{
		// OK cool - then let's create a new cURL resource handle
		$curl = curl_init();

		// Now set some options (most are optional)
		// http://en.php.net/curl_setopt

		// Set a referer
		curl_setopt($curl, CURLOPT_REFERER, "http://www.flederwiesel.com/fra-schedule");

		// User agent
		//curl_setopt($curl, CURLOPT_USERAGENT, "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.9.2.28) Gecko/20120306 Firefox/3.6.28 ( .NET CLR 3.5.30729; .NET4.0E)");

		// Include header in result? (0 = yes, 1 = no)
		curl_setopt($curl, CURLOPT_HEADER, 0);

		// Upon "301 Moved Permanently", follow the redirection
		// This is necessary since local url '.../airportcity' is a directory,
		// but url would have needed a trailing backslash then...
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

		// Should cURL return or print out the data? (true = return, false = print)
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

		curl_setopt($curl, CURLOPT_COOKIESESSION, TRUE);	// start new cookie "session"
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, FALSE);

		// Timeout in seconds
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);

		// Need to use a proxy?
		if (file_exists('.curlrc'))
		{
			$curlrc = file('.curlrc');

			if ($curlrc)
			{
				curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, 0);
				curl_setopt($curl, CURLOPT_PROXY, trim($curlrc[0]));
				curl_setopt($curl, CURLOPT_PROXYUSERPWD, trim($curlrc[1]));

				unset($curlrc);
			}
		}
	}

	return $curl;
}

function curl_download($curl, $url)
{
	curl_setopt($curl, CURLOPT_URL, $url);

	return curl_exec($curl);
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
					$error = seterrorinfo(__LINE__, implode(",", error_get_last()));
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
		$error = seterrorinfo(__LINE__, implode(",", error_get_last()));
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
			$htm = curl_download($curl, $url);

			curl_setopt($curl, CURLOPT_COOKIESESSION, FALSE);	// reuse session cookie

			if (0 == strlen($htm))
			{
				$page = 0;

				if (curl_errno($curl))
					$error = seterrorinfo(__LINE__, curl_error($curl));
			}
			else
			{
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
		}
		while ($page > 0);
	}

	return $error;
}

function CURL_GetFlightDetails($curl, &$airports)
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
		$error = seterrorinfo(__LINE__, implode(",", error_get_last()));
	}
	else
	{
		$error = NULL;

		/* Get airport IATA/ICAO from flight details page */
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
						sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
		}
		else
		{
			if (isset($DEBUG['query']))
				echo query_style($query);

			while ($fi = mysql_fetch_row($result))
			{
				if (isset($DEBUG['query']))
					echo "=$fi[0],...\n";

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
					$htm = curl_download($curl, $url);
					$len = strlen($htm);

					if (0 == $len)
					{
						if (curl_errno($curl))
							$error = seterrorinfo(__LINE__, curl_error($curl));
					}
					else
					{
						if (isset($DEBUG['airports']))
							echo "$htm\n";
					}
				}
				while (0 == $len && --$retry);

				if ($len > 0)
				{
					$airport = $airports->push(new AirportInfo($fi[0]));
					$awk->execute($htm, $airport);
				}

				/* Set srcipt execution limit. If set to zero, no time limit is imposed. */
				set_time_limit(0);
			}

			mysql_free_result($result);
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
	$query = "SELECT `id` FROM `airlines` WHERE `code`='".$f->airline."';";

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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
				$query = "INSERT INTO `airlines`(`uid`, `code`, `name`)".
						 " VALUES($uid, '".$f->carrier['code']."', '".$f->carrier['name']."');";

				if (mysql_query($query))
				{
					if (isset($DEBUG['query']))
						echo query_style($query);

					$airline = mysql_insert_id();

					if (isset($DEBUG['query']))
						echo "=$airline\n";

					warn_once(__LINE__, "Inserted airline $f->airline as \"".$f->carrier['name']."\"".
										" ($dir: flight $f->airline$f->code \"$f->scheduled\").");
				}
				else
				{
					$airline = NULL;
					$error = seterrorinfo(__LINE__,
								sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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
	$query = "SELECT `id` FROM `airlines` WHERE `code`='".$f->carrier['code']."';";

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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
				$query = "INSERT INTO `airlines`(`uid`, `code`, `name`)".
						 " VALUES($uid, '".$f->carrier['code']."', '".$f->carrier['name']."');";

				if (mysql_query($query))
				{
					if (isset($DEBUG['query']))
						echo query_style($query);

					$carrier = mysql_insert_id();

					if (isset($DEBUG['query']))
						echo "=$carrier\n";
				}
				else
				{
					$error = seterrorinfo(__LINE__,
								sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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

	$query = "SELECT `id` FROM `models` WHERE `icao`='$f->model';";

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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
				$query = "INSERT INTO `models`(`uid`, `icao`,`name`) VALUES($uid, '$f->model', '');";

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
								sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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
	$query = "SELECT `id` FROM `aircrafts` WHERE `reg`='$f->reg';";

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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
				$query = "INSERT INTO `aircrafts`(`uid`, `reg`,`model`)".
						 " VALUES($uid, '$f->reg', $model);";

				if (!mysql_query($query))
				{
					$error = seterrorinfo(__LINE__,
								sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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

	$query = "SELECT `id`, `aircraft` ".
		"FROM `flights` ".
		"WHERE `direction`='$dir'".
		" AND `airline`=$airline".
		" AND `code`='$code'".
		" AND `scheduled`='$scheduled'";

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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
	$query = "SELECT `num`, `current`, `previous` FROM `visits` WHERE `aircraft`=$reg;";
	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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
				$query = "INSERT INTO `visits`(`aircraft`, `num`, `current`, `previous`) ".
						 "VALUES($reg, 1, '$scheduled', NULL);";
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
					$query = "UPDATE `visits` ".
							 "SET `num`=".($num + 1).", `current`='$scheduled'".
							 ($current ? ", `previous`='$current' " : " ").
							 "WHERE `aircraft`=$reg";
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
					$query = "DELETE FROM `visits` ".
							 "WHERE `aircraft`=$reg";
				}
				else
				{
					/*	From bulk INSERT in "fra-schedule.sql" we do not get `previous`
						even for `num` > 1, where normally this would be NOT NULL.
						Need to check for this also... */
					if (!$previous)
					{
						$query = "SELECT MAX(`scheduled`) AS `scheduled`".
									"FROM".
									"(".
									"	SELECT `scheduled`".
									"	FROM `flights`".
									"	WHERE `direction`='arrival' AND `aircraft` = $reg".
									"	UNION ALL".
									"	SELECT `scheduled`".
									"	FROM `history`".
									"	WHERE `direction`='arrival' AND `aircraft` = $reg".
									") AS `flights`";

						$result = mysql_query($query);

						if (!$result)
						{
							$error = seterrorinfo(__LINE__,
										sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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
						$query = "UPDATE `visits` ".
								 "SET `num`=".($num - 1).", `current`='$previous' ".
								 "WHERE `aircraft`=$reg";
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
	$query = "SELECT `airports`.`id` FROM `airports` WHERE `iata`='".$airport->iata."'".
			 " AND `icao`='".$airport->icao."';";

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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
			$query = "INSERT INTO `airports`(`uid`, `iata`, `icao`, `name`)".
					 " VALUES($uid, '".$airport->iata."', '".
					 $airport->icao."', '".$airport->name."');";

			if (!mysql_query($query))
			{
				$error = seterrorinfo(__LINE__,
							sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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
			$query = "UPDATE `flights` SET `airport`=$airport->id WHERE `id`=$airport->fid;";

			if (!mysql_query($query))
			{
				$error = seterrorinfo(__LINE__,
							sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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

	$query = "INSERT INTO `flights` ".
		"(`uid`, `type`, `direction`, `airline`, `code`, ".
		"`scheduled`, `expected`, `aircraft`, `model`) ".
		"VALUES(".
		"$uid, 'pax-regular', '$dir', $airline, '$code', ".
		"'$scheduled', ".
		($expected ? "'$expected'" : "NULL").", ".
		($reg ? $reg : "NULL").", ".
		($model ? "$model": "NULL").");";

	if (!mysql_query($query))
	{
		$error = seterrorinfo(__LINE__,
					sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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

	$query = "UPDATE `flights` SET ".
		($expected ? "`expected`='$expected', " : "").	// Don't overwrite `expected` with NULL!
		"`aircraft`=".($reg ? $reg : "NULL").",".
		"`model`=".($model ? "$model" : "NULL")." ".
		"WHERE `id`=$id;";

	if (!mysql_query($query))
	{
		$error = seterrorinfo(__LINE__,
					sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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
	global $uid;

	$error = NULL;

	/* Determine whether there is something to be deleted at all */
	$query = "SELECT `id`, `aircraft` FROM `flights` ".
		"WHERE `direction`='$dir'".
		" AND `airline`=$airline".
		" AND `code`='$code'".
		" AND `scheduled`='$scheduled'";

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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
			$query = "DELETE FROM `flights` WHERE `id`=$id";

			$result = mysql_query($query);

			if (!$result)
			{
				$error = seterrorinfo(__LINE__,
							sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
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
			$result = mysql_query("INSERT INTO `move flights` ".
								  "  SELECT `id` ".
								  "    FROM `flights` ".
								  "      WHERE IFNULL(`expected`, `scheduled`) < ".
								  "        (SELECT SUBTIME(CONVERT_TZ(UTC_TIMESTAMP(), ".
								  "         '+00:00', '+01:00'), '1 00:00:00.0')) ".
								  "         LIMIT 100");

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
					$error = seterrorinfo(__LINE__, sprintf("[%d] %s", mysql_errno(), mysql_error()));
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

$error = NULL;
$warning = NULL;

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
				$uid = $row[0];
				unset($row);
			}


			mysql_free_result($result);
		}

		if (!$error)
		{
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

					while ($f = $flights->shift())
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
				}

				unset($flights);

				/* Get airports from flight details page */
				$airports = new vector;
				$error = CURL_GetFlightDetails($curl, $airports);

				while ($airport = $airports->shift())
				{
					$error = SQL_GetAirportId($airport);
					unset($airport);
				}

				unset($airports);

				/* betriebsrichtung.html */
				$betriebsrichtung = curl_download($curl, "http://$rwyinfo/betriebsrichtung/betriebsrichtung.html");

				$file = @fopen('data/betriebsrichtung.html', 'w');

				if ($file)
				{
					fwrite($file, $betriebsrichtung);
					fclose($file);
				}

				curl_close($curl);
			}
		}

		/* Move outdated flights to history table */
		if (!$error)
			$error = SQL_FlightsToHistory();
	}

	mysql_close($hdbc);
}

if ($error)
	echo $error;

if ($warning)
	echo $warning;

if ($error || $warning)
	mail(mb_encode_mimeheader(ADMIN_NAME, 'ISO-8859-1', 'Q').'<'.ADMIN_EMAIL.'>',
		 'fra-schedule - getflights.php: '.($error ? 'error' : 'warning'),
		 "$error\n----\n\n$warning", 'From: fra-schedule');

if (isset($DEBUG['any']))
	echo "\n\n=== fin. ===\n";

if (isset($DEBUG['fmt']))
{
	if ('htm'  == $DEBUG['fmt'] ||
		'html' == $DEBUG['fmt'])
	{
?>
</pre>
</body>
</html>
<?
	}
}

?>
