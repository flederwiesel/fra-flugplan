<?php

require_once "CsrfException.php";
require_once "CsrfExceptionInvalidToken.php";

class CsrfToken
{
	private static $value;

	private static function init()
	{
		$sid = session_id();

		if ($sid === null || $sid === "")
			session_start();

		if (isset($_SESSION["CSRFToken"]))
		{
			self::$value = $_SESSION["CSRFToken"];
		}
		else
		{
			self::$value = base64_encode(
				openssl_pbkdf2(session_id(), microtime(), 64, 1024, "sha256")
			);

			if (self::$value === null)
				throw new CsrfException();

			$_SESSION["CSRFToken"] = self::$value;
		}
	}

	public static function get()
	{
		return self::$value;
	}

	public static function validate()
	{
		if ($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			if (!isset($_SESSION["CSRFToken"]))
			{
				throw new RuntimeException("No CSRF token.");
			}
			elseif (empty($_SESSION["CSRFToken"]))
			{
				throw new RuntimeException("Empty CSRF token.");
			}
			elseif (!isset($_POST["CSRFToken"]))
			{
				throw new CsrfExceptionInvalidToken("No CSRF reponse token.");
			}
			else
			{
				if ($_POST["CSRFToken"] !== self::$value)
					throw new CsrfExceptionInvalidToken("Invalid CSRF reponse token.");
			}
		}
	}
}

(static function ()
{
	static::init();
})->bindTo(null, CsrfToken::class)();

?>
