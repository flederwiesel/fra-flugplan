<?php

/******************************************************************************
 *
 *       project: FRA-flights Live Schedule
 *
 *       $Author$
 *         $Date$
 *          $Rev$
 *
 ******************************************************************************
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

error_reporting(E_ALL | E_NOTICE);

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

//&& 	$_SERVER[HTTP_ACCEPT_ENCODING]
mb_internal_encoding('UTF-8');

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

@require_once '.config';

session_start();

/******************************************************************************
 * detect/set language and initialise strings
 ******************************************************************************/

/* Set session language from $_POST or $_COOKIE */
if (isset($_GET['lang']))
{
	setcookie('lang', $_GET['lang'], time() + COOKIE_LIFETIME);
	$_SESSION['lang'] = $_GET['lang'];
}
else
{
	if (!isset($_SESSION['lang']))
	{
		if (isset($_COOKIE['lang']))
			$_SESSION['lang'] = $_COOKIE['lang'];
		else
			$_SESSION['lang'] = 'en';
//&& 	$_SERVER[HTTP_ACCEPT_LANGUAGE]
	}

	setcookie('lang', $_SESSION['lang'], time() + COOKIE_LIFETIME);
}

header('Content-Type: text/html; charset=UTF-8');
header('Content-Language: '.$_SESSION['lang']);

$file = 'content/language/'.$_SESSION['lang'].'.php';

if (file_exists($file))
	@require_once $file;
else
	@require_once 'content/language/en.php';

/******************************************************************************
 * log cookies
 ******************************************************************************/

$file = @fopen('cookies.ini', 'a+');

if ($file)
{
	foreach ($_COOKIE as $reg => $comment)
	{
		switch ($reg)
		{
		case 'lang':
		case 'userID':
		case 'autologin':
		case 'hash':
		case 'PHPSESSID':
			/* do not log */
			break;

		default:
			fprintf($file, "%s = %s\n", $reg, $comment);
		}
	}

	fclose($file);
}

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
	// permissions may be defined here
	@require_once 'user.php';

	function content()
	{
		if (isset($_GET['page']))
			return "content/$_GET[page].php";
		else
			return 'content/index.php';
	}

	if (!isset($_GET['req']))
	{
		// try autologin from cookies
		$user = LoginUserAutomatically();
	}
	else
	{
		if (!('logout' == $_GET['req']))
		{
			// try autologin from cookies
			$user = LoginUserAutomatically();
		}
		else
		{
			// user requested logout, clear user data (cookies)
			LogoutUser();
			$user = null;

			if ('' == $_GET['req'])
				unset($_GET['req']);
		}
	}

	if (isset($_GET['req']))
	{
		if (isset($_POST['passwd']))
		{
			if (!('login' == $_GET['req']))
			{
				if (isset($_POST['passwd-confirm']))
				{
					if ($_POST['passwd'] != $_POST['passwd-confirm'])
						$error = $lang['passwordsmismatch'];
				}
			}
		}

		if (!$error)
		{
			if ('register' == $_GET['req'])
			{
				if (isset($_POST['user']) &&
					isset($_POST['email']))		/* else no post, we just followed a link */
				{
					if (strlen($_POST['user']) < USERNAME_MIN)
					{
						$error = sprintf($lang['usernamelengthmin'], USERNAME_MIN);
					}
					else
					{
						if (strlen($_POST['user']) > USERNAME_MAX)
						{
							$error = sprintf($lang['usernamelengthmax'], USERNAME_MAX);
						}
						else
						{
							if (preg_match('/^[A-Z0-9._%+-]+@([A-Z0-9-]+\.)*[A-Z0-9-]{2,}\.[A-Z]{2,6}$/i', $_POST['email']) != 1)
							{
								$error = sprintf($lang['emailinvalid'], USERNAME_MAX);
							}
							else
							{
								if ($_POST['passwd'] != $_POST['passwd-confirm'])
								{
									$error = $lang['passwordsmismatch'];
								}
								else
								{
									if (!RegisterUser($_POST['user'], $_POST['email'], $_POST['passwd'], $_POST['lang'], $message))
									{
										$error = $message;
									}
									else
									{
										$_GET['user'] = $_POST['user'];
										$_GET['req'] = 'activate';
									}
								}
							}
						}
					}
				}
				/* 'register' */
			}
			else if ('activate' == $_GET['req'])
			{
				$req = null;

				if (isset($_GET['user']) &&
					isset($_GET['token']))		/* from email link */
				{
					$req = $_GET;
				}
				else
				{
					if (isset($_POST['user']) &&
						isset($_POST['token']))		/* else no post, we just followed a link */
					{
						$req = $_POST;
					}
				}

				if ($req)
				{
					if (!ActivateUser($req['user'], $req['token'], $message))
					{
						$error = $message;
					}
					else
					{
						$message = $lang['activationsuccess'];

						$_GET['req'] = 'login';
						$_GET['user'] = $_POST['user'];
					}

					unset($req);
				}
				/* activate */
			}
			else if ('login' == $_GET['req'])
			{
				if (isset($_POST['user']))		/* else no post, we just followed a link */
				{
					$user = LoginUser($_POST['user'], $_POST['passwd'], false, isset($_POST['autologin']), $message);

					if (!$user)
					{
						$error = $message;
					}
					else
					{
						$message = NULL;

						$_GET['req'] = '';
					}
				}
				/* 'login' */
			}
			else if ('reqtok' == $_GET['req'])
			{
				if (isset($_POST['user']) &&
					isset($_POST['email']))		/* else no post, we just followed a link */
				{
					if (!RequestPasswordChange($_POST['user'], $_POST['email'], $message))
					{
						$error = $message;
					}
					else
					{
						$message = $lang['tokensent'];

						$_GET['req'] = 'changepw';
						$_GET['user'] = $_POST['user'];
					}
				}
				/* 'reqtok' */
			}
			else if ('changepw' == $_GET['req'])
			{
				if ((isset($_POST['user']) || $user) &&
					 isset($_POST['passwd']) &&
					 isset($_POST['passwd-confirm']))		/* else no post, we just followed a link */
				{
					if (!ChangePassword(isset($_POST['user']) ? $_POST['user'] : $user->name(),//&& ->id()?
									    isset($_POST['token']) ? $_POST['token'] : null,
									    $_POST['passwd'],
									    $message))
					{
						$error = $message;
					}
					else
					{
						if ($user)
						{
							$message = $lang['passwdchanged'];
						}
						else
						{
							$message = $lang['passwdchangedlogin'];

							$_GET['req'] = 'login';
							$_GET['user'] = $_POST['user'];
						}
					}
				}
				/* changepw */
			}
		}

		if ('' == $_GET['req'])
			unset($_GET['req']);
	}	/* if (isset($_GET['req'])) */
}

