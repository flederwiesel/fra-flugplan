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

if ('localhost' == $_SERVER['SERVER_NAME'])
	error_reporting(E_ALL | E_NOTICE);
else
	error_reporting(0);

mb_internal_encoding('UTF-8');

require_once '.config';
require_once 'classes/etc.php';

$jquery = 'jquery-1.10.1.min.js';
$jqueryui = 'jquery-ui-1.10.3';

if (defined('DEBUG'))
	$jquerymin = 'minified/';
else
	$jquerymin = '';

function get($get=null)
{
	if (!$_GET)
	{
		$strget = '?'.$get;
	}
	else
	{
		if ($get)
			parse_str($get, $values);

		$strget = '';

		foreach ($_GET as $key => $value)
		{
			$strget .= 0 == strlen($strget) ? '?' : '&';

			if (isset($values[$key]))
			{
				$strget .= urlencode($key);

				if (strlen($values[$key]) > 0)
					$strget .= '='.urlencode($values[$key]);

				unset($values[$key]);
			}
			else
			{
				$strget .= urlencode($key);

				if (strlen($value) > 0)
					$strget .= '='.urlencode($value);
			}
		}

		foreach ($values as $key => $value)
		{
			$strget .= 0 == strlen($strget) ? '?' : '&';

			$strget .= urlencode($key);

			if (strlen($value) > 0)
				$strget .= '='.urlencode($value);
		}
	}

	return $strget;
}

/******************************************************************************
 * Equal goes it loose
 ******************************************************************************/

session_start();

/******************************************************************************
 * detect/set language and initialise strings
 ******************************************************************************/

/* Set session language from $_POST or $_COOKIE */
if (isset($_GET['lang']))
{
	if (strlen($_GET['lang']))
	{
		setcookie('lang', $_GET['lang'], time() + COOKIE_LIFETIME);
		$_SESSION['lang'] = $_GET['lang'];
	}
}
else if (isset($_POST['lang']))
{
	if (strlen($_POST['lang']))
	{
		setcookie('lang', $_POST['lang'], time() + COOKIE_LIFETIME);
		$_SESSION['lang'] = $_POST['lang'];
	}
}
else
{
	if (!isset($_SESSION['lang']))
	{
		if (isset($_COOKIE['lang']))
			$_SESSION['lang'] = $_COOKIE['lang'];
	}
}

if (!isset($_SESSION['lang']))
	$_SESSION['lang'] = http_preferred_language(array('en', 'de'));
else if (0 == strlen($_SESSION['lang']))
	$_SESSION['lang'] = http_preferred_language(array('en', 'de'));

/******************************************************************************
 * detect device type
 ******************************************************************************/

require_once 'classes/Mobile_Detect.php';

$device = new Mobile_Detect();

if (!$device)
{
	$mobile = false;
}
else
{
	/* Treat tablets as desktop */
	$mobile = $device->isMobile() && !$device->isTablet();
	$tablet = $device->isTablet();
	unset($device);
}

$dir = NULL;
$rev = NULL;

/******************************************************************************
 * header
 ******************************************************************************/

// always modified
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');

// HTTP/1.1
header('Cache-control: private'); // IE 6 FIX
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
// HTTP/1.0
header('Pragma: no-cache');

header('Content-Type: text/html; charset=UTF-8');
header('Content-Language: '.$_SESSION['lang']);

$file = 'content/language/'.$_SESSION['lang'].'.php';

if (file_exists($file))
	include "$file";
else
	include "content/language/en.php";

setcookie('lang', $_SESSION['lang'], time() + COOKIE_LIFETIME);

/******************************************************************************
 * initialise variables
 ******************************************************************************/

$hdbc = NULL;

/*<html>*******************************************************************/
?>
<?php if ($mobile && !$tablet) { ?>
<!DOCTYPE HTML SYSTEM "html40-mobile.dtd"
	"http://www.w3.org/TR/NOTE-html40-mobile/DTD/html40-mobile.dtd">
<?php } else {  ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<?php } ?>
<html>
<head>
<title><?php echo PROJECT; ?> &ndash; <?php echo ORGANISATION; ?></title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta name="language" content="<?php echo $_SESSION['lang']; ?>">
<meta name="author" content="Tobias Kühne">
<meta name="description" lang="en" content="Spotter schedule for Frankfurt airport (FRA/EDDF) including aircraft registrations">
<meta name="description" lang="en" content="Spotter-Flugplan für Frankfurt (FRA/EDDF) einschließlich Flugzeugkennungen">
<meta name="keywords" content="fra, eddf, frankfurt, fraspotter, fraspotting, planespotting, fra-flights, fra-schedule, flederwiesel">
<meta name="keywords" lang="en" content="airport, aircraft, aviation, spotter, spotting, schedule, flights, flight schedule, flight plan">
<meta name="keywords" lang="de" content="Flughafen, Flugzeug, Luftfahrt, Spotter, Spotting, Flugzeugfotografie, Flüge, Flugplan">
<meta name="robots" content="index, nofollow">
<meta name="generator" content="http://www.ultraedit.com/">
<?php if ($mobile && !$tablet) { ?>
<meta name="viewport" content="width=device-width; initial-scale=1.0;"/>
<?php } ?>
<link rel="apple-touch-icon" href="apple-touch-icon.png"/>
<link type="image/gif" rel="icon" href="favicon.gif">
<?php if ($mobile && !$tablet) { ?>
<link rel="stylesheet" type="text/css" href="css/mobile.css">
<?php } else { ?>
<link rel="stylesheet" type="text/css" media="screen, projection, handheld, print" href="css/desktop.css">
<?php } ?>
</head>
<body>
	<noscript>
		<div class="noscript"><?php echo $lang['noscript']; ?></div>
	</noscript>
<?php if (defined('DEBUG')) { ?>
	<div id="debug">
		<h1>Debug version.</h1>
	</div>
<?php } ?>
	<div id="body">
		<div class="box left">
			<div>
				<div id="head">
					<h1 class="nobr"><?php echo ORGANISATION; ?></h1>
					<h3>
<?php
						echo "$lang[liveschedule]";
?></h3>
				</div>
				<div id="nav"><?php require_once 'nav.php'; ?>
				</div>
			</div>
			<div id="content">
				<img style="float:right" align="bottom" src="img/STOP-traffic.png">
				<div>
					<h1 style="display: inline">404 Autopilot failure.</h1>
					<div style="margin-top: 1em">The requested page
						<span style="font-style: italic; color: #00007f">
							<?php echo "http".(isset($_SERVER['HTTPS']) ? "s" : "")."://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>
						</span> could not be found on the server.
					</div>
<?php
		if (isset($_SERVER['HTTP_REFERER']))
		{
?>
					<div style="margin-top: 1em">Please <a href="content/emil.php?subject=404%20Autopilot%20failure&body=<?php
						$url = "http".(isset($_SERVER['HTTPS']) ? "s" : "")."://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
						$ref = "$_SERVER[HTTP_REFERER]";
						echo urlencode("$url not found. Referrer: $ref"); ?>"> inform the author at
						<img alt="email" src="content/mkpng.php?font=verdana&size=10&text=ADMIN_EMAIL" style="vertical-align: bottom;"></a>.
					</div>
<?php
		}
		else
		{
?>
				<div>If you entered the URL manually please check your spelling and try again.</div>
<?php
		}
?>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
