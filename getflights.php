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
 *   Test options: prefix=localhost/fra-flugplan/fra-flugplan-trunk/
 *                 time={localtime as '%Y-%m-%dT%H:%M:%S%z'}
 *
 ******************************************************************************/

$script_start = microtime(true);

mb_internal_encoding('UTF-8');

ini_set('max_execution_time', 180);

require_once ".config";
require_once "classes/etc.php";
require_once "classes/curl.php";
require_once "classes/vector.php";
require_once "classes/sql-xpdo.php";
require_once "classes/sql-xpose.php";

/* Create dir for warn_once() */
$datadir = "$_SERVER[DOCUMENT_ROOT]/var/run/fra-flugplan";
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
	$ignore = [];
}
else
{
	$ignore = file($ignorelist);

	foreach($ignore as &$entry)
		$entry = trim($entry);

	unset($entry);
}

/* Initialise variables */

$errorinfo = null;
$warning = null;
$info = null;

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

$now->atom = date("Y-m-d H:i:s", $now->time_t);

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

function sqlErrorInfo(PDOException $ex, $info = null) : string
{
	$query = null;

	if (isset($info))
		if (gettype($info) == "object")
			if (strstr(get_class($info), "PDOStatement"))
				$query = $info->queryString;

	$error = "*** {$ex->getMessage()}\n{$ex->getTraceAsString()}\n";

	if ($query !== null)
		$error .= <<<SQL
			$query
			SQL;

	echo "$error";

	return $error;
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
			$html = str_replace(
				["\r", "\n", "<br>"],
				[" ", " ", "&lt;br&gt;"],
				htmlspecialchars($html)
			);

			$html = preg_replace('/>[ \t]+</', '><', $html);
		}
	}

	return $html;
}

