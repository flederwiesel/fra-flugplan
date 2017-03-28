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
 *  Debug options: debug=[url,json,jflights,flights,sql]
 *                 fmt=[htm|html|...]
 *
 *   Test options: prefix=localhost/fra-schedule/fra-schedule-trunk/
 *                 time={localtime as '%Y-%m-%dT%H:%M:%S%z'}
 *
 ******************************************************************************/

/*
#!/usr/bin/php -f
*/

$script_start = microtime(TRUE);

error_reporting(E_ALL | E_STRICT);

mb_internal_encoding('UTF-8');

ini_set('max_execution_time', 180);

include ".config";
include "classes/etc.php";
include "classes/curl.php";
include "classes/vector.php";

/* Create dir for warn_once() */
$datadir = "$_SERVER[DOCUMENT_ROOT]/var/run/fra-schedule";
$ignorelist = "$datadir/warnings.ignore";

/* Initialise warnings for warn_once() */
if (file_exists($datadir))
{
	if (!is_dir($datadir))
		die(seterrorinfo(__LINE__, $datadir));
}
else
{
	if (!mkdir($datadir, 0770, true))
		die(seterrorinfo(__LINE__, $datadir));
}

if (!file_exists($ignorelist))
{
	$ignore = array();
}
else
{
	$ignore = file($ignorelist);

	foreach($ignore as &$entry)
		$entry = trim($entry);

	unset($entry);
}

/* Initialise variables */

$errorinfo = NULL;
$warning = NULL;
$info = NULL;

$tz = date_default_timezone_set('Europe/Berlin');

$now = new StdClass();
$now->time_t = time();
$prefix = '';
$items = 50;

if (0)
	$lookback = 60 * 60;	/*[s]*/
else
	$lookback = 0;

/* If no `esti` time is set for departure,
 estimate $defer from now, so flights will still be visible */
$defer = 5 * 60;	/*[s]*/

/* Debug features */
if (isset($_GET['prefix']))
{
	/* May be overridden for testing */
	$prefix = $_GET['prefix'];

	$len = strlen($prefix);

	if (substr($prefix, $len - 1, 1) != '/')
		$prefix .= '/';

	$defer = 60 * 60;	/*[s]*/
	$lookback = 0;
	$items = 3;
}

if (isset($_GET['time']))
{
	$now->atom = $_GET['time'];
	$now->time_t = strtotime($now->atom);
}

$now->atom = date(DATE_ISO8601, $now->time_t);

