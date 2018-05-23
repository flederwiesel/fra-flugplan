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
		$_SESSION['lang'] = $_GET['lang'];
}
else if (isset($_POST['lang']))
{
	if (strlen($_POST['lang']))
		$_SESSION['lang'] = $_POST['lang'];
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

/******************************************************************************
 * initialise variables
 ******************************************************************************/

$request = (isset($_GET['request']) ? $_GET['request'] : "");

if (isset($_SERVER['HTTP_REFERER']))
	$referrer = $_SERVER['HTTP_REFERER'];
else
	$referrer = NULL;

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
<link rel="apple-touch-icon" href="/apple-touch-icon.png"/>
<link type="image/gif" rel="icon" href="/favicon.gif">
<?php if ($mobile && !$tablet) { ?>
<link rel="stylesheet" type="text/css" href="css/mobile.css">
<?php } else { ?>
<link rel="stylesheet" type="text/css" media="screen, projection, handheld, print" href="css/desktop.css">
<?php } ?>
<style>
#text div {
	margin-top: 1em;
}
#text a {
	font-style: italic;
	color: #00007f;
}
</style>
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
				<div id="nav">
					<ul class="menu left">
						<li><a href="/fra-schedule"><?php echo $lang['home']; ?></a>
<?php if ($referrer) { ?>
						<li class="sep"><a href="">Back</a></li>
<?php } ?>
					</ul>
					<ul class="menu right">
						<li>&nbsp;</li>
					</ul>
				</div>
			</div>
			<div id="content">
				<img style="float:right" align="bottom" src="img/STOP-traffic.png">
				<div id="text">
					<h1 style="display: inline">
<?php
					if (isset($_GET['http_status']))
						$status = $_GET['http_status'];
					else
						$status = 500;

					echo "$status ";

					switch ($status)
					{
					case 403:
						$heading = "Takeoff rejected.";
						break;

					case 404:
						$heading = "Autopilot failure.";
						break;

					case 505:
						$heading = "Engine shutdown.";
						break;

					default:
						$status = 500;
						$heading = "Engine shutdown.";
						break;
					}

					echo $heading;
?>
					</h1>
<?php
					$emil = "content/emil.php";
					$emil .= "?subject=".urlencode("$status $heading");
					$emil .= "&body=".urlencode("$request");

					if ($referrer)
						$emil .= urlencode(" from $referrer");

					$img = "content/mkpng.php?font=verdana&size=10&bg=white&fg=%2300007f&res=ADMIN_EMAIL";
					$email = "<a href='$emil'><img alt='email' src='$img' style='vertical-align: bottom;'></a>";

					switch ($status)
					{
					case 403:

						echo "<div>Access to the request page <a>$request</a> has been denied. ";
						echo "This might happen due to access restrictions or an errorneous request.</div>";
						echo "<div>If you think this is an error, please contact the author at $email</div>";

						break;

					case 404:

						echo "<div>The requested page <a>$request</a> could not be found on the server.</div>";

						if ($referrer)
							echo "<div>Please inform the author at $email</div>";
						else
							echo "<div>If you entered the URL manually please check your spelling and try again.</div>";

						break;

					case 500:
						echo "<div>The requested page <a>$request</a> cannot be provided due to a server error.<div>";
						break;

					}
?>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
