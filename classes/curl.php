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

// https://php.net/manual/en/curl.constants.php
// https://github.com/php/php-src/blob/PHP-7.4.16/ext/curl/interface.c

// https://curl.haxx.se/libcurl/c/libcurl-errors.html
// https://github.com/curl/curl/blob/curl-7_58_0/include/curl/curl.h

if (!defined('CURLE_UNSUPPORTED_PROTOCOL'))         define('CURLE_UNSUPPORTED_PROTOCOL',         1);
if (!defined('CURLE_FAILED_INIT'))                  define('CURLE_FAILED_INIT',                  2);
if (!defined('CURLE_URL_MALFORMAT'))                define('CURLE_URL_MALFORMAT',                3);
if (!defined('CURLE_NOT_BUILT_IN'))                 define('CURLE_NOT_BUILT_IN',                 4);
if (!defined('CURLE_COULDNT_RESOLVE_PROXY'))        define('CURLE_COULDNT_RESOLVE_PROXY',        5);
if (!defined('CURLE_COULDNT_RESOLVE_HOST'))         define('CURLE_COULDNT_RESOLVE_HOST',         6);
if (!defined('CURLE_COULDNT_CONNECT'))              define('CURLE_COULDNT_CONNECT',              7);
if (!defined('CURLE_WEIRD_SERVER_REPLY'))           define('CURLE_WEIRD_SERVER_REPLY',           8);
if (!defined('CURLE_REMOTE_ACCESS_DENIED'))         define('CURLE_REMOTE_ACCESS_DENIED',         9);
if (!defined('CURLE_FTP_USER_PASSWORD_INCORRECT'))  define('CURLE_FTP_USER_PASSWORD_INCORRECT', 10);
if (!defined('CURLE_FTP_WEIRD_PASS_REPLY'))         define('CURLE_FTP_WEIRD_PASS_REPLY',        11);
if (!defined('CURLE_FTP_WEIRD_USER_REPLY'))         define('CURLE_FTP_WEIRD_USER_REPLY',        12);
if (!defined('CURLE_FTP_WEIRD_PASV_REPLY'))         define('CURLE_FTP_WEIRD_PASV_REPLY',        13);
if (!defined('CURLE_FTP_WEIRD_227_FORMAT'))         define('CURLE_FTP_WEIRD_227_FORMAT',        14);
if (!defined('CURLE_FTP_CANT_GET_HOST'))            define('CURLE_FTP_CANT_GET_HOST',           15);
if (!defined('CURLE_FTP_CANT_RECONNECT'))           define('CURLE_FTP_CANT_RECONNECT',          16);
if (!defined('CURLE_FTP_COULDNT_SET_TYPE'))         define('CURLE_FTP_COULDNT_SET_TYPE',        17);
if (!defined('CURLE_PARTIAL_FILE'))                 define('CURLE_PARTIAL_FILE',                18);
if (!defined('CURLE_FTP_COULDNT_RETR_FILE'))        define('CURLE_FTP_COULDNT_RETR_FILE',       19);
if (!defined('CURLE_FTP_WRITE_ERROR'))              define('CURLE_FTP_WRITE_ERROR',             20);
if (!defined('CURLE_QUOTE_ERROR'))                  define('CURLE_QUOTE_ERROR',                 21);
if (!defined('CURLE_HTTP_RETURNED_ERROR'))          define('CURLE_HTTP_RETURNED_ERROR',         22);
if (!defined('CURLE_WRITE_ERROR'))                  define('CURLE_WRITE_ERROR',                 23);
if (!defined('CURLE_MALFORMAT_USER'))               define('CURLE_MALFORMAT_USER',              24);
if (!defined('CURLE_UPLOAD_FAILED'))                define('CURLE_UPLOAD_FAILED',               25);
if (!defined('CURLE_READ_ERROR'))                   define('CURLE_READ_ERROR',                  26);
if (!defined('CURLE_OUT_OF_MEMORY'))                define('CURLE_OUT_OF_MEMORY',               27);
if (!defined('CURLE_OPERATION_TIMEDOUT'))           define('CURLE_OPERATION_TIMEDOUT',          28);
if (!defined('CURLE_FTP_COULDNT_SET_ASCII'))        define('CURLE_FTP_COULDNT_SET_ASCII',       29);
if (!defined('CURLE_FTP_PORT_FAILED'))              define('CURLE_FTP_PORT_FAILED',             30);
if (!defined('CURLE_FTP_COULDNT_USE_REST'))         define('CURLE_FTP_COULDNT_USE_REST',        31);
if (!defined('CURLE_FTP_COULDNT_GET_SIZE'))         define('CURLE_FTP_COULDNT_GET_SIZE',        32);
if (!defined('CURLE_RANGE_ERROR'))                  define('CURLE_RANGE_ERROR',                 33);
if (!defined('CURLE_HTTP_POST_ERROR'))              define('CURLE_HTTP_POST_ERROR',             34);
if (!defined('CURLE_SSL_CONNECT_ERROR'))            define('CURLE_SSL_CONNECT_ERROR',           35);
if (!defined('CURLE_BAD_DOWNLOAD_RESUME'))          define('CURLE_BAD_DOWNLOAD_RESUME',         36);
if (!defined('CURLE_FILE_COULDNT_READ_FILE'))       define('CURLE_FILE_COULDNT_READ_FILE',      37);
if (!defined('CURLE_LDAP_CANNOT_BIND'))             define('CURLE_LDAP_CANNOT_BIND',            38);
if (!defined('CURLE_LDAP_SEARCH_FAILED'))           define('CURLE_LDAP_SEARCH_FAILED',          39);
if (!defined('CURLE_LIBRARY_NOT_FOUND'))            define('CURLE_LIBRARY_NOT_FOUND',           40);
if (!defined('CURLE_FUNCTION_NOT_FOUND'))           define('CURLE_FUNCTION_NOT_FOUND',          41);
if (!defined('CURLE_ABORTED_BY_CALLBACK'))          define('CURLE_ABORTED_BY_CALLBACK',         42);
if (!defined('CURLE_BAD_FUNCTION_ARGUMENT'))        define('CURLE_BAD_FUNCTION_ARGUMENT',       43);
if (!defined('CURLE_BAD_CALLING_ORDER'))            define('CURLE_BAD_CALLING_ORDER',           44);
if (!defined('CURLE_INTERFACE_FAILED'))             define('CURLE_INTERFACE_FAILED',            45);
if (!defined('CURLE_BAD_PASSWORD_ENTERED'))         define('CURLE_BAD_PASSWORD_ENTERED',        46);
if (!defined('CURLE_TOO_MANY_REDIRECTS'))           define('CURLE_TOO_MANY_REDIRECTS',          47);
if (!defined('CURLE_UNKNOWN_OPTION'))               define('CURLE_UNKNOWN_OPTION',              48);
if (!defined('CURLE_TELNET_OPTION_SYNTAX'))         define('CURLE_TELNET_OPTION_SYNTAX',        49);
/* 50 - NOT USED */
if (!defined('CURLE_PEER_FAILED_VERIFICATION'))     define('CURLE_PEER_FAILED_VERIFICATION',    51);
if (!defined('CURLE_GOT_NOTHING'))                  define('CURLE_GOT_NOTHING',                 52);
if (!defined('CURLE_SSL_ENGINE_NOTFOUND'))          define('CURLE_SSL_ENGINE_NOTFOUND',         53);
if (!defined('CURLE_SSL_ENGINE_SETFAILED'))         define('CURLE_SSL_ENGINE_SETFAILED',        54);
if (!defined('CURLE_SEND_ERROR'))                   define('CURLE_SEND_ERROR',                  55);
if (!defined('CURLE_RECV_ERROR'))                   define('CURLE_RECV_ERROR',                  56);
if (!defined('CURLE_SHARE_IN_USE'))                 define('CURLE_SHARE_IN_USE',                57);
if (!defined('CURLE_SSL_CERTPROBLEM'))              define('CURLE_SSL_CERTPROBLEM',             58);
if (!defined('CURLE_SSL_CIPHER'))                   define('CURLE_SSL_CIPHER',                  59);
if (!defined('CURLE_SSL_CACERT'))                   define('CURLE_SSL_CACERT',                  60);
if (!defined('CURLE_BAD_CONTENT_ENCODING'))         define('CURLE_BAD_CONTENT_ENCODING',        61);
if (!defined('CURLE_LDAP_INVALID_URL'))             define('CURLE_LDAP_INVALID_URL',            62);
if (!defined('CURLE_FILESIZE_EXCEEDED'))            define('CURLE_FILESIZE_EXCEEDED',           63);
if (!defined('CURLE_USE_SSL_FAILED'))               define('CURLE_USE_SSL_FAILED',              64);
if (!defined('CURLE_SEND_FAIL_REWIND'))             define('CURLE_SEND_FAIL_REWIND',            65);
if (!defined('CURLE_SSL_ENGINE_INITFAILED'))        define('CURLE_SSL_ENGINE_INITFAILED',       66);
if (!defined('CURLE_LOGIN_DENIED'))                 define('CURLE_LOGIN_DENIED',                67);
if (!defined('CURLE_TFTP_NOTFOUND'))                define('CURLE_TFTP_NOTFOUND',               68);
if (!defined('CURLE_TFTP_PERM'))                    define('CURLE_TFTP_PERM',                   69);
if (!defined('CURLE_REMOTE_DISK_FULL'))             define('CURLE_REMOTE_DISK_FULL',            70);
if (!defined('CURLE_TFTP_ILLEGAL'))                 define('CURLE_TFTP_ILLEGAL',                71);
if (!defined('CURLE_TFTP_UNKNOWNID'))               define('CURLE_TFTP_UNKNOWNID',              72);
if (!defined('CURLE_REMOTE_FILE_EXISTS'))           define('CURLE_REMOTE_FILE_EXISTS',          73);
if (!defined('CURLE_TFTP_NOSUCHUSER'))              define('CURLE_TFTP_NOSUCHUSER',             74);
if (!defined('CURLE_CONV_FAILED'))                  define('CURLE_CONV_FAILED',                 75);
if (!defined('CURLE_CONV_REQD'))                    define('CURLE_CONV_REQD',                   76);
if (!defined('CURLE_SSL_CACERT_BADFILE'))           define('CURLE_SSL_CACERT_BADFILE',          77);
if (!defined('CURLE_REMOTE_FILE_NOT_FOUND'))        define('CURLE_REMOTE_FILE_NOT_FOUND',       78);
if (!defined('CURLE_SSH'))                          define('CURLE_SSH',                         79);
if (!defined('CURLE_SSL_SHUTDOWN_FAILED'))          define('CURLE_SSL_SHUTDOWN_FAILED',         80);
if (!defined('CURLE_AGAIN'))                        define('CURLE_AGAIN',                       81);
if (!defined('CURLE_SSL_CRL_BADFILE'))              define('CURLE_SSL_CRL_BADFILE',             82);
if (!defined('CURLE_SSL_ISSUER_ERROR'))             define('CURLE_SSL_ISSUER_ERROR',            83);
if (!defined('CURLE_FTP_PRET_FAILED'))              define('CURLE_FTP_PRET_FAILED',             84);
if (!defined('CURLE_RTSP_CSEQ_ERROR'))              define('CURLE_RTSP_CSEQ_ERROR',             85);
if (!defined('CURLE_RTSP_SESSION_ERROR'))           define('CURLE_RTSP_SESSION_ERROR',          86);
if (!defined('CURLE_FTP_BAD_FILE_LIST'))            define('CURLE_FTP_BAD_FILE_LIST',           87);
if (!defined('CURLE_CHUNK_FAILED'))                 define('CURLE_CHUNK_FAILED',                88);
if (!defined('CURLE_NO_CONNECTION_AVAILABLE'))      define('CURLE_NO_CONNECTION_AVAILABLE',     89);
if (!defined('CURLE_SSL_PINNEDPUBKEYNOTMATCH'))     define('CURLE_SSL_PINNEDPUBKEYNOTMATCH',    90);
if (!defined('CURLE_SSL_INVALIDCERTSTATUS'))        define('CURLE_SSL_INVALIDCERTSTATUS',       91);
if (!defined('CURLE_HTTP2_STREAM'))                 define('CURLE_HTTP2_STREAM',                92);

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
			curl_setopt($this->me, CURLOPT_REFERER, "https://fra-flugplan.de");

			// User agent
			//curl_setopt($this->me, CURLOPT_USERAGENT, "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.9.2.28) Gecko/20120306 Firefox/3.6.28 ( .NET CLR 3.5.30729; .NET4.0E)");

			// Include header in result? (0 = yes, 1 = no)
			curl_setopt($this->me, CURLOPT_HEADER, 0);

			// Upon "301 Moved Permanently", follow the redirection
			// This is necessary since local url '.../airportcity' is a directory,
			// but url would have needed a trailing backslash then...
			curl_setopt($this->me, CURLOPT_FOLLOWLOCATION, 1);

			// When debugging, localhost is using a certificate attributing back
			// a custom CA, which we need to make known to curl.
			// Put any root certificates here (not just the custom ones), otherwise
			// with a missing ca-certificates.crt file, curl uses its default.
			$certlocs = [
				getenv("FRA_FLUGPLAN_CA_CERTS"),
				$_SERVER["FRA_FLUGPLAN_CA_CERTS"] ?? null,
				"{$_SERVER['DOCUMENT_ROOT']}/etc/ssl/certs/ca-certificates.crt"
			];

			foreach ($certlocs as $cacerts)
			{
				if ($cacerts)
				{
					if (file_exists($cacerts))
					{
						curl_setopt($this->me, CURLOPT_CAINFO, $cacerts);
						break;
					}
				}
			}

			// Now can securely connect with proper verification
			curl_setopt($this->me, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($this->me, CURLOPT_SSL_VERIFYHOST, 2);

			// Should cURL return or print out the data? (true = return, false = print)
			curl_setopt($this->me, CURLOPT_RETURNTRANSFER, true);

			curl_setopt($this->me, CURLOPT_COOKIESESSION, true);	// start new cookie "session"
			curl_setopt($this->me, CURLOPT_FRESH_CONNECT, false);

			// Timeout in seconds
			curl_setopt($this->me, CURLOPT_CONNECTTIMEOUT, 10);

			// Process options from .curlrc
			// Path to this file can be set in apache server env
			// https://curl.haxx.se/docs/manpage.html
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

	function get_info(?string $what = null)
	{
		$info = curl_getinfo($this->me);

		if ($what == null)
		{
			return $info;
		}
		else
		{
			if (array_key_exists($what, $info))
				return $info[$what];
			else
				return false;
		}
	}
};

?>