if (isset($_GET['debug']))
{
	$flags = explode(",", $_GET['debug']);

	if ('full' == $_GET['debug'])
	{
		$full = 'url,json,flights,sql';

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
	if ('htm' == $DEBUG['fmt'])
		$DEBUG['fmt'] = 'html';

	if ('html' == $DEBUG['fmt'])
	{
?>
<!doctype html>
<html>
<head>
	<meta charset="UTF-8">
	<title>getflights &middot; debug output</title>
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
    json
    jflights
    flights
    sql

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
	global $ignorelist;
	global $ignore;
	global $warning;

	/* Check whether warning already issued */
	foreach ($ignore as $entry)
	{
		if ($entry == $info)
			return;
	}

	if (!$warning)
		$warning = '';

	$warning .= __FILE__."($line): $info\n";
	$ignore[] = $info;

	/* Save warning */
	$file = fopen($ignorelist, 'a+');

	if ($file)
	{
		fwrite($file, "$info\n");
		fclose($file);
	}
}

function warn($line, $text)
{
	global $warning;

	if (!$warning)
		$warning = '';

	$warning .= __FILE__."($line): $text\n";
}

function info($line, $text)
{
	global $info;

	if (!$info)
		$info = '';

	$info .= __FILE__."($line): $text\n";
}

function unify_query($query)
{
	// Cleanup query for single line display with 1 space separator
	$query = preg_replace('/[ \t\r]*\n[ \t]*/', ' ', $query);
	$query = preg_replace('/[ \t]*(;?)$/', '\\1', $query);
	$query = preg_replace('/^[ \t]*/', '', $query);

	$query = preg_replace('/\([ \t]+/', '(', $query);
	$query = preg_replace('/[ \t]+\)/', ')', $query);

	return $query."\n";
}

function unify_html($html)
{
	global $DEBUG;

	if (isset($DEBUG['fmt']))
	{
		if ('html' == $DEBUG['fmt'])
		{
			$html = str_replace(array("\r", "\n", "<br>"),
								array(" ", " ", "&lt;br&gt;"), htmlspecialchars($html));

			$html = preg_replace('/>[ \t]+</', '><', $html);
		}
	}

	return $html;
}

// Insert hyhen into reg, based on regex
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
		'/^JU/', 2,
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

/*
// representation of airline as decoded from JSON
{
	"id": "LH",
	"name": "Lufthansa",
	"t": 1,			// [null] Terminal
	"h": "A",		// [null] Halle
	"s": "260-338"	// [null] Schalter
}

// representation of airport as decoded from JSON
{
	"id": "YUL",
	"icao": "CYUL",
	"nameshort": "Montreal",
	"name": "Montreal-Pierre Elliot Trudeau",
	"land": "Kanada",
	"region": "am",
	"regionorg": "Nord-Amerika I",
	"soend": "2016-11-06T06:00:00+0100",	// = [null] Sommerzeit Ende
	"sostart": "2016-03-13T07:00:00+0100",	// = [null] Sommerzeit Start
	"uso": -4, 	// [null] Unterschied Sommer
	"uwi": -5	//        Unterschied Winter
}

// representation of aircraft as decoded from JSON
{
	"id": "A319",
	"name": "A319/ACJ",
	"fab": "Airbus"
}
*/

// representation of the flight as decoded from JSON
class jflight
{
	public $id; 		// = "a20151222lh1013"
	// last update
	public $lu;			// = "2015-12-07T05;31;58+0100"
	// flight
	public $typ;		// = enum { "P", "F" }
	public $fnr;		// = "LH 1013"
	public $sched;		// = "2015-12-07T05-12-22T16;10;00+0100"
	public $esti;		// = [null] "2015-12-05T16;21;00+0100"
	// airline
	public $al;			// = "LH"
	public $alname;		// = "Lufthansa"
	// airport
	public $iata;		// = "BRU"
	public $apname;		// = "Br\u00fcssel"
	// diversion
	public $div;		// = "CGN"
	public $divname;	// = "K\u00f6ln/Bonn"
	// route???
	public $rou;		// = [null] "ABV"
	public $rouname;	// = [null] "Abuja"
	// aircraft
	public $ac;			// = [null?] "TRN"
	public $reg;		// = [null] "DABFA"

	// ...
	public $status; 	// = [null] enum
	/* {
		{ 'de', 'en', 'zh' },
		{ 'storniert', 'storniert', 'storniert' },
		{ 'annulliert', 'cancelled', '取消' },
		{ 'umgeleitet', 'diverted', '航线改道' },
		{ 'versp\u00e4tet', 'versp\u00e4tet', 'versp\u00e4tet' },
		{ 'versp\u00e4tet auf ...', 'delayed to' },
		{ 'Ankunft vom', },
		{ 'im Anflug', 'approaching', '在飞行中' },
		{ 'gelandet', 'landed', '已着陆' },
		{ 'auf Position', 'auf Position', 'auf Position', },
		{ 'Gep\u00e4ckausgabe', 'baggage delivery', '托运行李领取' },
		{ 'Gep\u00e4ckausgabe beendet', 'baggage delivery finished', '托运行李领取已结束' },
		{ 'Neues Gate', 'Neues Gate', 'Neues Gate' },
		{ 'Gate offen', 'Gate open', '登机口开放' },
		{ 'Aufruf', 'Aufruf', 'Aufruf' },
		{ 'Boarding', 'Boarding', 'Boarding' },
		{ 'geschlossen', 'closed', '已关闭' },
		{ 'gestartet', 'started' '已起飞' },
		{ 'Zug', 'train', '火车' },
	} */

	public $terminal;	// = [null] enum { 1, 2 }
	public $halle;		// = [null] "A"
	public $ausgang;	// = [null] "A2"
	public $gate;		// = [null] "A25"
	public $schalter;	// = [null] "260-338"
	public $stops;		// = [null] 0

	// code share
	public $cs;			// = [null] array("", "")

	// unknown
	public $s;			// = [null] enum { false, true }
	public $flstatus;	// = [null] enum { 0, 1, 2, 3 }
	public $duration;	// = [null] 60

	public $lang;		// = "de"
}

abstract class FlightStatus
{
	const CANCELLED = -2;
	const IGNORE = -1;
	const UNDEFINED = 0;
	const APPROACHING = 1;
	const ARRIVED = 2;
	const BOARDING = 3;
	const DEPARTED = 4;
}

class airline
{
	public $id;
	public $code;
	public $name;

	public function __construct($al, $alname)
	{
		$this->id = 0;
		$this->code = $al;
		$this->name = $alname;
	}
}

class airport
{
	public $id;
	public $iata;
	public $icao;
	public $name;
	public $country;

	public function __construct($iata = NULL)
	{
		$this->id = 0;
		$this->iata = $iata;
		$this->icao = NULL;
		$this->name = NULL;
		$this->country = (object) [ 'id' => 0, 'name' => '' ];
	}
}

class aircrafttype
{
	public $id;
	public $icao;
	public $name;

	public function __construct($icao = NULL)
	{
		$this->id = 0;
		$this->icao = $icao;
		$this->name = NULL;
	}
}

class aircraft
{
	public $id;
	public $reg;
	public $type;

	public function __construct($reg, $type)
	{
		$this->id = 0;
		$this->reg = $reg;
		$this->type = new aircrafttype($type);
	}
}

// representation of the flight as we are working with in this script
class flight
{
	public $id;
	public $type;		// type = enum { "P", "F" }
	public $airline;	// airline = { id = 0, code = "LH", name = "Lufthansa" }
	public $fnr;		// fnr = "1013"
	public $scheduled;	// scheduled = "2015-12-07T05-12-22T16;10;00+0100"
	public $expected;	// expected = [null] "2015-12-05T16;21;00+0100"
	public $airport;	// airport = { id = 0, iata = "BRU", icao = "EBBR", name = "Brüssel" }
	public $aircraft;	// ac = { id = 0, reg = "D-AIRY", tid = 0, icao = "A306" }
	public $status;		// status = enum FlightStatus()
	public $lu;			// last update = "2015-12-07T05;31;58+0100"

	public function __construct($type, $al, $alname, $fnr, $sched, $esti, $ac, $reg, $iata, $status, $lu)
	{
		$this->id = 0;
		$this->type = $type;
		$this->airline = new airline($al, $alname);
		$this->fnr = $fnr;
		$this->scheduled = $sched;
		$this->expected = $esti;
		$this->airport = new airport($iata);
		$this->aircraft = new aircraft($reg, $ac);
		$this->status = $status;
		$this->lu = $lu;
	}
}

// Get JSON error messages
$constants = get_defined_constants(true);
$json_errors = array();

foreach ($constants["json"] as $name => $value)
{
	if (0 == strncmp($name, "JSON_ERROR_", 11))
		$json_errors[$value] = $name;
}

function CURL_GetAirline(/* in */ $curl, /* in/out */ &$airline)
{
	global $DEBUG;
	global $prefix;

	$error = NULL;
	$url = "http://${prefix}www.frankfurt-airport.com/de/_jcr_content.airlines.json";

	if (isset($DEBUG['url']))
		echo "$url\n";

	$retry = 3;

	do
	{
		/* Set script execution limit. If set to zero, no time limit is imposed. */
		set_time_limit(0);

		$error = $curl->exec($url, $json, 5);
	}
	while (!$error && !$json && --$retry);

	if ($error)
	{
		/* This is certainly a html error document... */
		if ($json)
		{
			$json = unify_html($json);
			$error = seterrorinfo(__LINE__, "$error: $url: `$json`");
		}
		else
		{
			$error = seterrorinfo(__LINE__, "$error: $url");
		}
	}
	else
	{
		if (!$json)
		{
			$error = seterrorinfo(__LINE__, "Empty response: $url");
		}
		else
		{
			$obj = json_decode($json);

			if (NULL == $obj)
			{
				$error = seterrorinfo(__LINE__, "json_decode($json)");
				$result = -1;
			}
			else
			{
				$error = NULL;

				foreach ($obj->data as $idx => $value)
				{
					$a = (object)$value;

					if ($a->id == $airline->code)
					{
						if (isset($DEBUG['jflights']))
						{
							echo json_encode($a, JSON_PRETTY_PRINT);
							echo "\n";
						}

						$airline->name = $a->name;
						break;
					}
				}
			}
		}
	}

	return $error;
}

function CURL_GetAirport(/* in */ $curl, /* in/out */ &$airport)
{
	global $DEBUG;
	global $prefix;

	$result = mysql_query("SELECT `id`,`de`,`alpha-2` FROM `countries`");

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), unify_query($query)));
	}
	else
	{
		$countries = array();

		while ($row = mysql_fetch_assoc($result))
			$countries["$row[de]"] = $row['id'];

		foreach (array( 'USA' => 'Vereinigte Staaten von Amerika',
						'Korea-Süd' => 'Südkorea',
						'Großbritannien' => 'Vereinigtes Königreich',
						'Weißrußland' => 'Weißrussland',
						'Äquatorial-Guinea' => 'Äquatorialguinea',
						'Saint Lucia' => 'St. Lucia',
						'Ver.Arab.Emirate' => 'Vereinigte Arabische Emirate',
						'Bosnien-Herzegow' => 'Bosnien und Herzegovina',
						'Dominikan. Rep.' => 'Dominikanische Republik',
						'Niederländische Antillen' => 'Curaçao',
						'Bangladesh' => 'Bangladesch',
						) as $alias => $name)
		{
			$countries["$alias"] = $countries["$name"];
		}

		$error = NULL;
		$url = "http://${prefix}www.frankfurt-airport.com/de/_jcr_content.airports.json".
				($prefix ? "?local" : "");

	if (isset($DEBUG['url']))
		echo "$url\n";

	$retry = 3;

	do
	{
		/* Set script execution limit. If set to zero, no time limit is imposed. */
		set_time_limit(0);

		$error = $curl->exec($url, $json, 5);
	}
	while (!$error && !$json && --$retry);

	if ($error)
	{
		/* This is certainly a html error document... */
		if ($json)
		{
			$json = unify_html($json);
			$error = seterrorinfo(__LINE__, "$error: $url: `$json`");
		}
		else
		{
			$error = seterrorinfo(__LINE__, "$error: $url");
		}
	}
	else
	{
		if (!$json)
		{
			$error = seterrorinfo(__LINE__, "Empty response: $url");
		}
		else
		{
			$obj = json_decode($json);

			if (NULL == $obj)
			{
				$error = seterrorinfo(__LINE__, "json_decode($json)");
				$result = -1;
			}
			else
			{
				$error = NULL;

				foreach ($obj->data as $idx => $value)
				{
					$a = (object)$value;

					if ($a->id == $airport->iata)
					{
						if (isset($DEBUG['jflights']))
						{
							echo json_encode($a, JSON_PRETTY_PRINT);
							echo "\n";
						}

						$airport->icao = $a->icao;
						$airport->name = $a->name;

							if (isset($countries["$a->land"]))
							{
								$airport->country->id = $countries["$a->land"];
								$airport->country->name = "$a->land";
							}

							break;
						}
					}
				}
			}
		}
	}

	return $error;
}

