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

mb_internal_encoding("UTF-8");

if (is_file("classes/etc.php"))
	require_once "classes/etc.php";

if (is_file("fra-flugplan/classes/etc.php"))
	require_once "fra-flugplan/classes/etc.php";

// Set session language from $_SESSION or $_COOKIE
session_start();

$lang = null;

if (isset($_GET["lang"]))
	if (in_array($_GET["lang"], ["de", "en"]))
		$lang = $_GET["lang"];

if (!$lang)
	if (isset($_SESSION["lang"]))
		if (in_array($_SESSION["lang"], ["de", "en"]))
			$lang = $_SESSION["lang"];

if (!$lang)
	$lang = http_preferred_language(["de", "en"]);

$_SESSION["lang"] = $lang;

if ($lang == "de")
	$subtitle = "Es ist ein Fehler aufgetreten.";
else
	$subtitle = "An error occured.";

// Get error/message
if (isset($_GET["status"]))
	$status = $_GET["status"];
elseif (isset($_SERVER["REDIRECT_STATUS"]))
	$status = $_SERVER["REDIRECT_STATUS"];
else
	$status = 500;

$messages = [
	400 => "Bad Request",
	401 => "Unauthorized",
	402 => "Payment Required",
	403 => "Forbidden",
	404 => "Not Found",
	405 => "Method Not Allowed",
	406 => "Not Acceptable",
	407 => "Proxy Authentication Required",
	408 => "Request Timeout",
	409 => "Conflict",
	410 => "Gone",
	411 => "Length Required",
	412 => "Precondition Failed",
	413 => "Payload Too Large",
	414 => "URI Too Long",
	415 => "Unsupported Media Type",
	416 => "Range Not Satisfiable",
	417 => "Expectation Failed",
	418 => "I'm a teapot",
	419 => "Page Expired",	// Laravel: CSRF token mismatch, Session expired,
							// User authentication timeout
	421 => "Misdirected Request",
	422 => "Unprocessable Content",
	423 => "Locked",
	424 => "Failed Dependency",
	425 => "Too Early",
	426 => "Upgrade Required",
	428 => "Precondition Required",
	429 => "Too Many Requests",
	431 => "Request Header Fields Too Large",
	451 => "Unavailable For Legal Reasons",
	500 => "Internal Server Error",
	501 => "Not Implemented",
	502 => "Bad Gateway",
	503 => "Service Unavailable",
	504 => "Gateway Timeout",
	505 => "HTTP Version Not Supported",
	506 => "Variant Also Negotiates",
	507 => "Insufficient Storage",
	508 => "Loop Detected",
	508 => "Resource Limit Is Reached",
	510 => "Not Extended",
	511 => "Network Authentication Required",
];

if (array_key_exists($status, $messages))
{
	$message = $messages[$status];
}
else
{
	$status = 500;
	$message = "Internal Server Error";
}

http_response_code($status);

header("Content-Type: text/html; charset=UTF-8");
header("Content-Language: {$lang}");
header("X-Error-Served-By: {$_SERVER['SERVER_SOFTWARE']}");
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="author" content="flederwiesel / Tobias Kühne">
	<meta name="language" content="<?= $lang; ?>">
	<title><?= "{$status} {$message}"; ?> &mdash; FRA Flugplan</title>
	<link rel="stylesheet" type="text/css" media="screen, print" href="error.css">
</head>
<body>
	<div class="container" id="container">
		<h1 id="title">FRA Flugplan</h1>
		<h2 id="subtitle"><?php echo $subtitle; ?></h2>
		<img src="img/errors/<?php echo $status ?>.gif" alt="<?php echo $status; ?>">
		<div id="message"><?php echo "$message" ?></div>
	</div>
</body>
</html>