// Insert hyphen into reg, based on regex
function patchreg($reg)
{
	$regdb =
	[
		// https://www.revolvy.com/main/index.php?s=List%20of%20aircraft%20registration%20prefixes
		// http://arg-intl.com/resources/icao-aircraft-registration-prefixes/
		// https://www.skytamer.com/5.2.htm
		// https://en.wikipedia.org/wiki/List_of_aircraft_registration_prefixes
		// https://www.airlineupdate.com/content_public/codes/misc_codes/nat_reg.htm
		// http://www.airlinecodes.co.uk/regprefixcur.asp
		// https://aircraft-registration-country-codes.blogspot.de/
		// https://www.aviatorjoe.net/go/icao-registration-prefixes/
		'/^2[A-Z]+/', 1,
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
		'/^E[35CIJKLPRSTWXYZ]/', 2,
		'/^F[A-Z]{4}/', 1,
		'/^FAE[0-9]+/', 3,
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
		'/^U[HKLNPR]/', 2,
		'/^V[234578HNPQRT]/', 2,
		'/^X[ABCHTUVWYZ]/', 2,
		'/^Y[AEIJKLNRSUV]/', 2,
		'/^Z[^3AKLPSTU]/', 1,
		'/^Z[3AKLPSTU]/', 2,
	];

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
	public $lu;			// = "2015-12-07T05:31:58+0100"
	// flight
	public $typ;		// = enum { "P", "F" }
	public $fnr;		// = "LH 1013"
	public $schedDep;	// = "2015-12-07T15:10:00+0100"
	public $sched;		// = "2015-12-07T16:10:00+0100"
	public $esti;		// = [null] "2015-12-05T16:21:00+0100"
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
	public $ac;			// = [null?] "TRN", "TRS"
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
		{ 'auf Position', 'on position', '至机位', },
		{ 'Gep\u00e4ckausgabe', 'baggage delivery', '托运行李领取' },
		{ 'Gep\u00e4ckausgabe beendet', 'baggage delivery finished', '托运行李领取已结束' },
		{ 'Neues Gate', 'Neues Gate', 'Neues Gate' },
		{ 'Gate offen', 'Gate open', '登机口开放' },
		{ 'Aufruf', 'ready for Boarding', '准备登机' },
		{ 'Boarding', 'Boarding', '登机' },
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
	public $cs;			// = [null] ["", ""]

	// unknown
	public $s;			// = [null] enum { false, true }
	public $flstatus;	// = [null] enum { 0 /* default */ , 1, 2, 3 /* verspätet auf ... */ }
	public $duration;	// = [null] 60 // min

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

abstract class VisitsToFra
{
	const DEC = -1;
	const NOP =  0;
	const INC =  1;
}

class airline
{
	public $id;
	public $code;
	public $name;

	public function __construct($al, $alname)
	{
		$this->id = null;
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

	public function __construct($iata = null)
	{
		$this->id = null;
		$this->iata = $iata;
		$this->icao = null;
		$this->name = null;
		$this->country = (object) [ 'id' => 0, 'name' => '' ];
	}
}

class aircrafttype
{
	public $id;
	public $icao;
	public $name;

	public function __construct($icao = null)
	{
		$this->id = null;
		$this->icao = $icao;
		$this->name = null;
	}
}

class aircraft
{
	public $id;
	public $reg;
	public $type;

	public function __construct($reg, $type)
	{
		$this->id = null;
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
	public $estimated;	// estimated = [null] "2015-12-05T16;21;00+0100"
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
		$this->estimated = $esti;
		$this->airport = new airport($iata);
		$this->aircraft = new aircraft($reg, $ac);
		$this->status = $status;
		$this->lu = $lu;
	}
}

// Get JSON error messages
$constants = get_defined_constants(true);
$json_errors = [];

foreach ($constants["json"] as $name => $value)
{
	if (0 == strncmp($name, "JSON_ERROR_", 11))
		$json_errors[$value] = $name;
}

function CURL_GetAirline(/* in */ $curl, /* in/out */ &$airline)
{
	global $DEBUG;
	global $prefix;

	$error = null;
	$url = "https://${prefix}www.frankfurt-airport.com/de/_jcr_content.airlines.json";

	if (isset($DEBUG['url']))
		echo "$url\n";

	$retry = 3;

	do
	{
		/* Set script execution limit. If set to zero, no time limit is imposed. */
		set_time_limit(0);

		$error = $curl->exec($url, $json, 5);
// check: curl ETIMEDOUT ??
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

			if (null == $obj)
			{
				$error = seterrorinfo(__LINE__, "json_decode($json)");
				$result = -1;
			}
			else
			{
				$error = null;

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
	global $db;

	try
	{
		$error = null;
		$countries = [];

		$st = $db->query(<<<SQL
			/*[Q21]*/
			SELECT
				`id`,
				`de`,
				`alpha-2`
			FROM
				`countries`
			SQL
		);

		while ($row = $st->fetchObject())
			$countries["$row->de"] = (int)$row->id;

		foreach ([
			'USA' => 'Vereinigte Staaten von Amerika',
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
			] as $alias => $name)
		{
			if (isset($countries["$name"]))
				$countries["$alias"] = $countries["$name"];
		}

		$error = null;
		$url = "https://${prefix}www.frankfurt-airport.com/de/_jcr_content.airports.json";

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

				if (null == $obj)
				{
					$error = seterrorinfo(__LINE__, "json_decode($json)");
					$result = -1;
				}
				else
				{
					$error = null;

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
	catch (PDOException $ex)
	{
		$error = sqlErrorInfo($ex, $st);
	}

	return $error;
}

function CURL_GetAircraftType(/* in */ $curl, /* in/out */ &$aircraft)
{
	global $DEBUG;
	global $prefix;

	$error = null;
	$url = "https://${prefix}www.frankfurt-airport.com/de/_jcr_content.aircrafts.json";

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

			if (null == $obj)
			{
				$error = seterrorinfo(__LINE__, "json_decode($json)");
				$result = -1;
			}
			else
			{
				$error = null;

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
	case 'on position':
	case '至机位':
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
	case 'new Gate':
	case 'Gate offen':
	case 'Gate open':
	case '登机口开放':
		// fallthrough
	case 'Aufruf':
	case 'ready for Boarding':
	case '准备登机':
		// fallthrough
	case 'Boarding':
	case '登机':
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
		else if ('arrival from' == mb_substr($status, 0, 12))
		{
			$status = FlightStatus::APPROACHING;
		}
		else if ('delayed to' == mb_substr($status, 0, 10))
		{
			$status = FlightStatus::APPROACHING;
		}
		else if ('delayed from' == mb_substr($status, 0, 12))
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

	if (null == $obj)
	{
		$error = seterrorinfo(__LINE__, "json_decode($json)");
		$result = -1;
	}
	else
	{
		$result = 0;

		if (isset($obj->version))
			if (!('2.4' == $obj->version))
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
								$jflight->esti = date("Y-m-d H:i:s", $departure);
							}
						}

						$f = new flight($jflight->typ,
										$jflight->al,
										isset($jflight->alname) ? $jflight->alname : '???',
										preg_replace('/[^ ]+ /', '', $jflight->fnr),
										$jflight->sched,
										isset($jflight->esti) ? $jflight->esti : null,
										isset($jflight->ac) ? $jflight->ac : null,
										isset($jflight->reg) ? patchreg($jflight->reg) : null,
										isset($jflight->iata) ? $jflight->iata : null,
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
		$type = 'C' == $type ? '.cargo' : '';
		$start = $now->time_t;
		$time = date("Y-m-d\TH:i:sO", $start);
		$time = urlencode($time);
		$current = $start;
		$page = 1;
		$error = null;

		while ($current < $start + 84600 && $page > 0)
		{
			// Build request URL
			$url = "https://${prefix}www.frankfurt-airport.com/de/_jcr_content.flights${type}.json/filter".
				   "?flighttype=${dir}s&lang=de&time=${time}&perpage=${items}&page=${page}";

			if (isset($DEBUG['url']))
				echo "$url\n";

			// Fetch JSON data
			$retry = 10;

			while ($retry--)
			{
				/* Set script execution limit. If set to zero, no time limit is imposed. */
				set_time_limit(0);

				$error = $curl->exec($url, $json, 5);

				if (!$error)
				{
					$retry = 0;
				}
				else
				{
					// Error listed explicitly here _might_ be recoverable
					switch ($error)
					{
					// curl errors
					case CURLE_WRITE_ERROR:
					case CURLE_SEND_ERROR:
					case CURLE_READ_ERROR:
					case CURLE_RECV_ERROR:
					case CURLE_OPERATION_TIMEDOUT:
					case CURLE_GOT_NOTHING:
					case CURLE_AGAIN:
						sleep(5);
						break;

					// HTTP result codes
					case 408:	// Request Timeout
					case 500:	// Internal Server Error
					case 503:	// Service Unavailable
					case 504:	// Gateway Timeout
					case 507:	// Insufficient Storage
						sleep(15);
						break;

					default:
						// Not recoverable
						$retry = 0;
					}
				}
			}

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
	global $db;

	try
	{
		$error = null;

		// Is airline already in database?
		$st = $db->prepare(<<<SQL
			/*[Q22]*/
			SELECT `id`
			FROM `airlines`
			WHERE `code` = :code;
			SQL
		);

		$st->execute(["code" => $airline->code]);

		if (isset($DEBUG['sql']))
			echo unify_query($st->queryString);

		if (0 == $st->rowCount())
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
			$row = $st->fetch(PDO::FETCH_NUM);

			if ($row)
				$airline->id = (int)$row[0];

			if (isset($DEBUG['sql']))
				echo "=$airline->id\n";
		}
	}
	catch (PDOException $ex)
	{
		$error = sqlErrorInfo($ex, $st);
	}

	return $error;
}

function SQL_InsertAirline(/* in/out */ &$airline)
{
	global $DEBUG;
	global $db;

	if (0 == strlen($airline->code))
	{
		$error = seterrorinfo(__LINE__, 'strlen(airline)');
	}
	else
	{
		$error = null;

		if (0 == strlen($airline->name))
			$airline->name = $airline->code;

		try
		{
			$st = $db->prepare(<<<SQL
				/*[Q23]*/
				INSERT INTO `airlines`(`code`, `name`)
				VALUES(:code, :name);
				SQL
			);

			$st->execute([
				"code" => $airline->code,
				"name" => $airline->name,
			]);

			$airline->id = (int)$db->lastInsertId();

			if (isset($DEBUG['sql']))
			{
				echo unify_query($st->queryString);
				echo "=$airline->id\n";
			}
		}
		catch (PDOException $ex)
		{
			$error = sqlErrorInfo($ex, $st);
		}
	}

	return $error;
}

function SQL_GetAirport(/* in/out */ &$airport)
{
	global $DEBUG;
	global $db;

	try
	{
		$error = null;

		$st = $db->prepare(<<<SQL
			/*[Q24]*/
			SELECT DISTINCT `airports`.`id`
			FROM `airports`
			WHERE `iata` = :iata;
			SQL
		);

		$st->execute(["iata" => $airport->iata]);

		if (isset($DEBUG['sql']))
			echo unify_query($st->queryString);

		if (0 == $st->rowCount())
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
			while ($row = $st->fetch(PDO::FETCH_NUM))
			{
				if ($row)
					$airport->id = (int)$row[0];

				if (isset($DEBUG['sql']))
					echo "=$airport->id\n";
			}
		}
	}
	catch (PDOException $ex)
	{
		$error = sqlErrorInfo($ex, $st);
	}

	return $error;
}

function SQL_InsertAirport(/* in/out */ &$airport)
{
	global $DEBUG;
	global $db;

	if (0 == strlen($airport->iata) ||
		0 == strlen($airport->icao))
	{
		$error = seterrorinfo(__LINE__, 'strlen(airport)');
	}
	else
	{
		try
		{
			$error = null;

			$st = $db->prepare(<<<SQL
				/*[Q25]*/
				INSERT INTO `airports`(
					`iata`,
					`icao`,
					`name`,
					`country`
				)
				VALUES(
					:iata,
					:icao,
					:name,
					:country
				);
				SQL
			);

			$st->execute([
				"iata" => $airport->iata,
				"icao" => $airport->icao,
				"name" => $airport->name ? $airport->name : "",
				"country" => $airport->country->id,
			]);

			$airport->id = (int)$db->lastInsertId();

			if (isset($DEBUG['sql']))
			{
				echo unify_query($st->queryString);
				echo "=OK\n";
			}
		}
		catch (PDOException $ex)
		{
			$error = sqlErrorInfo($ex, $st);
		}
	}

	return $error;
}

function SQL_GetAircraftType(/* in/out*/ &$aircraft)
{
	global $DEBUG;
	global $db;

	try
	{
		$error = null;

		$st = $db->prepare(<<<SQL
			/*[Q26]*/
			SELECT `id`
			FROM `models`
			WHERE `icao` = :icao;
			SQL
		);

		$st->execute(["icao" => $aircraft->type->icao]);

		if (isset($DEBUG['sql']))
			echo unify_query($st->queryString);

		if (0 == $st->rowCount())
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
			$row = $st->fetch(PDO::FETCH_NUM);

			if ($row)
				$aircraft->type->id = (int)$row[0];

			if (isset($DEBUG['sql']))
				echo "={$aircraft->type->id}\n";
		}
	}
	catch (PDOException $ex)
	{
		$error = sqlErrorInfo($ex, $st);
	}

	return $error;
}

function SQL_InsertAircraftType(/* in/out */ &$aircraft)
{
	global $DEBUG;
	global $db;

	if (0 == strlen($aircraft->type->icao))
	{
		$error = seterrorinfo(__LINE__, 'strlen(aircraft->type->icao)');
	}
	else
	{
		try
		{
			$error = null;

			$st = $db->prepare(<<<SQL
				/*[Q27]*/
				INSERT INTO `models`(`icao`,`name`)
				VALUES(:icao, :name);
				SQL
			);

			$st->execute([
				"icao" => $aircraft->type->icao,
				"name" => $aircraft->type->name,
			]);

			$aircraft->type->id = (int)$db->lastInsertId();

			if (isset($DEBUG['sql']))
			{
				echo unify_query($st->queryString);
				echo "={$aircraft->type->id}\n";
			}
		}
		catch (PDOException $ex)
		{
			$error = sqlErrorInfo($ex, $st);
		}
	}

	return $error;
}

function SQL_GetAircraft(/* in/out*/ &$aircraft)
{
	global $DEBUG;
	global $db;

	try
	{
		$error = null;

		$st = $db->prepare(<<<SQL
			/*[Q28]*/
			SELECT `id`
			FROM `aircrafts`
			WHERE `reg` = :reg;
			SQL
		);

		$st->execute(["reg" => $aircraft->reg]);

		if (isset($DEBUG['sql']))
			echo unify_query($st->queryString);

		if (0 == $st->rowCount())
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
			$row = $st->fetch(PDO::FETCH_NUM);

			if ($row)
				$aircraft->id = (int)$row[0];

			if (isset($DEBUG['sql']))
				echo "=$aircraft->id\n";
		}
	}
	catch (PDOException $ex)
	{
		$error = sqlErrorInfo($ex, $st);
	}

	return $error;
}

function SQL_InsertAircraft(/* in/out*/ &$aircraft)
{
	global $DEBUG;
	global $db;

	if (0 == strlen($aircraft->reg))
	{
		$error = seterrorinfo(__LINE__, 'strlen(aircraft->reg)');
	}
	else if (0 == $aircraft->type->id)
	{
		$error = seterrorinfo(__LINE__, 'aircraft->type->id == 0');
	}
	else
	{
		try
		{
			$error = null;

			$st = $db->prepare(<<<SQL
				/*[Q29]*/
				INSERT INTO `aircrafts`(`reg`, `model`)
				VALUES(:reg, :model);
				SQL
			);

			$st->execute([
				"reg" => $aircraft->reg,
				"model" => $aircraft->type->id,
			]);

			$aircraft->id = (int)$db->lastInsertId();

			if (isset($DEBUG['sql']))
			{
				echo unify_query($st->queryString);
				echo "=$aircraft->id\n";
			}
		}
		catch (PDOException $ex)
		{
			$error = sqlErrorInfo($ex, $st);
		}
	}

	return $error;
}

function SQL_GetFlightDetails(/* in */ $dir, /* in */ $f, /* out */ &$id, /* out */ &$ac, /* out */ &$lu)
{
	global $DEBUG;
	global $db;

	try
	{
		$error = null;

		$st = $db->prepare(<<<SQL
			/*[Q30]*/
			SELECT
				`id`,
				`aircraft`,
				`last update`
			FROM
				`flights`
			WHERE
				`direction` = :dir AND
				`airline` = :airline AND
				`code` = :code AND
				`scheduled` = :scheduled
			SQL
		);

		$st->execute([
			"dir" => $dir,
			"airline" => $f->airline->id,
			"code" => $f->fnr,
			"scheduled" => date("Y-m-d H:i:s", strtotime($f->scheduled)),
		]);

		if (isset($DEBUG['sql']))
			echo unify_query($st->queryString);

		$row = $st->fetchObject();

		if (false === $row)
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
			$id = (int)$row->id;
			$ac = $row->aircraft ? (int)$row->aircraft : null;
			$lu = $row->{'last update'};

			if (isset($DEBUG['sql']))
				echo "=$id,$ac,$lu\n";
		}
	}
	catch (PDOException $ex)
	{
		$error = sqlErrorInfo($ex, $st);
	}

	return $error;
}

function SQL_UpdateFlightDetails(/* in */ $id, /* in */ $f)
{
	global $DEBUG;
	global $db;

	$estimated = null;

	// Don't update flights which should have been landed > 3 days ago,
	// the status of these have probably not been updated...
	if ($f->estimated)
		if (strtotime($f->estimated) >= strtotime('-3 days'))
			$estimated = $f->estimated;

	try
	{
		$error = null;

		// Don't overwrite `estimated`/`airport` with null!
		$st = $db->prepare(<<<SQL
			/*[Q31]*/
			UPDATE `flights`
			SET
				`estimated` = coalesce(:estimated, `estimated`),
				`airport` = coalesce(:airport, `airport`),
				`aircraft` = :aircraft,
				`model` = :model
			WHERE
				`id` = :id;
			SQL
		);

		$st->execute([
			"estimated" => $estimated ? date("Y-m-d H:i:s", strtotime($estimated)) : null,
			"airport" => $f->airport->id,
			"aircraft" => $f->aircraft->id,
			"model" => $f->aircraft->type->id,
			"id" => $id,
		]);

		if (isset($DEBUG['sql']))
		{
			echo unify_query($st->queryString);
			echo "=OK\n";
		}
	}
	catch (PDOException $ex)
	{
		$error = sqlErrorInfo($ex, $st);
	}

	return $error;
}

function SQL_InsertFlight(/*in*/ $type, /* in */ $dir, /* in/out */ &$f)
{
	global $DEBUG;
	global $db;

	$estimated = null;

	if ($f->estimated)
		if (strtotime($f->estimated) >= strtotime('-3 days'))
			$estimated = $f->estimated;

	try
	{
		$error = null;

		$st = $db->prepare(<<<SQL
			/*[Q32]*/
			INSERT INTO `flights`
			(
				`direction`,
				`type`,
				`airline`,
				`code`,
				`scheduled`,
				`estimated`,
				`airport`,
				`model`,
				`aircraft`,
				`last update`
			)
			VALUES
			(
				:dir,
				:type,
				:airline,
				:code,
				:scheduled,
				:estimated,
				:airport,
				:model,
				:aircraft,
				:lu
			);
			SQL
		);

		$st->execute([
			"dir" => $dir,
			"type" => $type,
			"airline" => $f->airline->id,
			"code" => $f->fnr,
			"scheduled" => $s = date("Y-m-d H:i:s", strtotime($f->scheduled)),
			"estimated" => $estimated ? date("Y-m-d H:i:s", strtotime($estimated)) : null,
			"airport" => $f->airport->id,
			"model" => $f->aircraft->type->id,
			"aircraft" => $f->aircraft->id,
			"lu" => date("Y-m-d H:i:s", strtotime($f->lu)),
		]);

		// Don't bother about id here...
		if (isset($DEBUG['sql']))
		{
			echo unify_query($st->queryString);
			echo "=OK\n";
		}
	}
	catch (PDOException $ex)
	{
		$error = sqlErrorInfo($ex, $st);
	}

	return $error;
}

function SQL_DeleteFlight($id)
{
	global $DEBUG;
	global $db;

	if (!$id)
	{
		$error = seterrorinfo(__LINE__, 'EINVAL');
	}
	else
	{
		try
		{
			$error = null;

			$st = $db->prepare(<<<SQL
				/*[Q33]*/
				DELETE
				FROM `flights`
				WHERE `id` = :id
				SQL
			);

			$result = $st->execute(["id" => $id]);

			if (isset($DEBUG['sql']))
			{
				echo unify_query($st->queryString);
				echo "=OK\n";
			}

			if (0 == $result)
				warn_once(__LINE__, "No flight deleted: $id");
		}
		catch (PDOException $ex)
		{
			$error = sqlErrorInfo($ex, $st);
		}
	}

	return $error;
}

function SQL_UpdateVisitsToFra($scheduled, $aircraft, $op)
{
	global $DEBUG;
	global $db;

	try
	{
		$error = null;

		$st = $db->prepare(<<<SQL
			/*[Q34]*/
			SELECT `num`, `current`, `previous`
			FROM `visits`
			WHERE `aircraft` = :aircraft;
			SQL
		);

		$st->execute(["aircraft" => $aircraft]);

		if (isset($DEBUG['sql']))
			echo unify_query($st->queryString);

		$row = $st->fetchObject();

		if (!$row)
		{
			if (isset($DEBUG['sql']))
			{
				if ('html' == $DEBUG['fmt'])
					echo htmlspecialchars("=<empty>\n");
				else
					echo "=<empty>\n";
			}

			if (VisitsToFra::DEC == $op)	// "annulliert"
			{
				warn_once(__LINE__, "No visits found for '{$aircraft}'.");
			}
			else
			{
				$st = $db->prepare(<<<SQL
					/*[Q35]*/
					INSERT INTO `visits`(
						`aircraft`,
						`num`,
						`current`,
						`previous`
					)
					VALUES(
						:aircraft,
						1,
						:scheduled,
						NULL
					);
					SQL
				);

				$result = $st->execute([
					"aircraft" => $aircraft,
					"scheduled" => date("Y-m-d H:i:s", strtotime($scheduled)),
				]);

				if (isset($DEBUG['sql']))
				{
					echo unify_query($st->queryString);
					echo "=OK\n";
				}
			}
		}
		else
		{
			$num = (int)$row->num;
			$current = $row->current;
			$previous = $row->previous;
			$row = null;

			if (isset($DEBUG['sql']))
			{
				$prev = $previous ? "'{$previous}'" : 'NULL';
				echo "=$num,'$current',$prev\n";
			}

			if (VisitsToFra::INC == $op)
			{
				if (strtotime($scheduled) <= strtotime($current))
				{
					// flight earlier than last visit previously known
				}
				else
				{
					$st = $db->prepare(<<<SQL
						/*[Q36]*/
						UPDATE `visits`
						SET
							`num` = :num,
							`current` = :scheduled,
							`previous` = :previous
						WHERE
							`aircraft` = :aircraft
						SQL
					);

					$result = $st->execute([
						"num" => ++$num,
						"scheduled" => date("Y-m-d H:i:s", strtotime($scheduled)),
						"previous" => $current,
						"aircraft" => $aircraft,
					]);

					if (isset($DEBUG['sql']))
					{
						echo unify_query($st->queryString);
						echo "=OK\n";
					}
				}
			}
			else
			{
				if ($num < 1)
				{
					warn_once(__LINE__, "No visits found for '{$aircraft}'.");
				}
				else if ($num == 1)
				{
					$st = $db->prepare(<<<SQL
						/*[Q37]*/
						DELETE FROM `visits`
						WHERE `aircraft` = :aircraft
						SQL
					);

					$result = $st->execute(["aircraft" => $aircraft]);

					if (isset($DEBUG['sql']))
					{
						echo unify_query($st->queryString);
						echo "=OK\n";
					}
				}
				else
				{
					/*	This happens when multiple flights for this aircraft get
						cancelled, or for bulk INSERT. e.g. when testing, in which
						case `num`/`previous` may therefore be set incorrectly. */
					if (!$previous)
					{
						$st = $db->prepare(<<<SQL
							/*[Q38]*/
							SELECT MAX(`scheduled`) AS `scheduled`
							FROM `flights`
							WHERE
								`direction` = 'arrival' AND
								`aircraft` = :aircraft
							SQL
						);

						$st->execute(["aircraft" => $aircraft]);

						if (isset($DEBUG['sql']))
							echo unify_query($st->queryString);

						$row = $st->fetchObject();

						if ($row)
						{
							$previous = $row->scheduled;

							if (isset($DEBUG['sql']))
								echo "='$previous'\n";
						}
						else
						{
							if (isset($DEBUG['sql']))
							{
								if ('html' == $DEBUG['fmt'])
									echo htmlspecialchars("=<empty>\n");
								else
									echo "=<empty>\n";
							}
						}
					}

					if ($previous)
					{
						$st = $db->prepare(<<<SQL
							/*[Q39]*/
							UPDATE `visits`
							SET
								`num` = :num,
								`current` = :previous,
								`previous` = NULL
							WHERE
								`aircraft` = :aircraft
							SQL
						);

						$result = $st->execute([
							"num" => --$num,
							"previous" => $previous,
							"aircraft" => $aircraft,
						]);

						if (isset($DEBUG['sql']))
						{
							echo unify_query($st->queryString);
							echo "=OK\n";
						}
					}
				}
			}
		}
	}
	catch (PDOException $ex)
	{
		$error = sqlErrorInfo($ex, $st);
	}

	return $error;
}

/* Delete all notifications for cancelled flights or
   those not having been sent, if aircraft changes */
function SQL_DeleteNotifications($id, $all)
{
	global $DEBUG;
	global $db;

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

		try
		{
			$error = null;

			$st = $db->prepare(<<<SQL
				/*[Q40]*/
				DELETE
				FROM `watchlist-notifications`
				WHERE `flight` = :id{$cond}
				SQL
			);

			$st->execute(["id" => $id]);

			if (isset($DEBUG['sql']))
			{
				echo unify_query($st->queryString);
				echo "=OK\n";
			}
		}
		catch (PDOException $ex)
		{
			$error = sqlErrorInfo($ex, $st);
		}
	}

	return $error;
}

function SendWatchlistNotification($name, $email, $fmt, $locale, $notifications)
{
	global $DEBUG;
	global $now;
	global $db;

	if ('de' == $locale)
	{
		$locale = setlocale(LC_TIME, 'deu', 'deu_deu');
		$STRINGS = [
				'watchlist' => 'Beobachtungsliste',
				'schedule' => 'Flugplan',
		];
	}
	else
	{
		$locale = setlocale(LC_TIME, 'eng', 'english-uk', 'uk', 'enu', 'english-us', 'us', 'english', 'C');
		$STRINGS = [
			'watchlist' => 'watchlist',
			'schedule' => 'schedule',
		];
	}

	$today = mktime_c(gmstrftime('%d.%m.%Y', $now->time_t));

	if (null == $fmt)
		$fmt = '%+ %H:%M';

	$n = 0;
	$text = '';

	if (isset($DEBUG['any']))
		echo "$email:\n";

	foreach ($notifications as $notification)
	{
		$offset = (int)(($notification['expected'] - $today) / 86400);	// TODO: DST changes fix

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
	}

	if (isset($DEBUG['any']))
		echo "$text";

	$to = mb_encode_mimeheader($name, 'ISO-8859-1', 'Q')."<$email>";
	$subject = mb_encode_mimeheader("$STRINGS[watchlist]", 'ISO-8859-1', 'Q');
	$header = sprintf(
		"From: %s <%s>\n".
		"Reply-To: %s\n".
		"Mime-Version: 1.0\n".
		"Content-type: text/plain; charset=ISO-8859-1\n".
		"Content-Transfer-Encoding: 8bit\n".
		"X-Mailer: PHP/%s\n",
		"FRA $STRINGS[schedule]",
		"watchlist@fra-flugplan.de",
		ADMIN_EMAIL,
		phpversion());

	$text = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');

	if (!mail($to, $subject, $text, $header))
	{
		$error = seterrorinfo(0, null);
	}
	else
	{
		$error = null;
		$params = [
			$email,
			$now->atom,
		];

		foreach ($notifications as $notification)
			$params[] = $notification['id'];

		$in = str_repeat("?,", count($params) - 3) . "?";

		try
		{
			$st = $db->prepare(<<<SQL
				/*[Q47]*/
				UPDATE `watchlist-notifications`
				LEFT JOIN `watchlist`
					ON `watchlist`.`id`=`watchlist-notifications`.`watch`
				INNER JOIN `users`
					ON `users`.`id`=`watchlist`.`user`
						AND `users`.`email` = ?
				SET `watchlist-notifications`.`notified` = ?
				WHERE `watchlist-notifications`.`id` IN($in)
				SQL
			);

			$st->execute($params);

			if (isset($DEBUG['sql']))
				echo unify_query($st->queryString);
		}
		catch (PDOException $ex)
		{
			$error = sqlErrorInfo($ex, $st);
		}
	}

	return $error;
}

// main()

$error = null;

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
	try
	{
		if (isset($DEBUG['sql']))
			$namespace = 'flederwiesel\sql\xpose';
		else
			$namespace = null;

		if (isset($ExplainSQL))
			$classname = 'xPDO';
		else
			$classname = "$namespace\\PDO";

		$db = new $classname(
			sprintf(
				"mysql:host=%s;dbname=%s;charset=utf8",
				DB_HOSTNAME,
				DB_NAME
			),
			DB_USERNAME,
			DB_PASSWORD,
			[
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'",
			]
		);
	}
	catch (PDOException $ex)
	{
		$error = sqlErrorInfo($ex);
	}

	if (!$error)
	{
		// Iterate through [pax,cargo] [arrival, departure] tables
		foreach (['P', 'C'] as $type)
		{
			foreach (['arrival', 'departure'] as $dir)
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
						if (FlightStatus::IGNORE == $f->status)
						{
							if (isset($DEBUG['sql']))
								echo "/* ignored */\n";
						}
						else
						{
							$visits = VisitsToFra::NOP;

							if (FlightStatus::CANCELLED == $f->status)
							{
								if ($f->id)
								{
									$visits = VisitsToFra::DEC;

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
									$visits = VisitsToFra::INC;
									$error = SQL_InsertFlight($type, $dir, $f);
								}
								else
								{
									$error = SQL_UpdateFlightDetails($f->id, $f);

									if (!$error)
									{
										if (null == $ac)
										{
											if ('arrival' == $dir)
												$visits = VisitsToFra::INC;
										}
										else
										{
											if ($f->aircraft->id != $ac)
											{
												if ('arrival' == $dir)
												{
													SQL_UpdateVisitsToFra($f->scheduled, $ac, VisitsToFra::DEC);

													$visits = VisitsToFra::INC;	/* for $f->aircraft */
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

		try
		{
			/* Add watches to `watchlist-notifications` table */
			$st = $db->prepare(<<<SQL
				/*[Q44]*/
				INSERT INTO `watchlist-notifications`(`flight`, `watch`)

				SELECT
					`flights`.`id`,
					`watchlist`.`id`

				FROM
					`watchlist`

				INNER JOIN `aircrafts`
					ON `aircrafts`.`reg` LIKE REPLACE(REPLACE(`watchlist`.`reg`, '*', '%'), '?', '_')
					OR `aircrafts`.`reg` RLIKE REPLACE(`watchlist`.`reg`, '/', '') AND `watchlist`.`reg` LIKE '/_%/'

				INNER JOIN `flights`
					ON  `flights`.`aircraft` = `aircrafts`.`id` AND
						`flights`.`aircraft` IS NOT NULL

				LEFT JOIN `watchlist-notifications`
					ON  `watchlist-notifications`.`watch` = `watchlist`.`id` AND
						`watchlist-notifications`.`flight` = flights.id

				WHERE
					`watchlist`.`notify` = TRUE AND
					`watchlist-notifications`.`flight` IS NULL AND
					'arrival' = `flights`.`direction` AND
					TIMESTAMPDIFF(SECOND, :now, `flights`.`expected`) > 0

				FOR UPDATE
				SQL
			);

			$st->execute(["now" => $now->atom]);

			/* Check whether or which notifications are to be sent */
			if (isset($DEBUG['sql']))
				echo unify_query($st->queryString);
		}
		catch (PDOException $ex)
		{
			$error = sqlErrorInfo($ex, $st);
		}

		try
		{
			$st = $db->prepare(<<<SQL
				/*[Q45]*/
				SELECT
					`watchlist-notifications`.`id` AS `id`,
					UNIX_TIMESTAMP(`flights`.`expected`) AS `expected`,
					CONCAT(
						`airlines`.`code`,
						`flights`.`code`
					) AS `flight`,
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
				WHERE `flights`.`expected` > :atom
					AND `notified` IS NULL
					AND
						FROM_UNIXTIME(:time_t, '%H:%i:%s')
						BETWEEN `users`.`notification-from`
							AND `users`.`notification-until`
				ORDER BY
					`email` ASC,
					`expected` ASC
				SQL
			);

			$st->execute([
				"atom" => $now->atom,
				"time_t" => $now->time_t,
			]);

			if (isset($DEBUG['sql']))
				echo unify_query($st->queryString);

			$text = null;
			$name = null;
			$email = null;
			$watch = null;
			$fmt = null;
			$lang = 'en';

			$notifications = [];

			$row = $st->fetchObject();

			while ($row)
			{
				if ($email != $row->email)
				{
					/* We get here every time $row['email'] changes, at least */
					/* once at the beginning, i.e. $time and $fmt will be set, */
					/* if one row has been found  */
					if ($email != null)
					{
						/* Flush */
						SendWatchlistNotification($name, $email, $fmt, $lang, $notifications);
						$notifications = [];
					}

					/* Remember first ID of new email */
					$email = $row->email;
					$name = $row->name;
					$fmt = $row->fmt;
					$lang = $row->lang;
				}

				$notifications[] = [
					'id'       => (int)$row->id,
					'expected' => $row->expected,
					'flight'   => $row->flight,
					'reg'      => $row->reg,
					'comment'  => $row->comment,
				];

				$row = $st->fetchObject();
			}

			if ($email)
				SendWatchlistNotification($name, $email, $fmt, $lang, $notifications);

			unset($notifications);
		}
		catch (PDOException $ex)
		{
			$error = sqlErrorInfo($ex, $st);
		}

		try
		{
			/* Delete notifications for flights having been arrived prior to yesterday */
			$st = $db->prepare(<<<SQL
				/*[Q46]*/
				DELETE `watchlist-notifications`
				FROM `watchlist-notifications`
				INNER JOIN `flights`
					ON `flights`.`id`=`watchlist-notifications`.`flight`
					WHERE (
					DATEDIFF(
						:now,
						`flights`.`expected`
					) > 1
				)
				SQL
			);

			$st->execute(["now" => $now->atom]);

			if (isset($DEBUG['sql']))
				echo unify_query($st->queryString);
		}
		catch (PDOException $ex)
		{
			$error = sqlErrorInfo($ex, $st);
		}
	}

	/* betriebsrichtung.html */
	$file = @fopen("$datadir/betriebsrichtung.ini", "w");

	if ($file)
	{
		$url = "https://${prefix}sslapps.fraport.de/betriebsrichtung/betriebsrichtungsvg.js";

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
			$line = strtok($betriebsrichtung, "\n");

			while ($line)
			{
				if (strstr($line, '<h2>Betriebsrichtung</h2>'))
				{
					$line = strtok("\n");

					if ($line)
						if (preg_match("/<p>[ \t]*(07|25|99)[ \t]*/", $line, $match))
							fwrite($file, "$match[1] = active\n");
				}
				else if (strstr($line, '<p>18 West<br/>'))
				{
					$line = strtok("\n");

					if ($line)
					{
						$rwy18 = strstr($line, "in Betrieb") ? '' : 'in';
						fwrite($file, "18 = active\n");
						break;
					}
				}

				$line = strtok("\n");
			}
		}

		fclose($file);
	}

	unset($curl);
}

if ($errorinfo)
{
	if (isset($DEBUG['any']))
		echo $errorinfo;

	mail(mb_encode_mimeheader(ADMIN_NAME, 'ISO-8859-1', 'Q').'<'.ADMIN_EMAIL.'>',
		 "getflights.php: error",
		 "$errorinfo",
		 "From: FRA-Flugplan");
}

if ($warning)
{
	if (isset($DEBUG['any']))
		echo $warning;

	mail(mb_encode_mimeheader(ADMIN_NAME, 'ISO-8859-1', 'Q').'<'.ADMIN_EMAIL.'>',
		 "getflights.php: warning",
		 "$warning",
		 "From: FRA-Flugplan");
}

if ($info)
{
	if (isset($DEBUG['any']))
		echo $info;

	mail(mb_encode_mimeheader(ADMIN_NAME, 'ISO-8859-1', 'Q').'<'.ADMIN_EMAIL.'>',
		 "getflights.php: info",
		 "$info",
		 "From: FRA-Flugplan");
}

if (isset($DEBUG['any']))
	echo "\n\n=== fin. ===\n";

fin:
//printf("\ntotal duration: %f\n", microtime(true) - $script_start);

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
