<?php

class CspNonce
{
	private static $value;

	private static function init()
	{
		self::$value = base64_encode(
			openssl_pbkdf2(
				openssl_random_pseudo_bytes(32), microtime(), 32, 32, "sha256"
			)
		);

		if (self::$value === null)
			throw new RuntimeException("", 500);
	}

	public static function get()
	{
		return self::$value;
	}
}

(static function () {
	static::init();
})->bindTo(null, CspNonce::class)();

?>