function CURL_GetAircraftType(/* in */ $curl, /* in/out */ &$aircraft)
{
	global $DEBUG;
	global $prefix;

	$error = NULL;
	$url = "http://${prefix}www.frankfurt-airport.com/de/_jcr_content.aircrafts.json";

	if (isset($DEBUG['url']))
		echo "$url\n";

	$retry = 3;

	do
	{
		/* Set script execution limit. If set to zero, no time limit is imposed. */
		set_time_limit(0);

		$error = $curl->exec($url, $json, 5);
	}
	while (!$error && !$json && --$retry);

	if ($error)
	{
		/* This is certainly a html error document... */
		if ($json)
		{
			$json = unify_html($json);
			$error = seterrorinfo(__LINE__, "$error: $url: `$json`");
		}
		else
		{
			$error = seterrorinfo(__LINE__, "$error: $url");
		}
	}
	else
	{
		if (!$json)
		{
			$error = seterrorinfo(__LINE__, "Empty response: $url");
		}
		else
		{
			$obj = json_decode($json);

			if (NULL == $obj)
			{
				$error = seterrorinfo(__LINE__, "json_decode($json)");
				$result = -1;
			}
			else
			{
				$error = NULL;

				foreach ($obj->data as $idx => $value)
				{
					$a = (object)$value;

					if ($a->id == $aircraft->type->icao)
					{
						if (isset($DEBUG['jflights']))
						{
							echo json_encode($a, JSON_PRETTY_PRINT);
							echo "\n";
						}

						$aircraft->type->name = "$a->fab $a->name";

						break;
					}
				}
			}
		}
	}

	return $error;
}

/* Map string to class FlightStatus */
function MapFlightStatus(/*in/out*/ &$status)
{
	if (!isset($status))
	{
		$status = FlightStatus::UNDEFINED;
	}
	else if (!strlen($status))
	{
		$status = FlightStatus::UNDEFINED;
	}
	else
	switch ($status)
	{
	case 'storniert':
	case 'annulliert':
	case 'cancelled':
	case '取消':
		$status = FlightStatus::CANCELLED;
		break;

	case 'umgeleitet':
	case 'diverted':
	case '航线改道':

	case 'im Anflug':
	case 'approaching':
	case '在飞行中':
		$status = FlightStatus::APPROACHING;
		break;

	case 'auf Position':
		// fallthrough
	case 'gelandet':
	case 'landed':
	case '已着陆':
		$status = FlightStatus::ARRIVED;
		break;

	case 'Gepäckausgabe':
	case 'baggage delivery':
	case '托运行李领取':
		// fallthrough
	case 'Gepäckausgabe beendet':
	case 'baggage delivery finished':
	case '托运行李领取已结束':
		$status = FlightStatus::IGNORE;
		break;

	case 'Neues Gate':
	case 'Gate offen':
	case 'Gate open':
	case '登机口开放':
		// fallthrough
	case 'Aufruf':
		// fallthrough
	case 'Boarding':
		// fallthrough
	case 'geschlossen':
	case 'closed':
	case '已关闭':
		$status = FlightStatus::BOARDING;
		break;

	case 'gestartet':
	case 'started':
	case 'departed':
	case '已起飞':
		$status = FlightStatus::DEPARTED;
		break;

	case 'Zug':
	case 'train':
	case '火车':
		$status = FlightStatus::IGNORE;
		break;

	default:

		if ('verspätet' == mb_substr($status, 0, 9))
		{
			$status = FlightStatus::APPROACHING;
		}
		else if ('Ankunft vom' == mb_substr($status, 0, 11))
		{
			$status = FlightStatus::APPROACHING;
		}
		else if ('delayed to' == mb_substr($status, 0, 10))
		{
			$status = FlightStatus::APPROACHING;
		}
		else
		{
			warn_once(__LINE__, "Status '$status' is unknown.");
			$status = FlightStatus::UNDEFINED;
		}
	}
}

