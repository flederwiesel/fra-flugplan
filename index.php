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

require_once '.config';
require_once 'classes/etc.php';

if (defined('DEBUG'))
	error_reporting(E_ALL | E_NOTICE);
else
	error_reporting(0);

mb_internal_encoding('UTF-8');

$jqueryui = 'jquery-ui-1.10.3';
$jquery = 'jquery-1.10.1';

if (defined('DEBUG'))
{
	$jquerymin = '';
	$jqueryminified = '';
}
else
{
	$jquerymin = '.min';
	$jqueryminified = 'minified/';
}

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
			$strget .= 0 == strlen($strget) ? '?' : '&amp;';

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
			$strget .= 0 == strlen($strget) ? '?' : '&amp;';

			$strget .= urlencode($key);

			if (strlen($value) > 0)
				$strget .= '='.urlencode($value);
		}
	}

	return $strget;
}

function content()
{
	if (isset($_GET['req']))
		return "forms/$_GET[req].php";
	else if (isset($_GET['page']))
		return "content/$_GET[page].php";
	else if (isset($_GET['admin']))
		return "content/admin.php";
	else
		return 'content/index.php';
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

if ('de' == $_SESSION['lang'])
	$lang = setlocale(LC_TIME, 'deu', 'deu_deu');
else
	$lang = setlocale(LC_TIME, 'eng', 'english-uk', 'uk', 'enu', 'english-us', 'us', 'english', 'C');

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

if (isset($_GET))
{
	if (isset($_GET['departure']))
		$_SESSION['dir'] = 'departure';

	if (isset($_GET['arrival']))
		$_SESSION['dir'] = 'arrival';
}

if (!isset($_SESSION['dir']))
	$_SESSION['dir'] = 'arrival';

$dir = $_SESSION['dir'];
$rev = 'arrival' == $dir ? 'departure' : 'arrival';

/******************************************************************************
 * set profile cookies
 ******************************************************************************/

if (isset($_GET['req']))
{
	if ('profile'  == $_GET['req'] ||
		'changepw' == $_GET['req'])
	{
		if (isset($_GET['dispinterval']))
		{
			$item = 'dispinterval';
		}
		else
		{
			if (isset($_GET['notifinterval']))
			{
				$item = 'notifinterval';
			}
			else
			{
				if (isset($_GET['changepw']))
				{
					$item = 'changepw';
				}
				else
				{
					if (isset($_COOKIE['profile-item']))
						$item = $_COOKIE['profile-item'];
					else
						$item = 'dispinterval';
				}
			}
		}

		setcookie('profile-item', $item, time() + COOKIE_LIFETIME);
	}
}

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

$error = null;
$message = null;
$user = null;

$hdbc = @mysql_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD);

if (!$hdbc)
{
	$error = mysql_error();
}
else
{
	if (mysql_select_db(DB_NAME, $hdbc))
	{
		mysql_set_charset('utf8');
	}
	else
	{
		$error = mysql_error();
		mysql_close($hdbc);
		$hdbc = null;
	}
}

if (!$error)
{
	// callback function for user login, register, etc.
	require_once 'user.php';

	$error = UserProcessRequest($user, $message);
}

if ($user)
{
	if (isset($_GET['lang']))
		$user->language($_GET['lang']);	//&& -> hdbc
}

