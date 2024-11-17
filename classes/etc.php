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

function php_self($https = 0)
{
	if ($https)
	{
		$pageURL = 'https';
	}
	else
	{
		$pageURL = 'http';

		if (isset($_SERVER['HTTPS']))
			if ($_SERVER['HTTPS'] == 'on')
				$pageURL .= 's';
	}

	$pageURL .= '://'.$_SERVER['SERVER_NAME'];

	if ($_SERVER['SERVER_PORT'] != 80 &&
		$_SERVER['SERVER_PORT'] != 443)
		$pageURL .= ":".$_SERVER['SERVER_PORT'];

	$pageURL .= $_SERVER['PHP_SELF'];

	return $pageURL;
}

function htmlget()
{
	$get = '';

	if (isset($_GET))
	{
		foreach ($_GET as $key => $value)
		{
			$get .= 0 == strlen($get) ? '?' : '&amp;';
			$get .= urlencode($key);

			if (strlen($value) > 0)
				$get .= '='.urlencode($value);
		}
	}

	return $get;
}

/******************************************************************************
 * determine which language out of an available set the user prefers most
 *
 * $available_languages
 *   array with language-tag-strings (must be lowercase) that are available
 * $http_accept_language
 *   a HTTP_ACCEPT_LANGUAGE string (read from $_SERVER['HTTP_ACCEPT_LANGUAGE'] if left out)
 ******************************************************************************/

function http_preferred_language($available_languages)
{
	// if $http_accept_language was left out, read it from the HTTP-Header
	$http_accept_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ?
			$_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';

	// standard  for HTTP_ACCEPT_LANGUAGE is defined under
	// http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
	// pattern to find is therefore something like this:
	//    1#( language-range [ ";" "q" "=" qvalue ] )
	// where:
	//    language-range  = ( ( 1*8ALPHA *( "-" 1*8ALPHA ) ) | "*" )
	//    qvalue         = ( "0" [ "." 0*3DIGIT ] )
	//            | ( "1" [ "." 0*3("0") ] )
	preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" .
				   "(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i",
				   $http_accept_language, $match, PREG_SET_ORDER);

	// default language (in case of no matches) is the first in the array
	$bestlang = $available_languages[0];
	$bestqval = 0;

	foreach ($match as $entry)
	{
		// read data from the array of this match
		$langprefix = strtolower($entry[1]);

		if (empty($entry[3]))
		{
			$language = $langprefix;
		}
		else
		{
			$langrange = strtolower($entry[3]);
			$language = $langprefix . "-" . $langrange;
		}

		$qvalue = 1.0;

		if (!empty($entry[5]))
			$qvalue = floatval($entry[5]);

		// find q-maximal language
		if (in_array($language, $available_languages) && $qvalue > $bestqval)
		{
			$bestlang = $language;
			$bestqval = $qvalue;
		}
		else if (in_array($langprefix, $available_languages) && $qvalue * 0.9 > $bestqval)
		{
			// if no direct hit, try the prefix only but decrease
			// q-value by 10% (as http_negotiate_language does)
			$bestlang = $langprefix;
			$bestqval = $qvalue * 0.9;
		}
	}

	return $bestlang;
}

function mktime_c($ddmmyyyy /* dd.mm.YYYY */, $hhmm = '00:00')
{
	if (!preg_match('/([0-9]+).([0-9]+).([0-9]+)/', str_replace(' ', '', $ddmmyyyy), $day))
	{
		$date = -1;
	}
	else
	{
		if (!preg_match('/([0-9]+):([0-9]+)/', str_replace(' ', '', $hhmm), $time))
		{
			$date = -1;
		}
		else
		{
			$date = mktime($time[1], $time[2], 0, $day[2], $day[1], $day[3]);

			if ($date > -1)
			{
				$ddmmyyyy = sprintf('%02d.%02d.%04d %02d:%02d',
									$day[1], $day[2], $day[3], $time[1], $time[2]);

				if (date("d.m.Y H:i", $date) != "$ddmmyyyy")
					$date = -1;
			}
		}
	}

	return $date;
}

function ordinal($number, $lang)
{
	if ('en' == $lang)
	{
		$suffix = ['th','st','nd','rd','th','th','th','th','th','th'];

		if (($number % 100) >= 11 && ($number % 100) <= 13)
			$ordinal = "${number}th";
		else
			$ordinal = "${number}".$suffix[$number % 10];
	}
	else
	{
		$ordinal = "${number}.";
	}

	return $ordinal;
}

define('INP_FORCE', 0x1);
define('INP_POST', 0x2);
define('INP_GET',  0x4);

function Input_SetValue($name, $whence, $debug)
{
	$value = null;

	if (INP_POST & $whence)
	{
		if (isset($_POST[$name]))
			$value = $_POST[$name];
	}

	if (INP_GET & $whence)
	{
		if (!$value)
 			if (isset($_GET[$name]))
 				$value = $_GET[$name];
	}

	if (null == $value)
	{
		if (INP_FORCE & $whence)
			$value = $debug;
	}

	if (defined('DEBUG'))
		if (!$value)
			$value = $debug;

	if ($value)
		echo htmlspecialchars($value);
}

function PDOErrorInfo(PDOException $ex, ?string $fmt = null) : string
{
	$id = "PDO:" . uniqid();

	syslog(LOG_ERR,
		<<<LOG
			PDO:{$id} {$ex->getMessage()}
			{$ex->getTraceAsString()}
			LOG
	);

	return $fmt ? sprintf($fmt, $id) : "$id\n";
}

?>