// Convert JSON into vector of flight objects
function JSON_InterpretFlights(/*in*/ $dir, /*in*/ $json, /*in*/ $defer,
							   /*inout*/ &$flights, /*out*/ &$last, /*out*/ &$count)
{
	global $DEBUG;
	global $now;

	$obj = json_decode($json, false);

	if (NULL == $obj)
	{
		$error = seterrorinfo(__LINE__, "json_decode($json)");
		$result = -1;
	}
	else
	{
		$result = 0;

		if (isset($obj->version))
			if (!('1.2.5' == $obj->version))
				warn_once(__LINE__, "version = $obj->version");

		if ($obj->results > 0)
		{
			foreach ($obj->data as $idx => $value)
			{
				$result++;
				$count++;

				$jflight = (object)$value;

				if (isset($DEBUG['jflights']))
				{
					echo json_encode($jflight, JSON_PRETTY_PRINT);
					echo ",\n";
				}

				if ($jflight->fnr)
				{
					if (!isset($jflight->al))
						$jflight->al = strtok($jflight->fnr, ' ');

					if (isset($jflight->typ))
					if (strlen($jflight->typ))
					switch ($jflight->typ)
					{
					case 'C':
					case 'F':
					case 'P':
						break;

					default:
						warn_once(__LINE__, "$jflight->flstatus, $jflight->status");
					}

					/* We might remove this conditional as soon as we're sure we have all stati */
					if (isset($jflight->flstatus))
					if (strlen($jflight->flstatus))
					switch ($jflight->flstatus)
					{
					case 0:
					case 1:
					case 2:
					case 3:	/* verspätet auf ... */
						break;

					default:
						warn_once(__LINE__, "$jflight->flstatus, $jflight->status");
					}

					MapFlightStatus($jflight->status);

					if (!(FlightStatus::IGNORE == $jflight->status))
					{
						if (isset($jflight->ac))
						{
							if ('BUS' == $jflight->ac ||
								'TRN' == $jflight->ac ||
								'TRS' == $jflight->ac)
							{
								/* Ignore busses and trains... */
								$jflight->status = FlightStatus::IGNORE;
							}
						}
					}

					switch ($jflight->status)
					{
					case FlightStatus::IGNORE:
						break;

					case FlightStatus::DEPARTED:
						break;

					default:

						$sched = strtotime($jflight->sched);

						if ($last < $sched)
							$last = $sched;

						/* If a departure is due without `esti` having been set,
						   estimate departure in 5 mins */
						if ('departure' == $dir &&
							FlightStatus::BOARDING == $jflight->status)
						{
							$departure = isset($jflight->esti) ? $jflight->esti : $jflight->sched;
							$departure = strtotime($departure);

							if ($departure < $now->time_t)
							{
								$departure = $now->time_t + $defer;
								$jflight->esti = date(DATE_ISO8601, $departure);
							}
						}

						$f = new flight($jflight->typ,
										$jflight->al,
										isset($jflight->alname) ? $jflight->alname : '???',
										preg_replace('/[^ ]+ /', '', $jflight->fnr),
										$jflight->sched,
										isset($jflight->esti) ? $jflight->esti : NULL,
										isset($jflight->ac) ? $jflight->ac : NULL,
										isset($jflight->reg) ? patchreg($jflight->reg) : NULL,
										isset($jflight->iata) ? $jflight->iata : NULL,
										$jflight->status,
										$jflight->lu);

						if (isset($DEBUG['flights']))
							print_r($f);

						$flights->push($f);
					}
				}
			}
		}

		unset($obj);
	}

	return $result;
}

// Pull JSON from server page by page and convert to flight object vector
function CURL_GetFlights(/*in*/ $curl, /*in*/ $prefix,
						 /*in*/ $lookback, /*in*/ $defer,
						 /*in*/ $type, /*in*/ $dir, /*in*/ $items, /*out*/ &$flights, &$count)
{
	global $DEBUG;
	global $now;

	$flights = new vector;	// class flight

	if ($flights)
	{
		if (0)
		{
			// Retrieve flights from one hour ago (plus alignment)
			//   until no more 'esti' or 'reg' in flight data
			// Align timestamp on full hour...
			$offset = ($now->time_t - strtotime('00:00', $now->time_t)) % 3600 + $lookback;
		}
		else
		{
			$offset = 0;
		}

		$type = 'C' == $type ? '.cargo' : '';
		$start = $now->time_t - $offset;
		$time = date(DATE_ISO8601, $start);
		$time = urlencode($time);
		$current = $start;
		$page = 1;
		$error = NULL;

		while ($current < $start + 84600 && $page > 0)
		{
			// Build request URL
			$url = "http://${prefix}www.frankfurt-airport.com/de/_jcr_content.${dir}s${type}.json/filter".
				   "?type=${dir}&lang=de&time=${time}&perpage=${items}&page=${page}";

			if (isset($DEBUG['url']))
				echo "$url\n";

			// Fetch JSON data
			$retry = 10;

			do
			{
				/* Set script execution limit. If set to zero, no time limit is imposed. */
				set_time_limit(0);

				$error = $curl->exec($url, $json, 5);

				if ($error)
				{
					if ($error != CURLE_COULDNT_RESOLVE_HOST &&
						$error != CURLE_OPERATION_TIMEDOUT)
					{
						$log = fopen("$_SERVER[DOCUMENT_ROOT]/var/log/curl.error", "a+");
						fwrite($log, "$url: $error @$retry");
						fclose($log);
					}
				}
			}
			while ($error && --$retry);

		if ($error)
		{
			/* This is certainly a html error document... */
			if ($json)
			{
				$json = unify_html($json);
				$error = seterrorinfo(__LINE__, sprintf("[%s] %s: `%s`", $error, $url, $json));
			}
			else
			{
				$error = seterrorinfo(__LINE__, sprintf("[%s] %s", $error, $url));
			}

			$page = 0;
		}
		else
		{
			if (!$json)
			{
				$error = seterrorinfo(__LINE__, "Empty response: $url");
				$page = 0;
			}
			else
			{
					if (isset($DEBUG['json']))
						echo "$json\n";

					// Interpret JSON into `$flights` vector
					if (JSON_InterpretFlights($dir, $json, $defer, $flights, $current, $count) <= 0)
						$page = 0;
					else
						$page++;
				}
			}
		}
	}

	return $error;
}

function SQL_GetAirline(/* in/out */ &$airline)
{
	global $DEBUG;

	// Is airline already in database?
	$query = <<<SQL
		SELECT `id`
		FROM `airlines`
		WHERE `code`='$airline->code';
SQL;

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), unify_query($query)));
	}
	else
	{
		$error = NULL;

		if (isset($DEBUG['sql']))
			echo unify_query($query);

		if (0 == mysql_num_rows($result))
		{
			if (isset($DEBUG['sql']))
			{
				if ('html' == $DEBUG['fmt'])
					echo htmlspecialchars("=<empty>\n");
				else
					echo "=<empty>\n";
			}
		}
		else
		{
			// Yes
			$row = mysql_fetch_row($result);

			if ($row)
				$airline->id = $row[0];

			if (isset($DEBUG['sql']))
				echo "=$airline->id\n";
		}

		mysql_free_result($result);
	}

	return $error;
}