if ($user)
	if (isset($_GET['lang']))
		$user->language($_GET['lang']);

/******************************************************************************
 * detect device type
 ******************************************************************************/

@require_once 'classes/Mobile_Detect.php';

$device = new Mobile_Detect();

if (!$device)
{
	$mobile = false;
}
else
{
	/* Treat tablets as desktop */
	$mobile = $device->isMobile() && !$device->isTablet();
	unset($device);
}

if (!$error)
{
	if (empty($_GET))
	{
		if (!isset($_SESSION['dir']))
			$_SESSION['dir'] = 'arrival';
	}
	else
	{
		if (isset($_GET['departure']))
			$_SESSION['dir'] = 'departure';

		if (isset($_GET['arrival']))
			$_SESSION['dir'] = 'arrival';
	}

	if (isset($_SESSION['dir']))
		$dir = $_SESSION['dir'];
	else
		$dir = 'arrival';

	$rev = 'arrival' == $dir ? 'departure' : 'arrival';
}

	/*<html>*******************************************************************/
?>
<?php if ($mobile) { ?>
<!DOCTYPE HTML SYSTEM "html40-mobile.dtd">
<?php } else {  ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<?php } ?>
<html>
<head>
<title>Live Schedule &ndash; Frankfurt Aviation Friends</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta name="language" content="<?php echo $_SESSION['lang']; ?>">
<meta name="author" content="Tobias Kühne">
<meta name="description" content="24h flight forecast for Frankfurt/Main airport with aircraft registrations">
<meta name="keywords" content="fra eddf frankfurt airport aircraft spotter schedule">
<meta name="robots" content="index, nofollow">
<meta name="generator" content="Programmer's Notepad">
<?php if ($mobile) { ?>
<meta name="viewport" content="width=device-width; initial-scale=1.0;"/>
<link rel="stylesheet" type="text/css" href="css/mobile.css">
<?php } else { ?>
<link rel="stylesheet" type="text/css" media="screen, projection, handheld, print" href="css/desktop.css">
<?php } ?>
<link rel="icon" href="favicon.gif" type="image/gif">
<script type="text/javascript" src="script/jquery-1.8.0.min.js"></script>
<script type="text/javascript" src="script/jquery.sha256.min.js"></script>
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
		<div id="box">
			<div>
				<div id="head">
					<h1 class="nobr">Frankfurt Aviation Friends</h1>
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
				<div id="nav"><?php require_once('nav.php'); ?>
				</div>
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
						@require_once 'content/help.php';
				}
			}
			else
			{
?>
		<div id="content">
<?php
				if (!isset($_GET['req']))
				{
					@require_once content();
				}
				else
				{
					if ('logout' == $_GET['req'])
					{
						@require_once content();
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
							echo '<div id="auth">';
							@require_once("forms/$_GET[req].php");
							echo '</div>';
							break;

						default:
							@require_once content();
						}
					}
				}
?>
			</div>
<?php } ?>
		</div>
	</div>
</body>
</html>
<?php /*</html>****************************************************************/

if ($hdbc)
	mysql_close($hdbc);
?>