/*<html>*******************************************************************/
?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo PROJECT; ?> &ndash; <?php echo ORGANISATION; ?></title>
<meta name="language" content="<?php echo $_SESSION['lang']; ?>">
<meta name="description" lang="en" content="Spotter schedule for Frankfurt airport (FRA/EDDF) including aircraft registrations">
<meta name="description" lang="en" content="Spotter-Flugplan für Frankfurt (FRA/EDDF) einschließlich Flugzeugkennungen">
<meta name="keywords" content="fra, eddf, frankfurt, fraspotter, fraspotting, planespotting, fra-flights, fra-schedule, flederwiesel">
<meta name="keywords" lang="en" content="airport, aircraft, aviation, spotter, spotting, schedule, flights, flight schedule, flight plan">
<meta name="keywords" lang="de" content="Flughafen, Flugzeug, Luftfahrt, Spotter, Spotting, Flugzeugfotografie, Flüge, Flugplan">
<meta name="robots" content="index, nofollow">
<meta name="author" content="Tobias Kühne">
<meta name="generator" content="http://www.ultraedit.com/">
<?php if ($mobile && !$tablet) { ?>
<meta name="viewport" content="width=device-width; initial-scale=1.0;"/>
<?php } ?>
<link rel="apple-touch-icon" href="apple-touch-icon.png"/>
<link type="image/gif" rel="icon" href="favicon.gif">
<link type="text/css" rel="stylesheet" href="script/<?php echo $jqueryui; ?>/themes/base/<?php echo $jqueryminified; ?>jquery.ui.core<?php echo $jquerymin; ?>.css">
<link type="text/css" rel="stylesheet" href="script/<?php echo $jqueryui; ?>/themes/base/<?php echo $jqueryminified; ?>jquery.ui.theme<?php echo $jquerymin; ?>.css">
<link type="text/css" rel="stylesheet" href="script/<?php echo $jqueryui; ?>/themes/base/<?php echo $jqueryminified; ?>jquery.ui.tooltip<?php echo $jquerymin; ?>.css">
<link type="text/css" rel="stylesheet" href="script/<?php echo $jqueryui; ?>/themes/base/<?php echo $jqueryminified; ?>jquery.ui.slider<?php echo $jquerymin; ?>.css">
<link type="text/css" rel="stylesheet" href="script/<?php echo $jqueryui; ?>/themes/base/<?php echo $jqueryminified; ?>jquery.ui.datepicker<?php echo $jquerymin; ?>.css">
<?php if ($mobile && !$tablet) { ?>
<link rel="stylesheet" type="text/css" href="css/mobile.css">
<?php } else { ?>
<link rel="stylesheet" type="text/css" media="screen, print" href="css/desktop.css">
<!--[if IE]>
<link rel="stylesheet" type="text/css" media="screen, print" href="css/ie/desktop.css">
<![endif]-->
<?php } ?>
<script type="text/javascript" src="script/<?php echo $jqueryui; ?>/<?php echo $jquery.$jquerymin; ?>.js"></script>
<script type="text/javascript" src="script/<?php echo $jqueryui; ?>/ui/<?php echo $jqueryminified; ?>jquery.ui.core<?php echo $jquerymin; ?>.js"></script>
<script type="text/javascript" src="script/<?php echo $jqueryui; ?>/ui/<?php echo $jqueryminified; ?>jquery.ui.widget<?php echo $jquerymin; ?>.js"></script>
<script type="text/javascript" src="script/<?php echo $jqueryui; ?>/ui/<?php echo $jqueryminified; ?>jquery.ui.mouse<?php echo $jquerymin; ?>.js"></script>
<script type="text/javascript" src="script/<?php echo $jqueryui; ?>/ui/<?php echo $jqueryminified; ?>jquery.ui.position<?php echo $jquerymin; ?>.js"></script>
<script type="text/javascript" src="script/<?php echo $jqueryui; ?>/ui/<?php echo $jqueryminified; ?>jquery.ui.tooltip<?php echo $jquerymin; ?>.js"></script>
<script type="text/javascript" src="script/<?php echo $jqueryui; ?>/ui/<?php echo $jqueryminified; ?>jquery.ui.slider<?php echo $jquerymin; ?>.js"></script>
<script type="text/javascript" src="script/<?php echo $jqueryui; ?>/ui/<?php echo $jqueryminified; ?>jquery.ui.datepicker<?php echo $jquerymin; ?>.js"></script>
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

						if (!$error)
						{
							if (isset($_GET['req']))
							{
								if ('logout' == $_GET['req'])
									echo " &ndash; $lang[$dir]";
							}
							else
							{
								if (!isset($_GET['page']))
									echo " &ndash; $lang[$dir]";
							}
						}
?></h3>
				</div>
<?php require_once 'nav.php'; ?>
			</div>
<?php
			if (!$hdbc)
			{
				if (!$error)
					$error = $lang['unexpected'];
?>
				<div id="error">
					<h1><?php echo $lang['fatal']; ?></h1>
					<?php echo sprintf($lang['dberror'], __FILE__, __LINE__, $error); ?>
				</div>
<?php
				if (isset($_GET['page']))
				{
					if ('help' == $_GET['page'])
						require_once 'content/help.php';
				}
			}
			else
			{
?>
			<div id="content">
<?php
				if (file_exists('adminmessage.php'))
				{
					require_once 'adminmessage.php';

					/*
						$adminmessage = array(
							'from' => '%Y-%m-%d 03:30',
							'until' => '%Y-%m-%d 04:30 +1 day',
							'en' => '',
							'de' => '',
					*/

					if (isset($adminmessage))
					{
						$now = strtotime('now');

						if (isset($adminmessage['from']))
							$from = strtotime(strftime($adminmessage['from']));
						else
							$from = 0;

						if (isset($adminmessage['until']))
							$until = strtotime(strftime($adminmessage['until']));
						else
							$until = ~0;

						if ($now >= $from &&
							$now < $until &&
							isset($adminmessage[$_SESSION['lang']]))
						{
?>
				<div id="admin" class="notice center">
					<?php echo $adminmessage[$_SESSION['lang']]; ?>
				</div>
<?php
						}
					}
				}
				if (!isset($_GET['req']))
				{
					// TODO: stat $_GET['req'].php !!!
					require_once content();
				}
				else
				{
					switch ($_GET['req'])
					{
					case 'register':
					case 'activate':
					case 'login':
					case 'reqtok':
					case 'changepw':
					case 'profile':
						require_once "forms/$_GET[req].php";
						break;

					default:
						require_once content();
					}
				}
?>
			</div>
<?php		} ?>
		</div>
<?php
		if ((!$mobile || $tablet) && !isset($_GET['req']) && !isset($_GET['page']))
		{
?>
		<div>
			<div id="counter" class="box center">
				<a href="https://info.flagcounter.com/y9Ko">
					<img src="https://s07.flagcounter.com/count/y9Ko/bg_ffffff/txt_a3aab7/border_ffffff/columns_8/maxflags_248/viewers_Live+Schedule+Visitors:/labels_1/pageviews_0/flags_0/" alt="Free counters!">
				</a>
			</div>
		</div>
<?php	} ?>
	</div>
</body>
</html>
<?php /*</html>****************************************************************/

if ($hdbc)
	mysql_close($hdbc);
?>
