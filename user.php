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
require_once 'classes/curl.php';


function /* char* */ token() { return hash('sha256', mcrypt_create_iv(32)); }

function /* char* */ PasswordRegex($min, $letter, $upper, $lower, $digit, $specials)
{
	$regex = '/^';

	if ($min)
	{
		if ($letter)
		{
			$regex .= '(?=.*[A-Za-z])';
		}
		else
		{
			if ($upper)
				$regex .= '(?=.*[A-Z])';

			if ($lower)
				$regex .= '(?=.*[a-z])';
		}

		if ($digit)
			$regex .= '(?=.*[0-9])';

		if (strlen($specials) > 0)
		{
			// Quote everything with (possibly) special meaning within
			// regex character class definition
			$escaped = '';

			for ($i = 0; $i < strlen($specials); $i++)
			{
				$c = $specials[$i];

				if (strchr('/[^(-)]\#', $c))
					$escaped .= "\\$c";
				else
					$escaped .= "$c";
			}

			$regex .= "(?=.*[$escaped])";
		}
	}

	$regex .= sprintf('.{%lu,}', $min);
	$regex .= '$/';

	return $regex;
}

function /* char* */ PasswordHint()
{
	global $lang;

	$sep = 0;
	$text = sprintf($lang['passwd-min'], $GLOBALS['PASSWORD_MIN']);

	if ($GLOBALS['PASSWORD_REQUIRES_LETTER'])
	{
		$text .= sprintf($lang['passwd-letter']);
		$sep++;
	}

	if ($GLOBALS['PASSWORD_REQUIRES_UPPER'])
	{
		$text .= sprintf($lang['passwd-upper']);
		$sep++;
	}

	if ($GLOBALS['PASSWORD_REQUIRES_LOWER'])
	{
		$text .= sprintf($lang['passwd-lower']);
		$sep++;
	}

	if ($GLOBALS['PASSWORD_REQUIRES_DIGIT'])
	{
		$text .= sprintf($lang['passwd-digit']);
		$sep++;
	}

	if (strlen($GLOBALS['PASSWORD_REQUIRES_SPECIAL']))
	{
		$text .= sprintf($lang['passwd-special'], str_replace('%', '%%', $specials));
		$sep++;
	}

	$separators = array();

	if ($sep > 0)
	{
		if ($sep > 1)
			$separators[] = $lang['passwd-separator-0'];

		for ($i = 1; $i < $sep - 1; $i++)
			$separators[] = ',';

		$separators[] = ' '.$lang['and'];
	}

	$text = vsprintf($text, $separators);
	$text .= $sep ? $lang['passwd-postfix-N'] : $lang['passwd-postfix-0'];

	return $text;
}

function /* bool */ PasswordConstraintMet($passwd)
{
	$regex = PasswordRegex($GLOBALS['PASSWORD_MIN'],
						   $GLOBALS['PASSWORD_REQUIRES_LETTER'],
						   $GLOBALS['PASSWORD_REQUIRES_UPPER'],
						   $GLOBALS['PASSWORD_REQUIRES_LOWER'],
						   $GLOBALS['PASSWORD_REQUIRES_DIGIT'],
						   $GLOBALS['PASSWORD_REQUIRES_SPECIAL']);

	return preg_match($regex, $passwd);
}

function /* bool */ PasswordsMatch($password, $confirm)
{
	if (isset($password))
	{
		if (isset($confirm))
		{
			if ($password == $confirm)
				return true;
		}
	}

	return false;
}

