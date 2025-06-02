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
require_once 'classes/sql-xpdo.php';

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

function rev()
{
	echo '?rev='.preg_replace('/[^0-9]/', '', '$Rev$');
}

/******************************************************************************
 * Equal goes it loose
 ******************************************************************************/

session_start();

/******************************************************************************
 * detect/set language and initialise strings
 ******************************************************************************/

/* Set session language from $_POST or $_COOKIE */
if (isset($_GET["lang"]))
{
	if ("de" == $_GET["lang"])
		$lang = "de";
}
else if (isset($_POST["lang"]))
{
	if ("de" == $_POST["lang"])
		$lang = "de";
}
else if (isset($_SESSION["lang"]))
{
	if ("de" == $_SESSION["lang"])
		$lang = "de";
}

if (empty($lang))
	$lang = http_preferred_language(["en", "de"]);

if ("de" == $lang)
	setlocale(LC_TIME, "deu", "deu_deu");
else
	setlocale(LC_TIME, "eng", "english-uk", "uk", "enu", "english-us", "us", "english", "C");

$_SESSION["lang"] = $lang;

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
header("Content-Language: {$lang}");

$file = "content/language/{$lang}.php";

if (file_exists($file))
	include "$file";
else
	include "content/language/en.php";

/******************************************************************************
 * initialise variables
 ******************************************************************************/

$db = null;
$error = null;
$message = null;
$user = null;

if (file_exists("db.disabled"))
{
	$message = $STRINGS["db.disabled"];
}
else
{
	try
	{
		if (isset($ExplainSQL))
			$classname = 'xPDO';
		else
			$classname = 'PDO';

		$db = new $classname(
			sprintf("mysql:host=%s;dbname=%s;charset=utf8mb4", DB_HOSTNAME, DB_NAME),
			DB_USERNAME,
			DB_PASSWORD,
			[
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'",
			]
		);
	}
	catch (PDOException $ex)
	{
		$error = PDOErrorInfo($ex, $STRINGS['dberror']);
	}

	if (!$error)
	{
		// callback function for user login, register, etc.
		require_once 'user.php';

		$error = UserProcessRequest($db, $user, $message);
	}
}

if ($user)
{
	/**************************************************************************
	 * set profile cookies
	 **************************************************************************/

	if (isset($_GET['req']))
	{
		if ('profile'  == $_GET['req'] ||
			'changepw' == $_GET['req'])
		{
			// TODO: This should be done more professional...
			// e.g. profile/dispinterval, so we can check for 'profile' substr
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
					if (isset($_GET['photodb']))
					{
						$item = 'photodb';
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
			}

			setcookie('profile-item', $item, time() + COOKIE_LIFETIME);
		}
	}
}

