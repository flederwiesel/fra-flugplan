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

include ".config";
include "classes/vector.php";
include "classes/awk.php";

// We need to adjust departure times, make sure we use the correct tz
$tz = date_default_timezone_set('Europe/Berlin');

$baseurl = 'www.frankfurt-airport.de';
$rwyinfo = 'apps.fraport.de';
$now = strftime('%Y-%m-%d %H:%M:%S');
$items = 15;

if (isset($_GET['baseurl']))
{
	/* Those may be overridden for testing */
	$baseurl = $_GET['baseurl'];
	$rwyinfo = $_GET['baseurl'];

	if (isset($_GET['now']))
		$now = $_GET['now'];

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

	$error .= __FILE__."($line): ERROR: $info\n";

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

class airport_info
{
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

function awk_flights_number($rule, $fields)
{
	global $DEBUG;
	global $flights;
	global $f;

	if (preg_match('/<h3>/', $fields[0], $match))
	{
		if ($f)
			if (isset($DEBUG['awk']))
				print_r($f);

		if (0 == strncmp('<h3>Leider ', $fields[0], 11))
		{
			// Leider liegen keine Daten aktueller Abflüge vor.
			// Bitte versuchen Sie es zu einem späteren Zeitpunkt erneut oder
			// wenden Sie sich an das Fraport Communication Center unter der Telefonnummer 01805-3 72 46 36 (FRAINFO).
		}
		else
		{
			$f = $flights->push(new flight(str_replace('<h3>', '', $fields[1]), $fields[2]));

			if (NULL == $f)
				$error = seterrorinfo(__LINE__, implode(",", error_get_last()));
		}
	}
}

function awk_flights_airline($rule, $fields)
{
	global $f;

	// "a-z.html#OZ\">Asiana Airlines</"

	$fields = explode("#", $fields[0]);
	$code   = explode("\"", $fields[1]);
	$name   = explode(">", $fields[1]);
	$name   = explode("<", $name[1]);

	$f->carrier['name'] = $name[0];
	$f->carrier['code'] = $code[0];
}

function awk_flights_scheduled($rule, $fields)
{
	global $f;

	if (count($fields) > 2)
		$f->scheduled = $fields[2];
}

function awk_flights_expected($rule, $fields)
{
	global $f;

	if (count($fields) > 2)
		$f->expected = $fields[2];
}

function awk_flights_date($rule, $fields)
{
	global $f;

	// different for arrival and departure
	if (preg_match('/[0-9][0-9].[0-9][0-9].[0-9][0-9][0-9][0-9]/', $fields[2]))
		$col = 2;
	else
		$col = 5;

	$date = explode(".", $fields[$col]);

	// prepend "YYYY-mm-dd" to "HH:MM"
	if ($f->expected)
		$f->expected = $date[2].'-'.$date[1].'-'.$date[0].' '.$f->expected;
	else if ($f->scheduled)
		$f->scheduled = $date[2].'-'.$date[1].'-'.$date[0].' '.$f->scheduled;
}

function awk_flights_model($rule, $fields)
{
	global $f;

	if (count($fields) > 2)
		$f->model = $fields[2];
}

function awk_flights_reg($rule, $fields)
{
	global $f;

	if (count($fields) > 2)
		$f->reg = patchreg($fields[2]);
}

function awk_flights_remark($rule, $fields)
{
	global $tz;
	global $now;
	global $f;

	$remark = getline();

	/*
		$remark should be one of the following:

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
		$f->expected = 'NULL';
	}
	else if (preg_match('/<p>Gepäckausgabe( beendet)?<\/p>/', $remark))
	{
		// Don't update any more
		$f->scheduled = NULL;
	}
	else if (preg_match('/<p>gestartet<\/p>/', $remark))
	{
		// Don't update flight any more, unless $f->expected id in the future
		if ($f->expected)
			if (strtotime($f->expected) < strtotime($now))
				$f->scheduled = NULL;
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
			if (NULL == $f->expected)
			{
				if (strtotime($f->scheduled) < strtotime($now))
					$f->expected = strftime('%Y-%m-%d %H:%M', strtotime('+5 min', strtotime($now)));
			}
			else
			{
				if (strtotime($f->expected) < strtotime($now))
					$f->expected = strftime('%Y-%m-%d %H:%M', strtotime('+5 min', strtotime($now)));
			}
		}
	}
}

function awk_flights_next($rule, $fields)
{
	global $f;
	global $page;
	global $DEBUG;

	if (count($fields) < 2)
	{
		$page = 0;
	}
	else
	{
		$fields = explode("#", $fields[0]);

		if (count($fields) < 2)
			$page = 0;
		else
			$page = 0 + $fields[1];
	}

	if (isset($DEBUG['awk']))
	{
		print_r($f);
		echo "page=$page\n";
	}
}

function awk_airports_iata($rule, $fields)
{
	global $airports;
	global $airport;
	global $previous;
	global $fi;

	$airport = $airports->push(new airport_info($fi[0]));

	if ($airport)
	{
		$airport->iata = $fields[2];
		$airport->name = $previous;
	}
}

function awk_airports_icao($rule, $fields)
{
	global $airport;
	global $DEBUG;

	$airport->icao = $fields[2];

	if (isset($DEBUG['awk']))
		print_r($airport);
}

function awk_airports_all($rule, $fields)
{
	// remember last line for rule awk_airports_iata()
	global $previous; $previous = $fields[0];
}

$awk_flights = array(

/******************************************************************************
 * flights.awk
 ******************************************************************************/

'/<h3>/,/<\/h3>/',                                      'awk_flights_number',
'/airlines_a-z/',                                       'awk_flights_airline',
'/Planm\xc3\xa4\xc3\x9fig, [0-9][0-9]:[0-9][0-9] Uhr/', 'awk_flights_scheduled',
'/Erwartet: [0-9][0-9]:[0-9][0-9] Uhr/',                'awk_flights_expected',
'/[0-9][0-9].[0-9][0-9].[0-9][0-9][0-9][0-9][\r]*$/',   'awk_flights_date',
'/Flugzeugtyp:/',                                       'awk_flights_model',
'/Registrierung:/',                                     'awk_flights_reg',
'/Bemerkung:/',                                         'awk_flights_remark',
'/>weiter</',                                           'awk_flights_next',

/******************************************************************************
 * /flights.awk
 ******************************************************************************/

);

$awk_airports = array(

/******************************************************************************
 * airports.awk
 ******************************************************************************/

'/IATA-Code:/', 'awk_airports_iata',
'/ICAO-Code:/', 'awk_airports_icao',
'//',           'awk_airports_all',

/******************************************************************************
 * /airports.awk
 ******************************************************************************/

);

function GetFlights($curl, $dir, &$flights)
{
	global $DEBUG;
	global $baseurl;
	global $awk_flights;
	global $f;
	global $items;
	global $page;

	$error = NULL;
	$f = NULL;
	$flights = new vector;
	$action = 'init';
	$page = 1;
	$date = 0;
	$previous = 0;

	do
	{
		$url = "http://$baseurl/flugplan/airportcity?".
			   "type=$dir&typ=p&context=0&sprache=de&items=$items&$action=true&page=$page";

		if (isset($DEBUG['url']))
			echo "$url\n";

		$page = 0;
		$htm = curl_download($curl, $url);

		curl_setopt($curl, CURLOPT_COOKIESESSION, FALSE);	// reuse session cookie

		if (0 == strlen($htm))
		{
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
		}

		// create $flights[$n<flight>] from pager html
		awk($awk_flights, $htm);

		$action = 'usepager';
	}
	while ($page > 0);

	return $error;
}

function GetAirlineId($f, &$airline)
{
	global $DEBUG;
	global $uid;

	$error = NULL;
	$airline = NULL;

	// Is airline already in database?
	$query = "SELECT `id` FROM `airlines` WHERE `code`='".$f->airline."';";

	if (isset($DEBUG['query']))
		echo "$query\n";

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
	}
	else
	{
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

				if (isset($DEBUG['query']))
					echo "$query\n";

				if (mysql_query($query))
				{
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

function GetCarrierId($f, &$carrier)
{
	global $DEBUG;
	global $uid;

	$error = NULL;
	$carrier = NULL;
	$query = "SELECT `id` FROM `airlines` WHERE `code`='".$f->carrier['code']."';";

	if (isset($DEBUG['query']))
		echo "$query\n";

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
	}
	else
	{
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

				if (isset($DEBUG['query']))
					echo "$query\n";

				if (mysql_query($query))
				{
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

function GetModelId($f, &$model)
{
	global $DEBUG;
	global $uid;

	$error = NULL;
	$model = NULL;

	$query = "SELECT `id` FROM `models` WHERE `icao`='$f->model';";

	if (isset($DEBUG['query']))
		echo "$query\n";

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
	}
	else
	{
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

				if (isset($DEBUG['query']))
					echo "$query\n";

				if (mysql_query($query))
				{
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

function GetAircraftId($f, $model, &$reg)
{
	global $DEBUG;
	global $uid;

	$error = NULL;
	$query = "SELECT `id` FROM `aircrafts` WHERE `reg`='$f->reg';";

	if (isset($DEBUG['query']))
		echo "$query\n";

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__,
					sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
	}
	else
	{
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

				if (isset($DEBUG['query']))
					echo "$query\n";

				if (!mysql_query($query))
				{
					$error = seterrorinfo(__LINE__,
								sprintf("%s [%d] %s", $query, mysql_errno(), mysql_error()));
				}
				else
				{
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

function UpdateVisitsToFra($f, $reg)
{
	global $DEBUG;

	$error = NULL;
	$query = "SELECT `num`,`last` FROM `visits` WHERE `aircraft`=$reg;";
	$result = mysql_query($query);

	if (isset($DEBUG['query']))
		echo "$query\n";

	if (!$result)
	{
		$error = seterrorinfo(__LINE__, $query.": ".mysql_error());
	}
	else
	{
		$row = mysql_fetch_assoc($result);

		mysql_free_result($result);

		if (!$row)
		{
			if ('NULL' == $f->expected)	// "annulliert"
			{
				warn_once(__LINE__, "No visits found for '$reg'.");
			}
			else
			{
				$query = "INSERT INTO `visits`(`aircraft`, `num`, `last`)".
						 "VALUES($reg, 1, '$f->scheduled');";

				if (isset($DEBUG['query']))
					echo "=\n$query\n";
			}
		}
		else
		{
			$num = $row['num'];
			$last = $row['last'];

			if (isset($DEBUG['query']))
				echo "=$num,$last\n";

			if ('NULL' == $f->expected)
			{
				if ($num < 1)
				{
					$query = NULL;
				}
				else
				{
					$query = "UPDATE `visits` ".
							 "SET `num`=".($num - 1).",".
							 " `last`='$f->scheduled' ".
							 "WHERE `aircraft`=$reg";
				}
			}
			else
			{
				if ($f->scheduled <= $last)
				{
					$query = NULL;
				}
				else
				{
					$query = "UPDATE `visits` ".
							 "SET `num`=".('NULL' == $f->expected ? ($num > 0 ? $num - 1 : 0) : $num + 1).",".
							 " `last`='$f->scheduled' ".
							 "WHERE `aircraft`=$reg";
				}
			}

			if (isset($DEBUG['query']) && $query)
				echo "$query\n";
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
					echo "=OK\n";
			}
		}
	}

	return $error;
}

function GetFlightDetails($curl, &$airports)
{
	global $DEBUG;
	global $baseurl;
	global $now;
	global $awk_airports;
	global $fi;

	$error = NULL;

	// Get airport IATA/ICAO from flight details page
	$query = "SELECT".
			 " `flights`.`id`,".
			 " `airlines`.`code`,".
			 " `flights`.`code`,".
			 " `flights`.`scheduled`,".
			 " `flights`.`direction` ".
			 "FROM `flights` ".
			 "LEFT JOIN `airlines` ON `flights`.`airline` = `airlines`.`id` ".
			 "WHERE `airport` IS NULL ".
			 "AND ".
			 " (`scheduled` >= '$now'".
			 "  OR `expected` >= '$now'".
			 "  OR (TIME_TO_SEC(TIMEDIFF('$now', `scheduled`)) / 60 / 60) < 2) ".
			 "ORDER BY `scheduled`;";

	if (isset($DEBUG['query']))
		echo "$query\n";

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__, $query.": ".mysql_error());
	}
	else
	{
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

				if (0 == strlen($htm))
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
			while (0 == strlen($htm) && --$retry);

			awk($awk_airports, $htm);

			/* Set srcipt execution limit. If set to zero, no time limit is imposed. */
			set_time_limit(0);
		}

		mysql_free_result($result);
	}

	return $error;
}

function GetAirportId($airport)
{
	global $DEBUG;
	global $uid;

	$error = NULL;
	$query = "SELECT `airports`.`id` FROM `airports` WHERE `iata`='".$airport->iata."'".
			 " AND `icao`='".$airport->icao."';";

	if (isset($DEBUG['query']))
		echo "$query\n";

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__, $query.": ".mysql_error());
	}
	else
	{
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

			if (isset($DEBUG['query']))
				echo "$query\n";

			if (!mysql_query($query))
			{
				$error = seterrorinfo(__LINE__, $query.": ".mysql_error());
			}
			else
			{
				$airport->id = mysql_insert_id();

				if (isset($DEBUG['query']))
					echo "=OK\n";
			}
		}

		if ($airport->id)
		{
			// Update flight with airport id
			$query = "UPDATE `flights` SET `airport`=$airport->id WHERE `id`=$airport->fid;";

			if (isset($DEBUG['query']))
				echo "$query\n";

			if (!mysql_query($query))
			{
				$error = seterrorinfo(__LINE__,  $query.": ".mysql_error());
			}
			else
			{
				if (isset($DEBUG['query']))
					echo "=OK\n";
			}
		}

		mysql_free_result($result);
	}

	return $error;
}

function InsertOrUpdateFlight($dir, $airline, $code,
							  $scheduled, $expected, $model, $reg)
{
	global $DEBUG;
	global $uid;

	$error = NULL;

	$query = "SELECT `id` FROM `flights` ".
		"WHERE `direction`='$dir'".
		" AND `airline`=$airline".
		" AND `code`='$code'".
		" AND `scheduled`='$scheduled'";

	if (isset($DEBUG['query']))
		echo "$query\n";

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__, $query.": ".mysql_error());
	}
	else
	{
		$id = mysql_fetch_row($result);

		mysql_free_result($result);

		if ($id)
		{
			$id = $id[0];

			if (isset($DEBUG['query']))
				echo "=$id\n";

			$query = "UPDATE `flights` SET ".
				($expected ? "`expected`='$expected', " : "").	// Don't overwrite `expected` with NULL!
				"`aircraft`=".($reg ? $reg : "NULL").",".
				"`model`=".($model ? "$model" : "NULL")." ".
				"WHERE `id`=$id;";
		}
		else
		{
			if (isset($DEBUG['query']))
				echo "=<empty>\n";

			$query = "INSERT INTO `flights` ".
				"(`uid`, `type`, `direction`, `airline`, `code`, ".
				"`scheduled`, `expected`, `aircraft`, `model`) ".
				"VALUES(".
				"$uid, 'pax-regular', '$dir', $airline, '$code', ".
				"'$scheduled', ".
				($expected ? "'$expected'" : "NULL").", ".
				($reg ? $reg : "NULL").", ".
				($model ? "$model": "NULL").");";
		}

		if (isset($DEBUG['query']))
			echo "$query\n";

		if (!mysql_query($query))
		{
			$error = seterrorinfo(__LINE__, $query.": ".mysql_error());
		}
		else
		{
			if (isset($DEBUG['query']))
				echo "=OK\n";
		}
	}

	return $error;
}

function DeleteFlight($dir, $airline, $code, $scheduled, &$aircraft)
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

	if (isset($DEBUG['query']))
		echo "$query\n";

	$result = mysql_query($query);

	if (!$result)
	{
		$error = seterrorinfo(__LINE__, $query.": ".mysql_error());
	}
	else
	{
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

			if (isset($DEBUG['query']))
				echo "$query\n";

			$result = mysql_query($query);

			if (!$result)
			{
				$error = seterrorinfo(__LINE__, $query.": ".mysql_error());
			}
			else
			{
				if (isset($DEBUG['query']))
					echo "=OK\n";

				if (0 == mysql_affected_rows())
					warn_once(__LINE__, "No flight deleted: $dir-$airline-'$code'-'$scheduled'");
			}
		}
	}

	return $error;
}

