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

// http://php.net/manual/en/curl.constants.php
// https://curl.haxx.se/libcurl/c/libcurl-errors.html
if (!defined('CURLE_OPERATION_TIMEDOUT'))
	define('CURLE_OPERATION_TIMEDOUT', CURLE_OPERATION_TIMEOUTED);

class curl
{
	private $me = 0;
	private $errno = 0;

	function __construct()
	{
		// is cURL installed yet?
		if (!function_exists('curl_init'))
		{
			throw new Exception('cURL is not installed!');
		}
		else
		{
			// OK cool - then let's create a new cURL resource handle
			$this->me = curl_init();

			// Now set some options (most are optional)
			// http://en.php.net/curl_setopt

			// Set a referer
			curl_setopt($this->me, CURLOPT_REFERER, "https://www.fra-flugplan.de");

			// User agent
			//curl_setopt($this->me, CURLOPT_USERAGENT, "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.9.2.28) Gecko/20120306 Firefox/3.6.28 ( .NET CLR 3.5.30729; .NET4.0E)");

			// Include header in result? (0 = yes, 1 = no)
			curl_setopt($this->me, CURLOPT_HEADER, 0);

			// Upon "301 Moved Permanently", follow the redirection
			// This is necessary since local url '.../airportcity' is a directory,
			// but url would have needed a trailing backslash then...
			curl_setopt($this->me, CURLOPT_FOLLOWLOCATION, 1);

			// As we allow redirection, we might be redirected to a secure location.
			// So better, we have the certificate at hand...
			curl_setopt($this->me, CURLOPT_CAINFO, "$_SERVER[DOCUMENT_ROOT]/etc/ssl/fraport.pem");
			// Now can securely connect with proper verification
			curl_setopt($this->me, CURLOPT_SSL_VERIFYPEER, TRUE);
			curl_setopt($this->me, CURLOPT_SSL_VERIFYHOST, 2);

			// Should cURL return or print out the data? (true = return, false = print)
			curl_setopt($this->me, CURLOPT_RETURNTRANSFER, TRUE);

			curl_setopt($this->me, CURLOPT_COOKIESESSION, TRUE);	// start new cookie "session"
			curl_setopt($this->me, CURLOPT_FRESH_CONNECT, FALSE);

			// Timeout in seconds
			curl_setopt($this->me, CURLOPT_CONNECTTIMEOUT, 10);

			// Need to use a proxy?
			$curlrc = getenv('curlrc');

			if ($curlrc)
			{
				if (file_exists($curlrc))
				{
					$curlrc = parse_ini_file($curlrc);

					if ($curlrc)
					{
						// noproxy = localhost
						if (isset($curlrc['noproxy']) && defined('CURLOPT_NOPROXY'))
							curl_setopt($this->me, CURLOPT_NOPROXY, trim($curlrc['noproxy']));

						// proxy = proxy.domain.tld:3128
						if (isset($curlrc['proxy']))
							curl_setopt($this->me, CURLOPT_PROXY, trim($curlrc['proxy']));

						// proxy-user = user:********
						if (isset($curlrc['proxy-user']))
							curl_setopt($this->me, CURLOPT_PROXYUSERPWD, trim($curlrc['proxy-user']));

						unset($curlrc);
					}
				}
			}
		}

		return $this;
	}

	function __destruct()
	{
		curl_close($this->me);
		$this->me = 0;
	}

	function errno()
	{
		return $this->errno ? $this->errno : curl_errno($this->me);
	}

	function exec($url, &$result, $timeout = 0)
	{
		$this->errno = 0;

		curl_setopt($this->me, CURLOPT_URL, $url);
		curl_setopt($this->me, CURLOPT_TIMEOUT, $timeout);

		$result = curl_exec($this->me);

		if (false === $result)
		{
			if (curl_errno($this->me))
			{
				$this->errno = curl_errno($this->me);
			}
			else
			{
				$error = error_get_last();
				$this->errno = -$error['type'];
			}
		}
		else
		{
			$error = curl_getinfo($this->me, CURLINFO_HTTP_CODE);

			if (!(200 == $error))
				$this->errno = $error;
		}

		return $this->errno;
	}
};

?>
