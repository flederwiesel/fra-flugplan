<?php

// Content Security Policy

// https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy
// https://content-security-policy.com/#source_list
// https://web.dev/articles/strict-csp
// https://csp-evaluator.withgoogle.com/

// Restrict all resource origins to ourselves, add overrides to the `Csp` constructor.

class Csp
{
	private static $nonce;

	private static function init()
	{
		self::$nonce = base64_encode(
			openssl_pbkdf2(
				openssl_random_pseudo_bytes(32), microtime(), 32, 32, "sha256"
			)
		);

		if (self::$nonce === null)
			throw new RuntimeException("", 500);
	}

	public static function nonce()
	{
		return self::$nonce;
	}

	const BLOCK = 'Content-Security-Policy';
	const REPORT_ONLY = 'Content-Security-Policy-Report-Only';

	private $policies = [
		self::BLOCK => [],
		self::REPORT_ONLY => [],
	];

	// $policy = { BLOCK, REPORT_ONLY }
	// $addRules = [ name => value, ... ],
	//   where 'nonce' will be replace with the actual value
	// For keys with null values, the default will be deleted.
	// For key not having a value (e.g. `upgrade-insecure-requests`) use ''.
	public function __construct($policy, $addRules = [])
	{
		$RULES = [
			// Defines the default policy for fetching resources such as
			// JavaScript, Images, CSS, Fonts, AJAX requests, Frames, HTML5 Media.
			'default-src' => "'self'",
			// Defines a set of allowed URLs which can be used
			// in the src attribute of a HTML base tag.
			'base-uri' => "'self'",
			// Defines valid sources of JavaScript.
			'script-src' => "'self'",
			// Defines valid sources of stylesheets or CSS.
			'style-src' => "'self'",
			// Specifies valid sources for inline styles applied to individual DOM elements.
			'style-src-attr' => "'none'",
			// Defines valid sources of font resources (loaded via @font-face).
			'font-src' => "'self'",
			// Defines valid sources of images.
			'img-src' => "'self' data:",
			// Defines valid sources of audio and video, eg HTML5 <audio>, <video> elements.
			'media-src' => "'none'",
			// Restricts the URLs that application manifests can be loaded.
			'manifest-src' => "'none'",
			// Defines valid sources of plugins, eg <object>, <embed> or <applet>.
			'object-src' => "'none'",
			// Restricts the URLs which may be loaded as a
			// Worker, SharedWorker or ServiceWorker.
			'worker-src' => "'none'",
			// Applies to XMLHttpRequest (AJAX), WebSocket, fetch(), <a ping> or EventSource.
			// If not allowed the browser emulates a 400 HTTP status code.
			'connect-src' => "'none'",
			// Defines valid sources that can be used as an HTML <form> action.
			'form-action' => "'self'",
			// Defines valid sources for embedding the resource using
			// <frame> <iframe> <object> <embed> <applet>. Setting this directive
			// to 'none' should be roughly equivalent to X-Frame-Options: DENY
			'frame-ancestors' => "'none'",
			// Defines valid sources for web workers and nested browsing contexts
			// loaded using elements such as <frame> and <iframe>
			'child-src' => "'self'",
			// Automatically Converts urls from http to https for
			// links, images, javascript, css, etc.
			'upgrade-insecure-requests' => '',
		];

		$nonce = self::$nonce;

		foreach ($RULES as $key => $value)
		{
			if (key_exists($key, $addRules))
				$value = $addRules[$key];

			if ($value !== null)
				$this->policies[$policy][$key] =
					str_replace("'nonce'", "'nonce-{$nonce}'", $value);
		}
	}

	public function headers($addRules = [])
	{
		foreach ($this->policies as $header => $policy)
		{
			if (!$policy)
				continue;

			$uri = null;
			$rules = '';

			foreach ($policy as $key => $value)
				$rules .= "{$key} {$value};";

			header("{$header}: {$rules}");
		}
	}
}

(static function () {
	static::init();
})->bindTo(null, Csp::class)();

?>