function SQL_InsertAirline(/* in/out */ &$airline)
{
	global $DEBUG;

	if (0 == strlen($airline->code))
	{
		$error = seterrorinfo(__LINE__, 'strlen(airline)');
	}
	else
	{
		if (0 == strlen($airline->name))
			$airline->name = $airline->code;
		else
			$airline->name = addslashes($airline->name);

		$query = <<<SQL
			INSERT INTO `airlines`(`code`, `name`)
			VALUES('$airline->code', '$airline->name');
SQL;

		if (!mysql_query($query))
		{
			$airline->id = NULL;
			$error = seterrorinfo(__LINE__,
						sprintf("[%d] %s: %s",
							mysql_errno(), mysql_error(), unify_query($query)));
		}
		else
		{
			$error = NULL;
			$airline->id = mysql_insert_id();

			if (isset($DEBUG['sql']))
			{
				echo unify_query($query);
				echo "=$airline->id\n";
			}
		}
	}

	return $error;
}

function SQL_GetAirport(/* in/out */ &$airport)
{
	global $DEBUG;

	$query = <<<SQL
		SELECT DISTINCT `airports`.`id`
		FROM `airports`
		WHERE `iata`='$airport->iata';
SQL;

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), unify_query($query)));
	}
	else
	{
		$error = NULL;

		if (isset($DEBUG['sql']))
			echo unify_query($query);

		if (0 == mysql_num_rows($result))
		{
			if (isset($DEBUG['sql']))
			{
				if ('html' == $DEBUG['fmt'])
					echo htmlspecialchars("=<empty>\n");
				else
					echo "=<empty>\n";
			}
		}
		else
		{
// KLUGE: There are multiple airports with same IATA code, as of now, we cannot
// distinguish which one is really meant, since json only contains iata code
// For our tests to run, we simply fetch the last id to make the script select
// the proper icao code for JNB...
			while ($row = mysql_fetch_row($result))
			{
				if ($row)
					$airport->id = $row[0];

				if (isset($DEBUG['sql']))
					echo "=$airport->id\n";
			}
		}

		mysql_free_result($result);
	}

	return $error;
}

function SQL_InsertAirport(/* in/out */ &$airport)
{
	global $DEBUG;

	if (0 == strlen($airport->iata) ||
		0 == strlen($airport->icao))
	{
		$error = seterrorinfo(__LINE__, 'strlen(airport)');
	}
	else
	{
		$airport->name = addslashes($airport->name);

		$query = <<<SQL
			INSERT INTO `airports`(`iata`, `icao`, `name`, `country`)
			VALUES(
				'$airport->iata',
				'$airport->icao',
				'$airport->name',
				{$airport->country->id});
SQL;

		if (!mysql_query($query))
		{
			$error = seterrorinfo(__LINE__,
						sprintf("[%d] %s: %s",
							mysql_errno(), mysql_error(), unify_query($query)));
		}
		else
		{
			$error = NULL;
			$airport->id = mysql_insert_id();

			if (isset($DEBUG['sql']))
			{
				echo unify_query($query);
				echo "=OK\n";
			}
		}
	}

	return $error;
}

function SQL_GetAircraftType(/* in/out*/ &$aircraft)
{
	global $DEBUG;

	$query = <<<SQL
		SELECT `id`
		FROM `models`
		WHERE `icao`='{$aircraft->type->icao}';
SQL;

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), unify_query($query)));
	}
	else
	{
		$error = NULL;

		if (isset($DEBUG['sql']))
			echo unify_query($query);

		if (0 == mysql_num_rows($result))
		{
			if (isset($DEBUG['sql']))
			{
				if ('html' == $DEBUG['fmt'])
					echo htmlspecialchars("=<empty>\n");
				else
					echo "=<empty>\n";
			}
		}
		else
		{
			$row = mysql_fetch_row($result);

			if ($row)
				$aircraft->type->id = $row[0];

			if (isset($DEBUG['sql']))
				echo "={$aircraft->type->id}\n";
		}

		mysql_free_result($result);
	}

	return $error;
}

function SQL_InsertAircraftType(/* in/out */ &$aircraft)
{
	global $DEBUG;

	if (0 == strlen($aircraft->type->icao))
	{
		$error = seterrorinfo(__LINE__, 'strlen(aircraft->type->icao)');
	}
	else
	{
		$aircraft->type->name = addslashes($aircraft->type->name);

		$query = <<<SQL
			INSERT INTO `models`(`icao`,`name`)
			VALUES('{$aircraft->type->icao}', '{$aircraft->type->name}');
SQL;

		if (!mysql_query($query))
		{
			$error = seterrorinfo(__LINE__,
						sprintf("[%d] %s: %s",
							mysql_errno(), mysql_error(), unify_query($query)));
		}
		else
		{
			$error = NULL;
			$aircraft->type->id = mysql_insert_id();

			if (isset($DEBUG['sql']))
			{
				echo unify_query($query);
				echo "={$aircraft->type->id}\n";
			}
		}
	}
}

function SQL_GetAircraft(/* in/out*/ &$aircraft)
{
	global $DEBUG;

	$query = <<<SQL
		SELECT `id`
		FROM `aircrafts`
		WHERE `reg`='$aircraft->reg';
SQL;

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), unify_query($query)));
	}
	else
	{
		$error = NULL;

		if (isset($DEBUG['sql']))
			echo unify_query($query);

		if (0 == mysql_num_rows($result))
		{
			if (isset($DEBUG['sql']))
			{
				if ('html' == $DEBUG['fmt'])
					echo htmlspecialchars("=<empty>\n");
				else
					echo "=<empty>\n";
			}
		}
		else
		{
			$row = mysql_fetch_row($result);

			if ($row)
				$aircraft->id = $row[0];

			if (isset($DEBUG['sql']))
				echo "=$aircraft->id\n";
		}

		mysql_free_result($result);
	}

	return $error;
}

function SQL_InsertAircraft(/* in/out*/ &$aircraft)
{
	global $DEBUG;

	if (0 == strlen($aircraft->reg))
	{
		$error = seterrorinfo(__LINE__, 'strlen(aircraft->reg)');
	}
	else if (0 == $aircraft->type->id)
	{
		$error = seterrorinfo(__LINE__, 'aircraft(aircraft->type->id)');
	}
	else
	{
		$query = <<<SQL
			INSERT INTO `aircrafts`(`reg`,`model`)
			VALUES('$aircraft->reg', {$aircraft->type->id});
SQL;

		if (!mysql_query($query))
		{
			$error = seterrorinfo(__LINE__,
						sprintf("[%d] %s: %s",
							mysql_errno(), mysql_error(), unify_query($query)));
		}
		else
		{
			$error = NULL;
			$aircraft->id = mysql_insert_id();

			if (isset($DEBUG['sql']))
			{
				echo unify_query($query);
				echo "=$aircraft->id\n";
			}
		}
	}

	return $error;
}

