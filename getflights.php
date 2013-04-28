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
 *  Debug options: ?debug=[url,arrival,departure,airports,awk,query]&fmt=[htm|html|...]
 *
 ******************************************************************************/

error_reporting(E_ALL);

include ".config";
include "classes/vector.php";
include "classes/awk.php";

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

function curl_download($ch, $url)
{
	curl_setopt($ch, CURLOPT_URL, $url);

	return curl_exec($ch);
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
		'/^G[^L]/', 1,
		'/^GL/', 2,
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
	global $vector;
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
			$f = $vector->push(new flight(str_replace('<h3>', '', $fields[1]), $fields[2]));

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
	global $f; $f->scheduled = $fields[2];
}

function awk_flights_expected($rule, $fields)
{
	global $f; $f->expected = $fields[2];
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
	global $f; $f->model = $fields[2];
}

function awk_flights_reg($rule, $fields)
{
	global $f; $f->reg = patchreg($fields[2]);
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
		print_r($f);
}

function awk_airports_iata($rule, $fields)
{
	global $vector;
	global $airport;
	global $previous;
	global $fi;

	$airport = $vector->push(new airport_info($fi[0]));

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

$flights_awk = array(

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
'/>weiter</',                                           'awk_flights_next',

/******************************************************************************
 * /flights.awk
 ******************************************************************************/

);

$airports_awk = array(

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

$error = NULL;
$warning = NULL;

$hdbc = mysql_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD);

if (!$hdbc)
{
	$error = seterrorinfo(__LINE__, mysql_error());
}
else
{
	if (!mysql_select_db(DB_NAME, $hdbc))
	{
		$error = seterrorinfo(__LINE__, mysql_error());
	}
	else
	{
		mysql_set_charset("utf8");

		$result = mysql_query("SELECT `id` FROM `users` WHERE `name`='root'");

		if (!$result)
		{
			$error = seterrorinfo(__LINE__, mysql_error());
		}
		else
		{
			$row = mysql_fetch_row($result);

			if ($row)
				$uid = $row[0];
			else
				$error = seterrorinfo(__LINE__, mysql_error());

			mysql_free_result($result);
		}

		if (!$error)
		{
			// is cURL installed yet?
			if (!function_exists('curl_init'))
			{
				$error = seterrorinfo(__LINE__, 'cURL is not installed!');
			}
			else
			{
				// OK cool - then let's create a new cURL resource handle
				$ch = curl_init();

				// Now set some options (most are optional)
				// http://en.php.net/curl_setopt

				// Set a referer
				curl_setopt($ch, CURLOPT_REFERER, "http://www.flederwiesel.com/fra-flights");

				// User agent
				//curl_setopt($ch, CURLOPT_USERAGENT, "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.9.2.28) Gecko/20120306 Firefox/3.6.28 ( .NET CLR 3.5.30729; .NET4.0E)");

				// Include header in result? (0 = yes, 1 = no)
				curl_setopt($ch, CURLOPT_HEADER, 0);

				// Should cURL return or print out the data? (true = return, false = print)
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

				curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);	// start new cookie "session"
				curl_setopt($ch, CURLOPT_FRESH_CONNECT, FALSE);

				// Timeout in seconds
				curl_setopt($ch, CURLOPT_TIMEOUT, 30);

				// Need to use a proxy?
				if (file_exists('.curlrc'))
				{
					$curlrc = file('.curlrc');

					if ($curlrc)
					{
						curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 0);
						curl_setopt($ch, CURLOPT_PROXY, trim($curlrc[0]));
						curl_setopt($ch, CURLOPT_PROXYUSERPWD, trim($curlrc[1]));

						unset($curlrc);
					}
				}

				// Iterate through arrival/departure tables awk()ing basic flight info
				$direction = array('arrival', 'departure');

				foreach ($direction as $dir)
				{
					$vector = new vector;
					$f = NULL;

					$action = 'init';
					$page = 1;
					$date = 0;
					$previous = 0;

					$time_start = microtime(true);

					do
					{
						$url = "http://www.frankfurt-airport.de/flugplan/airportcity?".
							   "type=$dir&typ=p&context=0&sprache=de&items=12&$action=true&page=$page";

						if (isset($DEBUG['url']))
							echo "$url\n";

						$page = 0;
						$htm = curl_download($ch, $url);

						curl_setopt($ch, CURLOPT_COOKIESESSION, FALSE);	// reuse session cookie

						if (0 == strlen($htm))
						{
							if (curl_errno($ch))
								$error = seterrorinfo(__LINE__, curl_error($ch));
						}
						else
						{
							if (isset($DEBUG[$dir]))
							{
								//echo "$htm\n";
								echo str_replace(array('<', '>'), array('&lt;', '&gt;'), $htm);
								echo "\n";
							}
						}

						// create $vector[$n<flight>] from pager html
						awk($flights_awk, $htm);

						$action = 'usepager';
					}
					while ($page > 0);

					$time_end = microtime(true);
					$time = $time_end - $time_start;

					$n = $vector->count();

					while ($f = $vector->shift())
					{
						if (0 == strcmp("TRN", $f->model))	// no trains...
						{
							$n--;
						}
						else
						{
							// Is airline already in database?
							$airline = NULL;
							$query = "SELECT `id` FROM `airlines` WHERE `code`='".$f->airline."';";

							if (isset($DEBUG['query']))
								echo "$query\n";

							$result = mysql_query($query);

							if (!$result)
							{
								$error = seterrorinfo(__LINE__, $query.": ".mysql_error());
							}
							else
							{
								if (1 == mysql_num_rows($result))
								{
									// Yes
									$col = mysql_fetch_row($result);

									if ($col)
										$airline = $col[0];

									if (isset($DEBUG['query']))
										echo "=$airline\n";
								}
								else
								{
									if (isset($DEBUG['query']))
										echo "=<empty>\n";

									// No, insert airline
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
										$error = seterrorinfo(__LINE__, $query.": ".mysql_error());
									}
								}

								mysql_free_result($result);
							}

							if ($airline)
							{
								// Get carrier id, if different from airline
								if ($airline != $f->carrier['code'])
								{
									// flight is operated by someone else
									$carrier = NULL;
									$query = "SELECT `id` FROM `airlines` WHERE `code`='".$f->carrier['code']."';";

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
											$col = mysql_fetch_row($result);

											if ($col)
												$carrier = $col[0];

											if (isset($DEBUG['query']))
												echo "=$carrier\n";
										}
										else
										{
											if (isset($DEBUG['query']))
												echo "=<empty>\n";

											// Not found, insert airline
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
												$error = seterrorinfo(__LINE__, $query.": ".mysql_error());
											}
										}

										mysql_free_result($result);
									}
								}
							}

							// model
							$reg = NULL;
							$model = NULL;
							$query = "SELECT `id` FROM `models` WHERE `icao`='$f->model';";

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
									$col = mysql_fetch_row($result);

									if ($col)
										$model = $col[0];

									if (isset($DEBUG['query']))
										echo "=$model\n";
								}
								else
								{
									if (isset($DEBUG['query']))
										echo "=<empty>\n";

									$query = "INSERT INTO `models`(`uid`, `icao`,`name`) VALUES($uid, '$f->model', '');";

									if (isset($DEBUG['query']))
										echo "$query\n";

									if (mysql_query($query))
									{
										warn_once(__LINE__, "Aircraft $f->model is unknown (flight $f->airline$f->code $f->scheduled).");

										$model = mysql_insert_id();

										if (isset($DEBUG['query']))
											echo "=$model\n";
									}
									else
									{
										$error = seterrorinfo(__LINE__, $query.": ".mysql_error());
									}
								}

								mysql_free_result($result);
							}

							// aircraft
							if ($f->reg && $model)
							{
								$query = "SELECT `id` FROM `aircrafts` WHERE `reg`='$f->reg';";

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
										$col = mysql_fetch_row($result);

										if ($col)
											$reg = $col[0];

										if (isset($DEBUG['query']))
											echo "=$reg\n";
									}
									else
									{
										if (isset($DEBUG['query']))
											echo "=<empty>\n";

										$query = "INSERT INTO `aircrafts`(`uid`, `reg`,`model`)".
												 " VALUES($uid, '$f->reg', $model);";

										if (isset($DEBUG['query']))
											echo "$query\n";

										if (!mysql_query($query))
										{
											$error = seterrorinfo(__LINE__, $query.": ".mysql_error());
										}
										else
										{
											$reg = mysql_insert_id();

											if (isset($DEBUG['query']))
												echo "=$reg\n";
										}
									}

									mysql_free_result($result);
								}
							}

							// flight
							$query = "SELECT `id` FROM `flights` ".
								"WHERE `direction`='$dir' AND `airline`=$airline AND `code`='$f->code' AND `scheduled`='$f->scheduled';";

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

								if ($id)
								{
									$id = $id[0];

									if (isset($DEBUG['query']))
										echo "=$id\n";

									$query = "UPDATE `flights` SET ".
										($f->expected ? "`expected`='$f->expected', " : "").	// Don't overwrite `expected`
										"`aircraft`=".($reg ? "'$reg'" : "NULL").",".
										"`model`=".($model ? "$model" : "NULL")." ".
										"WHERE `id`=$id;";
								}
								else
								{
									if (isset($DEBUG['query']))
										echo "=<empty>\n";

									$query = "INSERT INTO `flights` ".
										"(`uid`, `direction`, `airline`, `code`, ".
										"`scheduled`, `expected`, `aircraft`, `model`) ".
										"VALUES(".
										"$uid, '$dir', $airline, '$f->code', ".
										"'$f->scheduled', ".
										($f->expected ? "'$f->expected'" : "NULL").", ".
										($reg ? "'$reg'" : "NULL").", ".
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

								mysql_free_result($result);
							}
						}

						if (isset($DEBUG['query']))
							echo "\n/************************************/\n\n";
					}

					if (isset($DEBUG['any']))
					{
						printf("%s\n---------------------------\n", $dir);
						printf("%lu Flüge gefunden.\n", $n);
						printf("    %s: %.3fs\n", 'Dauer', $time);
						printf("    \n===========================\n\n");
					}
				}

				unset($vector);

				// Get airport IATA/ICAO from flight details page
				$query = "SELECT `flights`.`id`, `airlines`.`code`, `flights`.`code`, `flights`.`scheduled`, `flights`.`direction` ".
							"FROM `flights` ".
							"LEFT JOIN `airlines` ON `flights`.`airline` = `airlines`.`id` ".
							"WHERE `airport` IS NULL ".
							"AND (`scheduled` >= now() OR `expected` >= now() OR (TIME_TO_SEC(timediff(now(), `scheduled`)) / 60 / 60) < 2) ".
							"ORDER BY `scheduled`;";

				$result = mysql_query($query);

				if (!$result)
				{
					$error = seterrorinfo(__LINE__, $query.": ".mysql_error());
				}
				else
				{
					$vector = new vector;
					$airport = NULL;

					while ($fi = mysql_fetch_row($result))
					{
						$date = substr($fi[3], 0, 4).substr($fi[3], 5, 2).substr($fi[3], 8, 2);
						$url = "http://www.frankfurt-airport.de/flugplan/airportcity?fi".
									substr($fi[4], 0, 1)."=".	// 'a'/'d' -> arrival/departure
									$fi[1].$fi[2].				// LH1234
									$date;//."&sprache=de";		// 20120603

						if (isset($DEBUG['url']))
							echo "$url\n";

						$htm = curl_download($ch, $url);

						if (0 == strlen($htm))
						{
							if (curl_errno($ch))
								$error = seterrorinfo(__LINE__, curl_error($ch));
						}
						else
						{
							if (isset($DEBUG['airports']))
								echo "$htm\n";
						}

						awk($airports_awk, $htm);

						set_time_limit(0);
					}

					mysql_free_result($result);

					while ($airport = $vector->shift())
					{
						// Get airport id
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
								$col = mysql_fetch_row($result);

								if ($col)
									$airport->id = $col[0];
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
									$error = seterrorinfo(__LINE__, $query.": ".mysql_error());
								else
									$airport->id = mysql_insert_id();
							}

							if ($airport->id)
							{
								// Update flight with airport id
								$query = "UPDATE `flights` SET `airport`=$airport->id WHERE `id`=$airport->fid;";

								if (isset($DEBUG['query']))
									echo "$query\n";

								if (!mysql_query($query))
									$error = seterrorinfo(__LINE__,  $query.": ".mysql_error());
							}

							mysql_free_result($result);
						}
					}
				}
				// /airport

				/* betriebsrichtung.html */
				$betriebsrichtung = curl_download($ch, 'http://apps.fraport.de/betriebsrichtng/betriebsrichtungNEW.html');

				$file = @fopen('data/betriebsrichtung.html', 'w');

				if ($file)
				{
					fwrite($file, $betriebsrichtung);
					fclose($file);
				}

				/* betriebsrichtung.htm for fra-forum */
				$file = @fopen('data/betriebsrichtung.htm', 'w');

				if ($file)
				{
					$style = <<<EOF
<style type="text/css">
ul { padding: 0; }
li { list-style-type: none; }
</style>
EOF;

					fwrite($file, $style);

					$html = explode("\n", $betriebsrichtung);
					$copy = 0;

					foreach ($html as $line)
					{
						if (strstr($line, '<ul id="webticker">'))
						{
							$copy = 1;
							fwrite($file, "$line\n");
						}
						else if (strstr($line, '</ul>'))
						{
							fwrite($file, "$line\n");
							$copy = 0;
						}
						else
						{
							if ($copy)
								fwrite($file, "$line\n");
						}
					}

					fclose($file);
				}

				curl_close($ch);
			}
		}
	}

	mysql_close($hdbc);
}

if ($error)
	echo $error;

if ($warning)
	echo $warning;

if ($error || $warning)
	mail('=?ISO-8859-1?Q?Tobias_K=FChne?= <hausmeister@flederwiesel.com>', $error ? 'error' : 'warning',
		 "$error\n----\n\n$warning", 'From: fra-flights');

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