/*<html>*******************************************************************/
?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo PROJECT; ?> &ndash; <?php echo ORGANISATION; ?></title>
<meta name="language" content="<?php echo $lang; ?>">
<link rel="alternate" href="http://www.fra-flugplan.de?lang=de" hreflang="de">
<link rel="alternate" href="http://www.fra-flugplan.de?lang=en" hreflang="en">
<link rel="alternate" href="http://www.fra-flugplan.de" hreflang="x-default">
<?php
if ('de' == $lang) {
?>
<meta name="description" content="Spotter-Flugplan für Frankfurt (FRA/EDDF) einschließlich Flugzeugkennungen">
<meta name="keywords" content="fra,eddf,frankfurt,spotter,spotting,planespotting,flederwiesel">
<meta name="keywords" lang="en" content="airport,aircraft,aviation,schedule,flights,fra-schedule">
<meta name="keywords" lang="de" content="Flughafen,Flugzeug,Luftfahrt,Flugzeugfotografie,Flugplan,fra-flugplan">
<?php
} else {
?>
<meta name="description" content="Spotter schedule for Frankfurt airport (FRA/EDDF) including aircraft registrations">
<meta name="keywords" content="flederwiesel, fra-flugplan, FRA, EDDF, Frankfurt, flight schedule, flight plan, airport, spotter, spotting, planespotting, aircraft registration, aviation">
<?php
}
?>
<meta name="robots" content="index, nofollow">
<meta name="author" content="Tobias Kühne">
<meta name="generator" content="http://www.ultraedit.com/">
<?php if ($mobile && !$tablet) { ?>
<meta name="viewport" content="width=device-width; initial-scale=1.0;"/>
<?php } ?>
<link rel="apple-touch-icon" href="apple-touch-icon.png"/>
<!-- IE -->
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
<!-- other browsers -->
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link type="text/css" rel="stylesheet" href="script/<?php echo "{$jqueryui}/themes/base/{$jqueryminified}jquery.ui.core{$jquerymin}.css"; rev(); ?>">
<link type="text/css" rel="stylesheet" href="script/<?php echo "${jqueryui}/themes/base/{$jqueryminified}jquery.ui.theme{$jquerymin}.css"; rev(); ?>">
<link type="text/css" rel="stylesheet" href="script/<?php echo "${jqueryui}/themes/base/{$jqueryminified}jquery.ui.tooltip{$jquerymin}.css"; rev(); ?>">
<link type="text/css" rel="stylesheet" href="script/<?php echo "${jqueryui}/themes/base/{$jqueryminified}jquery.ui.slider{$jquerymin}.css"; rev(); ?>">
<link type="text/css" rel="stylesheet" href="script/<?php echo "${jqueryui}/themes/base/{$jqueryminified}jquery.ui.datepicker{$jquerymin}.css"; rev(); ?>">
<?php if ($mobile && !$tablet) {
//https://markjaquith.wordpress.com/2009/05/04/force-css-changes-to-go-live-immediately/ ?>
<link rel="stylesheet" type="text/css" href="css/mobile.css<?php rev(); ?>">
<?php } else { ?>
<link rel="stylesheet" type="text/css" media="screen, print" href="css/desktop.css<?php rev(); ?>">
<!--[if IE]>
<link rel="stylesheet" type="text/css" media="screen, print" href="css/ie/desktop.css<?php rev(); ?>">
<![endif]-->
<?php } ?>
<script type="text/javascript" src="script/<?php echo "{$jqueryui}/{$jquery}{$jquerymin}.js"; rev(); ?>"></script>
<script type="text/javascript" src="script/<?php echo "{$jqueryui}/ui/{$jqueryminified}jquery.ui.core{$jquerymin}.js"; rev(); ?>"></script>
<script type="text/javascript" src="script/<?php echo "{$jqueryui}/ui/{$jqueryminified}jquery.ui.widget{$jquerymin}.js"; rev(); ?>"></script>
<script type="text/javascript" src="script/<?php echo "{$jqueryui}/ui/{$jqueryminified}jquery.ui.mouse{$jquerymin}.js"; rev(); ?>"></script>
<script type="text/javascript" src="script/<?php echo "{$jqueryui}/ui/{$jqueryminified}jquery.ui.position{$jquerymin}.js"; rev(); ?>"></script>
<script type="text/javascript" src="script/<?php echo "{$jqueryui}/ui/{$jqueryminified}jquery.ui.tooltip{$jquerymin}.js"; rev(); ?>"></script>
<script type="text/javascript" src="script/<?php echo "{$jqueryui}/ui/{$jqueryminified}jquery.ui.slider{$jquerymin}.js"; rev(); ?>"></script>
<script type="text/javascript" src="script/<?php echo "{$jqueryui}/ui/{$jqueryminified}jquery.ui.datepicker{$jquerymin}.js"; rev(); ?>"></script>
</head>
<body>
	<noscript>
		<div class="noscript"><?php echo $STRINGS['noscript']; ?></div>
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
						echo "$STRINGS[liveschedule]";

						if (!$error)
						{
							if (isset($_GET['req']))
							{
								if ('logout' == $_GET['req'])
									echo " &ndash; $STRINGS[$dir]";
							}
							else
							{
								if (!isset($_GET['page']))
									echo " &ndash; $STRINGS[$dir]";
							}
						}
?></h3>
				</div>
<?php require_once 'nav.php'; ?>
			</div>
			<div id="content">
<?php
			if (file_exists('adminmessage.php'))
			{
				require_once 'adminmessage.php';

				/*
					$adminmessage = [
						'from' => '%Y-%m-%d 03:30',
						'until' => '%Y-%m-%d 04:30 +1 day',
						'en' => '',
						'de' => '',
					]
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
						isset($adminmessage[$lang]))
					{
?>
				<div id="admin" class="notice center">
				<?php echo $adminmessage[$lang]; ?>
				</div>
<?php
					}
				}
			}

			if (!$db)
			{
				if (!$error)
				{
					if ($message)
					{
?>
						<div id="notification" class="notice nodb">
							<?php echo $message; ?>
						</div>
<?php
					}
					else
					{
						$error = $STRINGS['unexpected'];
?>
				<div id="error">
					<h1><?php echo $STRINGS['fatal']; ?></h1>
					<?php echo $error; ?>
				</div>
<?php
					}
				}

				if (isset($_GET['page']))
				{
					if ('help' == $_GET['page'])
						require_once 'content/help.php';
				}
			}
			else
			{
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
	</div>
</body>
</html>