function SQL_GetFlightDetails(/* in */ $dir, /* in */ $f, /* out */ &$id, /* out */ &$ac, /* out */ &$lu)
{
	global $DEBUG;

	$query = <<<SQL
		SELECT `id`, `aircraft`, `last update`
		FROM `flights`
		WHERE `direction`='$dir'
		 AND `airline`={$f->airline->id}
		 AND `code`='{$f->fnr}'
		 AND `scheduled`='{$f->scheduled}'
SQL;

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), unify_query($query)));
	}
	else
	{
		$error = NULL;

		if (isset($DEBUG['sql']))
			echo unify_query($query);

		$row = mysql_fetch_row($result);

		if (FALSE == $row)
		{
			if (isset($DEBUG['sql']))
			{
				if ('html' == $DEBUG['fmt'])
					echo htmlspecialchars("=<empty>\n");
				else
					echo "=<empty>\n";
			}
		}
		else
		{
			$id = $row[0];
			$ac = $row[1];
			$lu = $row[2];

			if (isset($DEBUG['sql']))
				echo "=$id,$ac,$lu\n";
		}

		mysql_free_result($result);
	}

	return $error;
}

function SQL_UpdateFlightDetails(/* in */ $id, /* in */ $f)
{
	global $DEBUG;

	// Don't overwrite `expected`/`airport` with NULL!

	if (NULL == $f->expected)
	{
		$expected = "";
	}
	else
	{
		if (strtotime($f->expected) < strtotime('-3 days'))
			$expected = "";
		else
			$expected = "`expected`='{$f->expected}', ";
	}

	$airport = $f->airport->id ? "`airport`={$f->airport->id}," : "";
	$aircraft = $f->aircraft->type->id ? $f->aircraft->type->id : "NULL";
	$reg = $f->aircraft->id ? $f->aircraft->id : "NULL";

	$query = <<<SQL
		UPDATE `flights`
		SET $expected
		 $airport
		 `aircraft`=$reg,
		 `model`=$aircraft
		WHERE `id`=$id;
SQL;

	if (!mysql_query($query))
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), unify_query($query)));
	}
	else
	{
		$error = NULL;

		if (isset($DEBUG['sql']))
		{
			echo unify_query($query);
			echo "=OK\n";
		}
	}

	return $error;
}

function SQL_InsertFlight(/*in*/ $type, /* in */ $dir, /* in/out */ &$f)
{
	global $DEBUG;

	if (NULL == $f->expected)
	{
		$expected = "NULL";
	}
	else
	{
		if (strtotime($f->expected) < strtotime('-3 days'))
			$expected = "NULL";
		else
			$expected = "'{$f->expected}'";
	}

	$aircraft = $f->aircraft->type->id ? $f->aircraft->type->id : "NULL";
	$reg = $f->aircraft->id ? $f->aircraft->id : "NULL";
	$lu = $f->lu ? "'$f->lu'" : "NULL";

	$query = <<<SQL
		INSERT INTO `flights`
		(`direction`, `type`, `airline`, `code`,
		 `scheduled`, `expected`, `airport`, `model`, `aircraft`, `last update`)
		VALUES(
		 '$dir', '$type',
		 {$f->airline->id}, '{$f->fnr}',
		 '{$f->scheduled}', $expected,
		 {$f->airport->id},
		 $aircraft, $reg, $lu);
SQL;

	if (!mysql_query($query))
	{
		$error = seterrorinfo(__LINE__,
					sprintf("[%d] %s: %s",
						mysql_errno(), mysql_error(), unify_query($query)));
	}
	else
	{
		$error = NULL;

		// Don't bother about id here...
		if (isset($DEBUG['sql']))
		{
			echo unify_query($query);
			echo "=OK\n";
		}
	}

	return $error;
}

function SQL_DeleteFlight($id)
{
	global $DEBUG;

	if (!$id)
	{
		$error = seterrorinfo(__LINE__, 'EINVAL');
	}
	else
	{
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
							mysql_errno(), mysql_error(), unify_query($query)));
		}
		else
		{
			$error = NULL;

			if (isset($DEBUG['sql']))
			{
				echo unify_query($query);
				echo "=OK\n";
			}

			if (0 == mysql_affected_rows())
				warn_once(__LINE__, "No flight deleted: $id");
		}
	}

	return $error;
}

function SQL_UpdateVisitsToFra($scheduled, $reg, $op)
{
	global $DEBUG;

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
						mysql_errno(), mysql_error(), unify_query($query)));
	}
	else
	{
		$error = NULL;

		if (isset($DEBUG['sql']))
			echo unify_query($query);

		$row = mysql_fetch_assoc($result);

		mysql_free_result($result);

		if (!$row)
		{
			if (isset($DEBUG['sql']))
			{
				if ('html' == $DEBUG['fmt'])
					echo htmlspecialchars("=<empty>\n");
				else
					echo "=<empty>\n";
			}

			if (-1 == $op)	// "annulliert"
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

			if (isset($DEBUG['sql']))
				echo "=$num,'$current',".($previous ? $previous : "NULL")."\n";

			if (1 == $op)
			{
				if (strtotime($scheduled) <= strtotime($current))
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
											mysql_errno(), mysql_error(), unify_query($query)));
						}
						else
						{
							if (isset($DEBUG['sql']))
								echo unify_query($query);

							$row = mysql_fetch_assoc($result);

							mysql_free_result($result);

							if ($row)
							{
								$previous = $row['scheduled'];

								if (isset($DEBUG['sql']))
									echo "='$previous'\n";
							}
							else
							{
								$previous = NULL;

								if (isset($DEBUG['sql']))
								{
									if ('html' == $DEBUG['fmt'])
										echo htmlspecialchars("=<empty>\n");
									else
										echo "=<empty>\n";
								}
							}
						}
					}

					if ($previous)
					{
						$num--;
						$query = <<<SQL
							UPDATE `visits`
							SET `num`=$num, `current`='$previous', `previous`=NULL
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
				if (isset($DEBUG['sql']))
				{
					echo unify_query($query);
					echo "=OK\n";
				}
			}
		}
	}

	return $error;
}

/* Delete all notifications for cancelled flights or
   those not having been sent, if aircraft changes */