function AdminMail($subject, $text)
{
	$header = sprintf(
		"From: FRA schedule <%s>\n".
		"Reply-To: %s\n".
		"Mime-Version: 1.0\n".
		"Content-type: text/plain; charset=ISO-8859-1\n".
		"Content-Transfer-Encoding: 8bit\n".
		"X-Mailer: PHP/%s\n",
		ADMIN_EMAIL_FROM,
		ADMIN_EMAIL,
		phpversion());

	return @mail(mb_encode_mimeheader(ADMIN_NAME, 'ISO-8859-1', 'Q').' <'.ADMIN_EMAIL.'>',
				 mb_encode_mimeheader("user $subject", 'ISO-8859-1', 'Q'),
				 mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8'), $header);
}

class User
{
	private $id = 0;
	private $name = null;
	private $email = null;
	private $timezone = null;
	private $lang = null;
	private $options = array();
	private $gid = array();

	public function __construct($id, $name, $email, $tz, $lang, $groups)
	{
		$this->id = $id;
		$this->name = $name;
		$this->email = $email;
		$this->timezone = $tz;
		$this->lang = $lang;
		$this->groups = $groups;

		return $this;
	}

	public function id() { return $this->id; }
	public function name() { return $this->name; }
	public function email() { return $this->email; }
	public function timezone() { return $this->timezone; }

	public function language($lang = NULL)
	{
		if (NULL == $lang)
			return $this->lang;
		else
			$this->lang = $lang;

		 return NULL;
	}

	public function IsMemberOf($group) { return in_array($group, $this->groups); }

	public function opt($name, $value = NULL)
	{
		if (null == $value)
		{
			if (isset($this->options[$name]))
				return $this->options[$name];
		}
		else
		{
			$this->options[$name] = $value;
			return $value;
		}

		 return NULL;
	}
}

function /* bool */ pwmatch()
	// __out $_POST['passwd']
	// __out $_POST['passwd-confirm']
{
	if (isset($_POST['passwd']))
	{
		if (isset($_POST['passwd-confirm']))
		{
			if ($_POST['passwd'] == $_POST['passwd-confirm'])
				return true;
		}
	}

	return false;
}

function LogoutUser(/* __out */ &$user)
	// __out $_GET['req']
	// __out $_COOKIE['userID']
	// __out $_COOKIE['hash']
{
	$user = null;

	unset($_GET['req']);

	unset($_COOKIE['userID']);
	unset($_COOKIE['hash']);

	// remove cookies
	setcookie('userID', 0, 0);
	setcookie('hash', null, 0);
	setcookie('autologin', false, 0);

	return null;
}

function LoginUserAutomatically(/* __out */ &$user)
	// __out $_COOKIE['userID']
	// __out $_COOKIE['hash']
	// __out $_COOKIE['autologin']
{
	$error = null;
	$user = null;

	if (isset($_COOKIE['userID']) &&
		isset($_COOKIE['hash']) &&
		isset($_COOKIE['autologin']))
	{
		$error = LoginUserSql($user, $_COOKIE['userID'], true, $_COOKIE['hash'], 1);
	}

	return $error;
}

function /*bool*/ LoginUser(/* __out */ &$user)
	// __out $_POST['user']
	// __out $_POST['passwd']
{
	global $lang;

	$error = null;

	if (isset($_POST['user']))		/* else: no post, just display form */
	{
		if (!isset($_POST['passwd']))
		{
			$error = $lang['authfailed'];
		}
		else
		{
			$error = LoginUserSql($user, $_POST['user'], false, $_POST['passwd'], isset($_POST['autologin']));

			if (!$error)
				unset($_GET['req']);
		}
	}

	return $error;
}

function /* char *error */ LoginUserSql(/* __out */ &$user, $id, $byid, $password, $remember)
{
	global $lang;

	$user = null;
	$error = null;

	$query = sprintf("SELECT `%s`, `passwd`, `salt`, `email`, `timezone`, `language`,".
					 " `token_type`, `tm-`, `tm+`, `tt-`, `tt+`,".
					 " `notification-from`, `notification-until`, `notification-timefmt`".
					 " FROM `users` WHERE `%s`='%s'",
					 $byid ? 'name' : 'id',
					 $byid ? 'id' : 'name',
					 $id);

	$result = mysql_query($query);

	if (!$result)
	{
		$error = mysql_error();
	}
	else
	{
		if (mysql_num_rows($result) != 1)
		{
			$error = mysql_error();

			if (!$error)
				$error = $lang['authfailed'];
		}
		else
		{
			$row = mysql_fetch_assoc($result);

			if (!$row)
			{
				$error = mysql_error();
			}
			else
			{
				if (isset($row['token_type']))
				{
					switch ($row['token_type'])
					{
					case 'activation':
						$error = $lang['activationrequired'];
						break;

					case 'password':
						$error = $lang['authfailedtoken'];
						break;

					case 'none':
					default:
						break;
					}
				}

				if (!$error)
				{
					$hash = $byid ? $password : hash_hmac('sha256', $password, $row['salt']);

					if ($row['passwd'] != $hash)
					{
						setcookie('hash', null, 0);

						$error = $lang['authfailed'];
					}
					else
					{
						if ($byid)
						{
							$name = $row['name'];
						}
						else
						{
							$name = $id;
							$id = $row['id'];
						}
					}
				}
			}
		}

		mysql_free_result($result);
	}

	if (!$error)
	{
		$query = <<<SQL
			SELECT `groups`.`name`
			FROM `membership`
			INNER JOIN `groups` ON `membership`.`group` = `groups`.`id`
			WHERE `user`=$id
SQL;

		$result = mysql_query($query);

		if (!$result)
		{
			$error = mysql_error();
		}
		else
		{
			if (0 == mysql_num_rows($result))
			{
				$error = $lang['nopermission'];
			}
			else
			{
				while ($group = mysql_fetch_row($result))
					$groups[] = $group[0];

				if (!$groups)
				{
					$error = mysql_error();
				}
				else
				{
					$user = new User($id, $name, $row['email'], $row['timezone'], $row['language'], $groups);

					if ($user)
					{
						$user->opt('tm-', $row['tm-']);
						$user->opt('tm+', $row['tm+']);
						$user->opt('tt-', $row['tt-']);
						$user->opt('tt+', $row['tt+']);
						$user->opt('notification-from', $row['notification-from']);
						$user->opt('notification-until', $row['notification-until']);
						$user->opt('notification-timefmt', $row['notification-timefmt']);

						$expires = (1 == $remember) ? time() + COOKIE_LIFETIME : 0;

						setcookie('userID',    $user->id(),       $expires);
						setcookie('hash',      $hash,             $expires);
						setcookie('autologin', true,              $expires);
						setcookie('lang',      $user->language(), $expires);

						$query = sprintf("UPDATE `users` SET `last login`='%s' WHERE `id`=%u",
										 strftime('%Y-%m-%d %H:%M:%S'), $user->id());

						mysql_query($query);
					}
				}
			}

			mysql_free_result($result);
		}
	}

	return $error;
}

/* Check for forum spam and provide hrefs for reporting */
function /* bool */ SuspectedSpam(/* __in */ $user,
								 /* __in */ $email,
								 /* __in */ $ipaddr /* ['real','fwd'] */,
								 /* __out */ /* char *error */ &$message)
{
	global $lang;

 	$suspicion = NULL;
	$curl = new curl();

	if ($curl)
	{
		/* Suspected spam? */
		$stopforumspam = isset($_SESSION['stopforumspam']) ? urldecode("$_SESSION[stopforumspam]/") : NULL;
		$stopforumspam = sprintf("http://${stopforumspam}api.stopforumspam.org/api?".
								 "f=json&ip=%s&email=%s&username=%s",
								 $ipaddr['real'], urlencode($email), urlencode($user));

		$error = $curl->exec($stopforumspam, $json, 5);

		if (!$error)
		{
			$json = (object)json_decode($json);

			if ($json)
			{
				if ($json->success)
				{
					if ($json->username->confidence < 96.0)
						$json->username->appears = 0;

					if ($json->email->confidence < 92.0)
						$json->email->appears = 0;

					if ($json->ip->confidence < 98.0)
						$json->ip->appears = 0;

					if ($json->username->appears ||
						$json->email->appears    ||
						$json->ip->appears)
					{
						AdminMail('registration',
							sprintf("\nSuspected spam:\n%s=%s\n%s=%s\n%s=%s\n".
									"\nReport: http://www.stopforumspam.com/add.php?".
									"api_key=nrt20iomfc34sz&ip_addr=%s&email=%s&username=%s".
									"&evidence=Automated%%20registration%%2e\n",
									$user, $json->username->appears ? $json->username->confidence : 0,
									$email, $json->email->appears ? $json->email->confidence : 0,
									$ipaddr['real'], $json->ip->appears ? $json->ip->confidence : 0,
									$ipaddr['real'], urlencode($email), urlencode($user),
									$_SERVER['SERVER_NAME']));

						$suspicion = array();

						if ($json->username->appears)
							$suspicion[0] = $lang['username'];

						if ($json->email->appears)
							$suspicion[1] = $lang['emailaddress'];

						if ($json->ip->appears)
							$suspicion[2] = $lang['ipaddress'];

						/* Join suspicions to string, separated with command and "and" */
						$last  = array_slice($suspicion, -1);
						$first = join(', ', array_slice($suspicion, 0, -1));
						$both  = array_filter(array_merge(array($first), $last), 'strlen');
						$insert = join(" $lang[and] ", $both);

						$plural = $json->username->appears ?
								 ($json->email->appears ? 1 : $json->ip->appears) :
								 ($json->email->appears ? 0 : $json->ip->appears);

						$message = sprintf($plural ? $lang['spam:plur'] : $lang['spam:sing'], $insert);
					}
				}
			}
		}

		unset($curl);
	}

	return $message;
}

function /* char *error */ RegisterUser(/* __out */ &$message)
	// __in $_POST['user']
	// __in $_POST['email']
	// __in $_POST['passwd']
	// __in $_POST['passwd-confirm']
	// __in $_POST['lang']
	// __in $_SESSION['lang']

	// __out $_GET['req']
	// __out $_GET['user']
{
	global $lang;

	$error = null;
	$message = null;

	if (isset($_GET['stopforumspam']))
	{
		$_SESSION['stopforumspam'] = $_GET['stopforumspam'];
		$_SESSION['ipaddr'] = $_SERVER['HTTP_X_REAL_IP'];
	}

	if (isset($_POST['user']) &&
		isset($_POST['email']))		/* else: no request, just display form */
	{
		if (strlen($_POST['user']) < $GLOBALS['USERNAME_MIN'])
		{
			$error = sprintf($lang['usernamelengthmin'], $GLOBALS['USERNAME_MIN']);
		}
		else
		{
			if (strlen($_POST['user']) > $GLOBALS['USERNAME_MAX'])
			{
				$error = sprintf($lang['usernamelengthmax'], $GLOBALS['USERNAME_MAX']);
			}
			else
			{
				if (strlen($_POST['email']) > $GLOBALS['EMAIL_MAX'])
				{
					$error = sprintf($lang['emailinvalid']);
				}
				else if (preg_match('/^([A-Z0-9._%+-]+)@'.
									'([A-Z0-9-]+\.)*([A-Z0-9-]{2,})\.'.
									'[A-Z]{2,6}$/i', $_POST['email'], $match) != 1)
				{
					$error = sprintf($lang['emailinvalid']);
				}
				else
				{
					for ($m = 1; $m < count($match); $m++)
					{
						if (strlen($match[$m]) > 1024)
						{
							$error = sprintf($lang['emailinvalid']);
							break;
						}
					}

					if (!$error)
					{
						if (!PasswordConstraintMet($_POST['passwd']))
						{
							$error = PasswordHint();
						}
						else if (!PasswordsMatch($_POST['passwd'], $_POST['passwd-confirm']))
						{
							$error = $lang['passwordsmismatch'];
						}
						else
						{
							$ipaddr['fwd'] = NULL;

							if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
							{
								$ipaddr['fwd'] = $_SERVER['HTTP_X_FORWARDED_FOR'];

								if (preg_match('/83.125.11[2-5]/', $ipaddr['fwd']))
									$ipaddr['fwd'] = NULL;
							}

							$ipaddr['real'] = $_SESSION['ipaddr'];

							if (!$ipaddr['real'])
							{
								if (isset($_SERVER['HTTP_X_REAL_IP']))
									$ipaddr['real'] = $_SERVER['HTTP_X_REAL_IP'];
								else
									$ipaddr['real'] = $_SERVER['REMOTE_ADDR'];
							}

							$ipaddr['fwd'] = str_replace('::1', '127.0.0.1', $ipaddr['fwd']);
							$ipaddr['real'] = str_replace('::1', '127.0.0.1', $ipaddr['real']);

							if (!SuspectedSpam($_POST['user'], $_POST['email'], $ipaddr, $error))
							{
								if (!$error)
								{
									$error = RegisterUserSql($_POST['user'], $_POST['email'], $_POST['passwd'],
															 $ipaddr['real'].($ipaddr['fwd'] ? " ($ipaddr[fwd])" : ""),
															 isset($_POST['lang']) ? $_POST['lang'] :
															 isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en');

									if (!$error)
									{
										$message = $lang['regsuccess'];

										$_GET['req'] = 'activate';		// Form to be displayed next
										$_GET['user'] = $_POST['user'];	// Pre-set user name in form
									}
								}
							}
						}
					}
				}
			}
		}
	}

	return $error;
}

function /* char *error */ RegisterUserSql($user, $email, $password, $ipaddr, $language)
	// __in  $_SERVER['REMOTE_ADDR']
	// __out $_SESSION['lang']
{
	global $lang;

	$uid = null;
	$error = null;
	$expires = null;

	$result = mysql_query("SELECT `id` FROM `users` WHERE `name`='$user'");

	if (!$result)
	{
		$error = mysql_error();
	}
	else
	{
		if (mysql_num_rows($result) != 0)
			$error = $lang['userexists'];

		mysql_free_result($result);

		if (!$error)
		{
			$result = mysql_query("SELECT `id` FROM `users` WHERE `email`='$email'");

			if (!$result)
			{
				$error = mysql_error();
			}
			else
			{
				if (mysql_num_rows($result) != 0)
					$error = $lang['emailexists'];

				mysql_free_result($result);
			}
		}
	}

	if (!$error)
	{
		$salt = token();
		$token = token();
		$password = hash_hmac('sha256', $password, $salt);

		if (!mysql_query('START TRANSACTION'))
		{
			$error = mysql_user_error($lang['regfailed']);
		}
		else
		{
			$query = <<<SQL
				INSERT INTO
				`users`(
					`name`, `email`, `passwd`, `salt`, `language`,
					`token`, `token_type`, `token_expires`, `ip`
				)
				VALUES(
					'$user', '$email', '$password', '$salt', '$language',
					'$token', 'activation',
					FROM_UNIXTIME(UNIX_TIMESTAMP(UTC_TIMESTAMP()) + %lu), '%s'
				);
SQL;

			$query = sprintf($query, TOKEN_LIFETIME, $_SERVER['REMOTE_ADDR']);

			if (!mysql_query($query))
			{
				$error = mysql_user_error($lang['regfailed']);
			}
			else
			{
				$result = mysql_query("SELECT `id`,`token_expires` ".
									   "FROM `users` ".
									   "WHERE `id`=LAST_INSERT_ID()");

				if (!$result)
				{
					$error = mysql_error();
				}
				else
				{
					if (0 == mysql_num_rows($result))
					{
						$error = $lang['regfailed'];
					}
					else
					{
						$row = mysql_fetch_row($result);
						//&& $_POST['timezone'] -> localtime($expires)

						if (!$row)
						{
							$error = mysql_error();
						}
						else
						{
							$uid = $row[0];
							$expires = $row[1];
							$_SESSION['lang'] = $language;
						}
					}

					mysql_free_result($result);
				}

				$query = <<<SQL
					INSERT INTO `membership`(`user`, `group`)
					VALUES(
						LAST_INSERT_ID(),
						(SELECT `id` FROM `groups` WHERE `name`='users')
					);
SQL;

				if (!mysql_query($query))
					$error = mysql_user_error($lang['regfailed']);
			}
		}

		if ($error)
		{
			mysql_query('ROLLBACK');
		}
		else
		{
			if (!mysql_query('COMMIT'))
				$error = mysql_user_error($lang['regfailed']);
		}
	}

	if (!$error)
	{
		/* Send registration email to user */
		$header = sprintf(
			"From: %s <%s>\n".
			"Reply-To: %s\n".
			"Mime-Version: 1.0\n".
			"Content-type: text/plain; charset=ISO-8859-1\n".
			"Content-Transfer-Encoding: 8bit\n".
			"X-Mailer: PHP/%s\n",
			mb_encode_mimeheader(ORGANISATION, 'ISO-8859-1', 'Q'),
			ADMIN_EMAIL_FROM,
			ADMIN_EMAIL,
			phpversion());

		$subject = mb_encode_mimeheader($lang['subjactivate'], 'ISO-8859-1', 'Q');

		$body = sprintf($lang['emailactivation'],
						$ipaddr,
						$user, ORGANISATION, SITE_URL,
						$token, php_self(), $user, $token, $expires, ORGANISATION);

		/* http://www.outlookfaq.net/index.php?action=artikel&cat=6&id=84&artlang=de */
		$body = mb_convert_encoding($body, 'ISO-8859-1', 'UTF-8');

		if (!@mail($email, $subject, $body, $header))
		{
			$error = error_get_last();
			$error = $lang['mailfailed'].$error['message'];
		}
	}

	AdminMail('registration',
			  sprintf("$uid:$user <$email>%s = %s\n",
			  		  $expires ? " (expires $expires)" : "",
					  $error ? $error : "OK"));

	return $error;
}

function /* char *error */ ActivateUser(/* __out */ &$message)
	// __in $_POST['user']
	// __in $_POST['token']

	// __in $_GET['user']
	// __in $_GET['token']

	// __out $_GET['req']
	// __out $_GET['user']
{
	global $lang;

	$error = NULL;
	$message = NULL;
	$user = NULL;

	if (isset($_GET['user']))	/* from email link */
	{
		if (isset($_GET['token']))
		{
			$user = trim($_GET['user']);
			$token = trim($_GET['token']);
			unset($_GET['token']);
		}
	}
	else if (isset($_GET['token']))	/* from email link */
	{
		if (isset($_GET['user']))
		{
			$user = trim($_GET['user']);
			$token = trim($_GET['token']);
			unset($_GET['token']);
		}
		else
		{
			$error = sprintf($lang['activationfailed_u']);
		}
	}
	else
	{
		if (isset($_POST['user']) &&
			isset($_POST['token']))		/* else: no request, just display form */
		{
			if (!$_POST['user'])
			{
				$error = sprintf($lang['activationfailed_u']);
			}
			else if (!$_POST['token'])
			{
				$error = sprintf($lang['activationfailed_t']);
			}
			else
			{
				$user = trim($_POST['user']);
				$token = trim($_POST['token']);
				unset($_POST['token']);
			}
		}
	}

	if (!$error)
	{
		if ($user)
		{
			$error = ActivateUserSql($user, $token);

			if (!$error)
			{
				$token = NULL;
				$message = $lang['activationsuccess'];

				$_GET['req'] = 'login';	// Form to be displayed next
				$_GET['user'] = $user;	// Pre-set user name in form
			}
		}
	}

	if ($error)
	{
		define(SHA_256_LEN, 64);

		if (strlen($token) > SHA_256_LEN)
			$token = substr($token, 0, SHA_256_LEN + 1).'...';

		if (strlen($user) > $GLOBALS['USERNAME_MAX'])
			$user = substr($user, 0, $GLOBALS['USERNAME_MAX'] + 1).'...';

		AdminMail('activation',
			sprintf("%s user='%s' token='%s'\n",
					$error, $user, $token));
	}

	return $error;
}

function /* char *error */ ActivateUserSql($user, $token)
{
	global $lang;

	$uid = null;
	$now = null;
	$error = null;

	$query = sprintf("SELECT `id`, `token`, `token_type`, UTC_TIMESTAMP() as `now`, ".
			 		 "(SELECT UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(`token_expires`)) AS `expires`".
			 		 " FROM `users` WHERE `name`='%s'", $user);

	$result = mysql_query($query);

	if (!$result)
	{
		$error = mysql_error();
	}
	else
	{
		if (mysql_num_rows($result) != 1)
		{
			$error = sprintf($lang['activationfailed'], __LINE__);
		}
		else
		{
			$row = mysql_fetch_assoc($result);

			if (!$row)
			{
				$error = mysql_error();
			}
			else
			{
				$uid = $row['id'];
				$now = $row['now'];

				if ('none' == $row['token_type'] ||
					NULL == $row['token'] ||
					NULL == $row['expires'])
				{
					// Already activated, silently accept re-activation
					$token = NULL;
				}
				else
				{
					$expires = $row['expires'];

					if ($expires > 0)
					{
						$error = $lang['activationexpired'];
					}
					else
					{
						if ($token != $row['token'])
							$error = sprintf($lang['activationfailed'], __LINE__);
					}
				}
			}
		}

		mysql_free_result($result);
	}

	if (NULL == $token)
	{
		// Already activated, silently accept re-activation
	}
	else
	{
		if (!$error)
		{
				$query = "UPDATE `users`".
						 " SET `token`=NULL, `token_type`='none', `token_expires`=NULL".
						 " WHERE `id`=$uid";

				if (!mysql_query($query))
					$error = mysql_error();
		}

		if ($error)
		{
			if (!isset($token))
				$token = "";

			$max = strlen(token());

			if (strlen($token) > $max)	/* SHA-256 */
				$token = substr($token, 0, $max + 1).'...';

			if (strlen($user) > $GLOBALS['USERNAME_MAX'])
				$user = substr($user, 0, $GLOBALS['USERNAME_MAX'] + 1).'...';

			AdminMail('activation',
				sprintf("$uid:$user%s = %s token='%s'\n",
						 $now ? " ($now)" : "",
						 $error ? $error : "OK",
						 $token));
		}
		else
		{
			AdminMail('activation',
				sprintf("$uid:$user%s = OK\n",
						 $now ? " ($now)" : ""));
		}
	}

	return $error;
}

function /* char *error */ RequestPasswordToken(/* __out */ &$message)
	// __in $_POST['user']
	// __in $_POST['email']

	// __out $_GET['req']
	// __out $_GET['user']
	// __out $_GET['user']
{
	global $lang;

	$error = null;
	$message = null;

	$user  = isset($_POST['user'])  ? $_POST['user']  : (isset($_GET['user'])  ? $_GET['user']  : NULL);
	$email = isset($_POST['email']) ? $_POST['email'] : (isset($_GET['email']) ? $_GET['email'] : NULL);

	if ($user || $email)		/* else: no post, just display form */
	{
		$error = RequestPasswordTokenSql($user, $email);

		if (!$error)
		{
			$message = $lang['tokensent'];

			$_GET['req'] = 'changepw';			// Form to be displayed next
			$_GET['user'] = $_POST['user'];		// Pre-set user name in form
		}
	}

	return $error;
}

function /* char *error */ RequestPasswordTokenSql($user, $email)
{
	global $lang;

	$error = null;
	$uid = null;
	$expires = null;
	$where = null;

	if ($user)
	{
		if (strlen($user))
			$where = "`name`='$user'";
	}

	if ($email)
	{
		if (strlen($email))
		{
			if ($where)
				$where .= " AND `email`='$email'";
			else
				$where = "`email`='$email'";
		}
	}

	if (null == $where)
	{
		$error = $lang['nonempty'];
	}
	else
	{
		$query = sprintf("SELECT `id`, `name`, `email`, `token_type`, IF (ISNULL(`token_expires`), %lu, ".
				 		 "(SELECT UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(`token_expires`))) AS `expires`".
				 		 "FROM `users` WHERE $where",
						 TOKEN_LIFETIME);
	}

	if (!$error)
	{
		$result = mysql_query($query);

		if (!$result)
		{
			$error = mysql_error();
		}
		else
		{
			if (0 == mysql_num_rows($result))
			{
				if ($user)
				{
					if ($email)
						$error = $lang['nosuchuseremail'];
					else
						$error = $lang['nosuchuser'];
				}
				else
				{
					if ($email)
						$error = $lang['nosuchemail'];
					else
						$error = $lang['nosuchuseremail'];
				}
			}
			else
			{
				$row = mysql_fetch_assoc($result);

				if (!$row)
				{
					$error = mysql_error();
				}
				else
				{
					$uid = $row['id'];
					$user = $row['name'];
					$email = $row['email'];
					$type = $row['token_type'];
					$left = $row['expires'];

					if ('activation' == $type)
					{
						$error = sprintf($lang['activatefirst'], $user);
					}
					else if ('password' == $type)
					{
						if ($left < 0)
						{
							$left *= -1;
							$retry = '';

							if ($left > 3600)
							{
								$h = (($left - $left % 3600) / 3600);
								$left -= 3600 * $h;
								$retry .= "$h h";
							}

							if ($left > 60)
							{
								if (strlen($retry))
									$retry .= ' ';

								$min = (($left - $left % 60) / 60);
								$left -= 60 * $min;
								$retry .= "$min min";
							}

							if ($left > 0)
							{
								if (strlen($retry))
									$retry .= ' ';

								$retry .= "$left s";
							}

							$error = sprintf($lang['noretrybefore'], $retry);
						}
					}
				}
			}
		}

		mysql_free_result($result);
	}

	if (!$error)
	{
		$token = token();

		$query = sprintf("UPDATE `users` SET `token`='$token', `token_type`='password', `token_expires`=".
				 		 "FROM_UNIXTIME(UNIX_TIMESTAMP(UTC_TIMESTAMP()) + %lu) WHERE `id`=$uid",
				 		 TOKEN_LIFETIME);

		if (!mysql_query($query))
		{
			$error = mysql_error();
		}
		else
		{
			$expires = null;
			$query = "SELECT `token_expires` FROM `users` WHERE `id`=$uid";

			$result = mysql_query($query);

			if (!$result)
			{
				$error = mysql_error();
			}
			else
			{
				if (0 == mysql_num_rows($result))
				{
					$error = mysql_error();
				}
				else
				{
					$row = mysql_fetch_row($result);

					if (!$row)
						$error = mysql_error();
					else
						$expires = $row[0];
				}

				mysql_free_result($result);
			}
		}
	}

	if (!$error)
	{
		$client_ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ?
			$_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

		$header = sprintf(
			"From: %s <%s>\n".
			"Reply-To: %s\n".
			"Mime-Version: 1.0\n".
			"Content-type: text/plain; charset=ISO-8859-1\n".
			"Content-Transfer-Encoding: 8bit\n".
			"X-Mailer: PHP/%s\n",
			mb_encode_mimeheader(ORGANISATION, 'ISO-8859-1', 'Q'),
			ADMIN_EMAIL_FROM,
			ADMIN_EMAIL,
			phpversion());

		$body = sprintf($lang['emailpasswd'], $client_ip, $user, ORGANISATION, $token,
						   php_self(), $user, $token, $expires, ORGANISATION);

		/* http://www.outlookfaq.net/index.php?action=artikel&cat=6&id=84&artlang=de */
		$body = mb_convert_encoding($body, 'ISO-8859-1', 'UTF-8');

		if (!@mail($email, mb_encode_mimeheader($lang['subjpasswdchange'], 'ISO-8859-1', 'Q'), $body, $header))
		{
			$error = error_get_last();
			$error = $lang['mailfailed'].$error['message'];
		}
	}

	AdminMail('reqpasswdch',
		sprintf("$uid:$user%s = %s\n",
				$expires ? " (expires $expires)" : "",
				$error ? $error : "OK"));

	return $error;
}

function /* char *error */ ChangePassword($user, /* __out */ &$message)
	// __in $_POST['user']
	// __in $_POST['passwd']
	// __in $_POST['passwd-confirm']

	// __out $_GET['req']
	// __out $_GET['user']
{
	global $lang;

	$error = null;
	$message = null;

	if ((isset($_POST['user']) || $user) &&
		 isset($_POST['passwd']) &&
		 isset($_POST['passwd-confirm']))		/* else: no post, display form */
	{
		$error = ChangePasswordSql(isset($_POST['user']) ? $_POST['user'] : $user->name(),
								   isset($_POST['token']) ? $_POST['token'] : null,
								   $_POST['passwd']);

		if ($error)
		{
			if ($user)
				$_GET['req'] = 'profile';			// Form to be displayed next
		}
		else
		{
			if ($user)
			{
				$message = $lang['passwdchanged'];

				$_GET['req'] = 'profile';			// Form to be displayed next
			}
			else
			{
				$message = $lang['passwdchangedlogin'];

				$_GET['req'] = 'login';				// Form to be displayed next
				$_GET['user'] = $_POST['user'];		// Pre-set user name in form
			}
		}
	}

	return $error;
}

function /* char *error */ ChangePasswordSql($user, $token, $password)
	// __in $_POST['passwd']
	// __in $_POST['passwd-confirm']
	// __in $_COOKIE['autologin']
{
	global $lang;

	$uid = null;
	$now = null;
	$error = null;

	$query = sprintf("SELECT `id`,".
					 " (SELECT UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(`token_expires`)) AS `expires`%s".
					 " FROM `users` WHERE `name`='$user'",
					 isset($token) ? ', `token`' : '');

	$result = mysql_query($query);

	if (!$result)
	{
		$error = mysql_error();
	}
	else
	{
		if (mysql_num_rows($result) != 1)
		{
			$error = mysql_error();

			if ('' == $error)
				$error = $lang['authfailedpasswdnotch'];
		}
		else
		{
			$row = mysql_fetch_assoc($result);

			if (!$row)
			{
				$error = mysql_error();
			}
			else
			{
				$expires = $row['expires'];

				if (isset($token))
				{
					if (!isset($row['token']) /* No token has been requested! */ ||
						$token != $row['token'])
					{
						$error = $lang['authfailedpasswdnotch'];
					}
					else
					{
						// check token exiration time
						if ($expires > 0)
							$error = $lang['passwdtokenexpired'];
					}
				}

				if (!$error)
				{
					if (!PasswordConstraintMet($_POST['passwd']))
					{
						$error = PasswordHint();
					}
					else if (!PasswordsMatch($_POST['passwd'], $_POST['passwd-confirm']))
					{
						$error = $lang['passwordsmismatch'];
					}
					else
					{
						$uid = $row['id'];
						$salt = token();
						$password = hash_hmac('sha256', $password, $salt);
						$query = "UPDATE `users`".
								 " SET `salt`='$salt', `passwd`='$password',".
								 " `token`=NULL, `token_type`='none', `token_expires`=NULL".
								 " WHERE `id`=$uid";

						if (!mysql_query($query))
						{
							$error = mysql_error();
						}
						else
						{
							if (isset($_COOKIE['autologin']))
								if ($_COOKIE['autologin'])
									setcookie('hash', $password, time() + COOKIE_LIFETIME);
						}
					}
				}
			}
		}

		mysql_free_result($result);
	}

	AdminMail('passwdch',
		sprintf("$uid:$user%s = %s\n",
				$now ? " ($now)" : "",
				$error ? $error : "OK"));

	return $error;
}

function /*str*/ mysql_user_error($default)
{
	$error = mysql_error();

	$expl = explode(": ", $error, 2);
	$errno = $expl[0];

	switch ($errno)
	{
	case '400':
		$error = sprintf($lang['usernamelengthmin'], $GLOBALS['USERNAME_MIN']);
		break;

	case '401':
		$error = sprintf($lang['usernamelengthmax'], $GLOBALS['USERNAME_MAX']);
		break;

	case '402':
	case '403':
		$error = $default;
		break;
	}

	return $error;
}

function /* char *error */ UserProcessRequest(&$user, &$message)
	// __in $_GET['req']
	// __out $_GET['req']
{
	$error = null;

	if (!isset($_GET['req']))
	{
		// try autologin from cookies
		$error = LoginUserAutomatically($user);
	}
	else
	{
		switch ($_GET['req'])
		{
		case 'logout':
			LogoutUser($user);
			break;

		case 'login':
			$error = LoginUser($user);
			break;

		case 'register':
			$error = RegisterUser($message);
			break;

		case 'activate':
			$error = ActivateUser($message);
			break;

		case 'reqtok':
			$error = RequestPasswordToken($message);
			break;

		case 'changepw':
			LoginUserAutomatically($user);
			$error = ChangePassword($user, $message);
			break;

		default:
			LoginUserAutomatically($user);
		}
	}

	if (!$user)
		if (isset($_GET['req']))
			if ('profile' == $_GET['req'])
				unset($_GET['req']);

	return $error;
}

?>
