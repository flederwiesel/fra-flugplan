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

	if ($_SERVER['SERVER_PORT'] != 80)
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
			$get .= 0 == strlen($get) ? '?' : '&';
			$get .= urlencode($key);

			if (strlen($value) > 0)
				$get .= '='.urlencode($value);
		}
	}

	return $get;
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

?>