function SQL_DeleteNotifications($id, $all)
{
	global $DEBUG;

	if (!$id)
	{
		$error = seterrorinfo(__LINE__, 'EINVAL');
	}
	else
	{
		if ($all)
			$cond = '';
		else
			$cond = ' AND `notified` IS NULL';

		$query = <<<SQL
			DELETE
			FROM `watchlist-notifications`
			WHERE `flight`={$id}{$cond}
SQL;

		$result = mysql_query($query);

		if (!$result)
		{
			$error = seterrorinfo(__LINE__,
						sprintf("[%d] %s: %s",
							mysql_errno(), mysql_error(), unify_query($query)));
		}
		else
		{
			$error = NULL;

			if (isset($DEBUG['sql']))
			{
				echo unify_query($query);
				echo "=OK\n";
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

function SendWatchlistNotification($name, $email, $fmt, $locale, $notifications)
{
	global $DEBUG;
	global $now;

	if ('de' == $locale)
	{
		$locale = setlocale(LC_TIME, 'deu', 'deu_deu');
		$lang = array(
				'watchlist' => 'Beobachtungsliste',
				'schedule' => 'Flugplan',
			);
	}
	else
	{
		$locale = setlocale(LC_TIME, 'eng', 'english-uk', 'uk', 'enu', 'english-us', 'us', 'english', 'C');
		$lang = array(
				'watchlist' => 'watchlist',
				'schedule' => 'schedule',
			);
	}

	$today = mktime_c(gmstrftime('%d.%m.%Y', $now->time_t));

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

		/* On Windows, strftime() will encode as 'de' as 'German_Germany.1252'
		   and setlocale(..., 'de_DE.UTF-8') doesn't work... */
		if (strstr($locale, '1252'))
			$expected = utf8_encode($expected);

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
	$subject = mb_encode_mimeheader("$lang[watchlist]", 'ISO-8859-1', 'Q');
	$header = sprintf(
		"From: %s <%s>\n".
		"Reply-To: %s\n".
		"Mime-Version: 1.0\n".
		"Content-type: text/plain; charset=ISO-8859-1\n".
		"Content-Transfer-Encoding: 8bit\n".
		"X-Mailer: PHP/%s\n",
		"FRA $lang[schedule]",
		"watchlist@fra-flugplan.de",
		ADMIN_EMAIL,
		phpversion());

	$text = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');

	if (!mail($to, $subject, $text, $header))
	{
		$error = seterrorinfo(0, NULL);
	}
	else
	{
		$query = <<<SQL
			UPDATE `watchlist-notifications`
			LEFT JOIN `watchlist`
				   ON `watchlist`.`id`=`watchlist-notifications`.`watch`
			INNER JOIN `users`
					ON `users`.`id`=`watchlist`.`user`
						AND `users`.`email`='$email'
			SET `watchlist-notifications`.`notified`='$now->atom'
			WHERE `watchlist-notifications`.`id` IN(%s)
SQL;

		$query = sprintf($query, $update);

		if (isset($DEBUG['sql']))
			echo unify_query($query);

		if (mysql_query($query))
			$error = NULL;
		else
			$error = seterrorinfo(__LINE__, sprintf("[%d] %s", mysql_errno(), mysql_error()));
	}
}

function mysql_connect_db(&$hdbc)
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
					$error = seterrorinfo(__LINE__, sprintf("[%d] %s", mysql_errno(), mysql_error()));
				else
					$error = NULL;

				mysql_free_result($result);
			}
		}
	}

	return $error;
}

// main()

$error = NULL;

try
{
	$curl = new curl;
}
catch (Exception $e)
{
	$error = $e->getMessage();
}