function FlightsToHistory()
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

					$error = GetFlights($curl, $dir, $flights);

					$time_end = microtime(true);
					$time = $time_end - $time_start;

					$n = $flights->count();

					while ($f = $flights->shift())
					{
						if (0 == strcmp("TRN", $f->model))	// no trains...
						{
							$n--;
						}
						else if (0 == strcmp("NULL", $f->expected))	// "annulliert"
						{
							$error = GetAirlineId($f, $airline);

							if (!$error)
							{
								$error = DeleteFlight($dir, $airline, $f->code, $f->scheduled, $reg);

								if (!$error && 'arrival' == $dir && $reg)
									$error = UpdateVisitsToFra($f, $reg);
							}
						}
						else if ($f->scheduled)
						{
							$airline = NULL;
							$error = GetAirlineId($f, $airline);

							if ($airline)
							{
								// Get carrier id, if different from flight airline code
								// (operated by someone else)
								if ($airline != $f->carrier['code'])
									$error = GetCarrierId($f, $airline);
							}

							// model
							$model = NULL;
							$error = GetModelId($f, $model);

							// aircraft
							$reg = NULL;

							if ($f->reg && $model)
								$error = GetAircraftId($f, $model, $reg);

							// flight
							$error = InsertOrUpdateFlight($dir, $airline, $f->code,
														  $f->scheduled, $f->expected, $model, $reg);

							if (!$error && $reg && 'arrival' == $dir)
								$error = UpdateVisitsToFra($f, $reg);
						}

						if (isset($DEBUG['query']))
							echo "\n/************************************/\n\n";
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
				unset($f);

				/* Get airports from flight details page */
				$airports = new vector;
				$error = GetFlightDetails($curl, $airports);

				while ($airport = $airports->shift())
					$error = GetAirportId($airport);

				unset($airports);
				unset($airport);

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
			$error = FlightsToHistory();
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