if (!$error)
{
	if (!$error)
		$error = mysql_connect_db($hdbc);

	if (!$error)
	{
		// Iterate through [pax,cargo] [arrival, departure] tables
		foreach (array('P', 'C') as $type)
		{
			foreach (array('arrival', 'departure') as $dir)
			{
				if (isset($DEBUG['any']))
					printf("%s\n========\n\n", $dir);

				$count = 0;
				$time_start = microtime(true);

				$error = CURL_GetFlights($curl, $prefix, $lookback, $defer, $type, $dir, $items, $flights, $count);

				$time_end = microtime(true);
				$time = $time_end - $time_start;

				$n = $flights->count();

				while ($f = $flights->pop())
				{
					if (isset($DEBUG['sql']))
						echo "\n/************************************/\n\n";

					$error = SQL_GetAirline($f->airline);

					if (!$error)
					{
						if (!$f->airline->id)
						{
							if (!$f->airline->name)
								$error = CURL_GetAirline($curl, $f->airline);

							if (!$error)
							{
								$error = SQL_InsertAirline($f->airline);

								if (!$error)
								{
									info(__LINE__,
										 "Inserted airline {$f->airline->code} as \"{$f->airline->name}\"".
										 " for {$f->aircraft->reg} ".
										 " ($dir {$f->airline->code}{$f->fnr} \"{$f->scheduled}\").");
								}
							}
						}
					}

					if (!$error)
					{
						if (!$f->airport->iata)
							$f->airport->iata = '???';

						$error = SQL_GetAirport($f->airport);

						if (!$error)
						{
							if (!$f->airport->id)
							{
								$error = CURL_GetAirport($curl, $f->airport);

								if (!$error)
								{
									if (!$f->airport->icao)
										$f->airport->icao = "#{$f->airport->iata}";

									$error = SQL_InsertAirport($f->airport);

									if (!$error)
									{
										if ($f->airport->country->id > 0)
										{
											info(__LINE__,
												 "Inserted airport {$f->airport->iata} as ".
												 "{$f->airport->icao} \"{$f->airport->name}\"".
												 " ($dir {$f->airline->code}{$f->fnr} \"{$f->scheduled}\")".
												 " located in '{$f->airport->country->name}'.");
										}
										else
										{
											warn(__LINE__,
												 "Inserted airport {$f->airport->iata} as ".
												 "{$f->airport->icao} \"{$f->airport->name}\"".
												 " ($dir {$f->airline->code}{$f->fnr} \"{$f->scheduled}\")".
												 ", however, I was unable to determine the country it is located in.");
										}
									}
								}
							}
						}
					}

					if (!$error)
					{
						if ($f->aircraft->type->icao)
						{
							$error = SQL_GetAircraftType($f->aircraft);

							if (!$error)
							{
								if (!$f->aircraft->type->id)
								{
									$error = CURL_GetAircraftType($curl, $f->aircraft);

									if (!$error)
									{
										$error = SQL_InsertAircraftType($f->aircraft);

										if (!$error)
										{
											info(__LINE__,
												 "Inserted aircraft {$f->aircraft->type->icao} as".
												 " \"{$f->aircraft->type->name}\"".
												 " ($dir {$f->airline->code}{$f->fnr} \"{$f->scheduled}\").");
										}
									}
								}
							}
						}
					}

					if (!$error)
					{
						if ($f->aircraft->reg)
						{
							$error = SQL_GetAircraft($f->aircraft);

							if (!$error)
							{
								if (!$f->aircraft->id)
									$error = SQL_InsertAircraft($f->aircraft);
							}
						}
					}

					if (!$error)
					{
						if (!(FlightStatus::IGNORE == $f->status))
						{
							/* We need flight's `id` and `aircraft` for
							   - deletion of cancelled flights
							   - update of flights
							   - update of #visits
							     - in case of cancelled flights
							     - in case of equipment change

							   $f comes in with id=0
							 */
							$ac = 0;
							$lu = 0;

							$error = SQL_GetFlightDetails($dir, $f, $f->id, $ac, $lu);
						}
					}

					if (!$error)
					{
						if (strtotime($f->lu) <= strtotime($lu))
							$f->status = FlightStatus::IGNORE;

						if (!(FlightStatus::IGNORE == $f->status))
						{
							$visits = 0;

							if (FlightStatus::IGNORE == $f->status)
							{
								if (isset($DEBUG['sql']))
									echo "/* ignored */\n";
							}
							else if (FlightStatus::CANCELLED == $f->status)
							{
								if ($f->id)
								{
									$visits = -1;

									/* Delete from `watchlist-notifications` first, which uses
									   `flights`.`is` a foreign key... */
									SQL_DeleteNotifications($f->id, true);

									$error = SQL_DeleteFlight($f->id);
								}
							}
							else
							{
								if (0 == $f->id)
								{
									$visits = 1;
									$error = SQL_InsertFlight($type, $dir, $f);
								}
								else
								{
									$error = SQL_UpdateFlightDetails($f->id, $f);

									if (!$error)
									{
										if (NULL == $ac)
										{
											if ('arrival' == $dir)
												$visits = 1;
										}
										else
										{
											if ($f->aircraft->id != $ac)
											{
												if ('arrival' == $dir)
												{
													SQL_UpdateVisitsToFra($f->scheduled, $ac, -1);

													$visits = 1;	/* for $f->aircraft */
												}

												SQL_DeleteNotifications($f->id, false);
											}
										}
									}
								}
							}

							if (!$error)
								if ('arrival' == $dir && $f->aircraft->id && $visits)
									$error = SQL_UpdateVisitsToFra($f->scheduled, $f->aircraft->id, $visits);
						}
					}
				}

				if (isset($DEBUG['sql']))
					echo "\n/************************************/\n\n";

				if (isset($DEBUG['any']))
				{
					printf("---------------------------\n");
					printf("%lu (%lu) Flüge gefunden.\n", $n, $count);
					printf("    %s: %.3fs\n", 'Dauer', $time);
					printf("\n===========================\n\n\n");
				}

				unset($flights);
			}
		}

		/* Add watches to `watchlist-notifications` table */
		$query = <<<SQL
			INSERT INTO `watchlist-notifications`(`flight`, `watch`)

			SELECT `flights`.`id`, `watchlist`.`id`
			FROM `watchlist`
			INNER JOIN `aircrafts`
				ON `aircrafts`.`reg` LIKE REPLACE(REPLACE(`watchlist`.`reg`, '*', '%'), '?', '_')
				OR `aircrafts`.`reg` RLIKE REPLACE(`watchlist`.`reg`, '/', '') AND `watchlist`.`reg` LIKE '/_%/'
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
				AND `expected` > '$now->atom'
			FOR UPDATE
SQL;

		if (!mysql_query($query))
		{
			$error = seterrorinfo(__LINE__,
						sprintf("[%d] %s: %s",
							mysql_errno(), mysql_error(), unify_query($query)));
		}
		else
		{
			/* Check whether or which notifications are to be sent */
			if (isset($DEBUG['sql']))
				echo unify_query($query);

			$query = <<<SQL
				SELECT
					`watchlist-notifications`.`id` AS `id`,
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
				WHERE IFNULL(`flights`.`expected`, `flights`.`scheduled`) > '$now->atom'
				AND `notified` IS NULL
				AND
				 FROM_UNIXTIME($now->time_t, '%H:%i:%s')
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
								mysql_errno(), mysql_error(), unify_query($query)));
			}
			else
			{
				if (isset($DEBUG['sql']))
					echo unify_query($query);

				$text = NULL;
				$name = NULL;
				$email = NULL;
				$watch = NULL;
				$fmt = NULL;
				$lang = 'en';

				$notifications = array();

				$row = mysql_fetch_assoc($result);

				while ($row)
				{
					if ($email != $row['email'])
					{
						/* We get here every time $row['email'] changes, at least */
						/* once at the beginning, i.e. $time and $fmt will be set, */
						/* if one row has been found  */
						if ($email != NULL)
						{
							/* Flush */
							SendWatchlistNotification($name, $email, $fmt, $lang, $notifications);
							$notifications = array();
						}

						/* Remember first ID of new email */
						$email = $row['email'];
						$name = $row['name'];
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
					SendWatchlistNotification($name, $email, $fmt, $lang, $notifications);

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
			WHERE (DATEDIFF('$now->atom', IFNULL(`flights`.`expected`, `flights`.`scheduled`)) > 1)
SQL;

		if (isset($DEBUG['sql']))
			echo unify_query($query);

		if (!mysql_query($query))
			$error = seterrorinfo(__LINE__, sprintf("[%d] %s", mysql_errno(), mysql_error()));

		/* Move outdated flights to history table */
		if (!$error)
			$error = SQL_FlightsToHistory();

		mysql_close($hdbc);
	}

	/* betriebsrichtung.html */
	$url = "http://${prefix}apps.fraport.de/betriebsrichtung/betriebsrichtung.html";

	do
	{
		/* Set script execution limit. If set to zero, no time limit is imposed. */
		set_time_limit(0);

		$error = $curl->exec($url, $betriebsrichtung, 5);
	}
	while (!$error && !$betriebsrichtung && --$retry);

	if ($error)
	{
		/* This is certainly a html error document... */
		$betriebsrichtung = unify_html($betriebsrichtung);
		$error = seterrorinfo(__LINE__, "$error: $url: `$betriebsrichtung`");
	}
	else
	{
		$file = @fopen("$datadir/betriebsrichtung.html", "w");

		if ($file)
		{
			fwrite($file, $betriebsrichtung);
			fclose($file);
		}
	}

	unset($curl);
}

if ($errorinfo)
{
	if (isset($DEBUG['any']))
		echo $errorinfo;

	mail(mb_encode_mimeheader(ADMIN_NAME, 'ISO-8859-1', 'Q').'<'.ADMIN_EMAIL.'>',
		 "fra-schedule - getflights.php: error",
		 "$errorinfo",
		 "From: fra-schedule");
}

if ($warning)
{
	if (isset($DEBUG['any']))
		echo $warning;

	mail(mb_encode_mimeheader(ADMIN_NAME, 'ISO-8859-1', 'Q').'<'.ADMIN_EMAIL.'>',
		 "fra-schedule - getflights.php: warning",
		 "$warning",
		 "From: fra-schedule");
}

if ($info)
{
	if (isset($DEBUG['any']))
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
