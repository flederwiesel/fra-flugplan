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
require_once "classes/sql-xpdo.php";

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

function LoginUserAutomatically($db, /* __out */ &$user)
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
		$hash = $_COOKIE['hash'];
		$error = LoginUserSql($db, true, $_COOKIE['userID'], $hash, $user);

		if ($error)
		{
			setcookie('hash', null, 0);
		}
		else
		{
			$expires = isset($_COOKIE['autologin']) ? time() + COOKIE_LIFETIME : 0;

			setcookie('userID',    $user->id(),       $expires);
			setcookie('hash',      $hash,             $expires);
			setcookie('autologin', true,              $expires);
		}
	}

	return $error;
}

function /*bool*/ LoginUser($db, /* __out */ &$user)
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
			$hash = $_POST['passwd'];
			$error = LoginUserSql($db, false, $_POST['user'], $hash, $user);

			if ($error)
			{
				setcookie('hash', null, 0);
			}
			else
			{
				$expires = isset($_POST['autologin']) ? time() + COOKIE_LIFETIME : 0;

				setcookie('userID',    $user->id(),       $expires);
				setcookie('hash',      $hash,             $expires);
				setcookie('autologin', true,              $expires);

				unset($_GET['req']);
			}
		}
	}

	return $error;
}

function /* char *error */ LoginUserSql($db, $byid, $id, /* __in __out */ &$password, /* __out */ &$user)
{
	global $lang;

	$user = null;
	$error = null;

	$query = <<<SQL
		/*[Q1]*/
		SELECT `%s`, `passwd`, `salt`, `email`, `timezone`, `language`,
			`token_type`, `tm-`, `tm+`, `tt-`, `tt+`,
			`notification-from`, `notification-until`, `notification-timefmt`
		FROM `users`
		WHERE `%s`=?
SQL;

	$query = sprintf($query,
					 $byid ? 'name' : 'id',
					 $byid ? 'id' : 'name');

	$st = $db->prepare($query);

	if (!$st)
	{
		$error = sprintf($lang['dberror'], $db->errorCode());
	}
	else
	{
		if (!$st->execute(array($id)))
		{
			$error = sprintf($lang['dberror'], $st->errorCode());
		}
		else
		{
			if ($st->rowCount() != 1)
			{
				if ('00000' == $st->errorCode())
					$error = $lang['authfailed'];
				else
					$error = sprintf($lang['dberror'], $st->errorCode());
			}
			else
			{
				// TODO: fetch as User class object
				$row = $st->fetchObject();

				if (!$row)
				{
					$error = sprintf($lang['dberror'], $st->errorCode());
				}
				else
				{
					if (isset($row->token_type))
					{
						if ('activation' == $row->token_type)
							$error = $lang['activationrequired'];
					}

					if (!$error)
					{
						$hash = $byid ? $password : hash_hmac('sha256', $password, $row->salt);

						if ($row->passwd != $hash)
						{
							$error = $lang['authfailed'];
						}
						else
						{
							$password = $hash;

							if ($byid)
							{
								$name = $row->name;
							}
							else
							{
								$name = $id;
								$id = (int)$row->id;
							}
						}
					}
				}
			}
		}
	}

	if (!$error)
	{
		$query = <<<SQL
			/*[Q2]*/
			SELECT `groups`.`name`
			FROM `membership`
			INNER JOIN `groups` ON `membership`.`group` = `groups`.`id`
			WHERE `user`=?
SQL;

		$st = $db->prepare($query);

		if (!$st)
		{
			$error = sprintf($lang['dberror'], $db->errorCode());
		}
		else
		{
			if (!$st->execute(array($id)))
			{
				$error = sprintf($lang['dberror'], $st->errorCode());
			}
			else
			{
				if (0 == $st->rowCount())
				{
					$error = $lang['nopermission'];
				}
				else
				{
					while ($group = $st->fetchColumn())
						$groups[] = $group;

					if (!$groups)
					{
						$error = sprintf($lang['dberror'], $st->errorCode());
					}
					else
					{
						$user = new User($id, $name, $row->email, $row->timezone, $row->language, $groups);

						if ($user)
						{
							$user->opt('tm-', $row->{'tm-'});
							$user->opt('tm+', $row->{'tm+'});
							$user->opt('tt-', $row->{'tt-'});
							$user->opt('tt+', $row->{'tt+'});
							$user->opt('notification-from', $row->{'notification-from'});
							$user->opt('notification-until', $row->{'notification-until'});
							$user->opt('notification-timefmt', $row->{'notification-timefmt'});

							$query = sprintf(<<<SQL
								/*[Q3]*/
								UPDATE `users`
								SET `last login`='%s', `token`=NULL, `token_type`='none', `token_expires`=NULL
								WHERE `id`=%u
SQL
								, strftime('%Y-%m-%d %H:%M:%S'), $user->id());

							$db->exec($query);
						}
					}
				}
			}
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
		$stopforumspam = sprintf("https://${stopforumspam}api.stopforumspam.org/api?".
								 "f=json&ip=%s&email=%s&username=%s",
								 $ipaddr['real'], urlencode($email), urlencode($user));

		$error = $curl->exec($stopforumspam, $json, 5);

		if ($error)
		{
			$message = $lang['spamcheckfailed'];
		}
		else
		{
			$json = (object)json_decode($json);

			if ($json)
			{
				if ($json->success)
				{
					if (!isset($json->username->confidence))
					{
						$json->username->appears = 0;
					}
					else
					{
						if ($json->username->confidence < 96.0)
							$json->username->appears = 0;
					}

					if (!isset($json->email->confidence))
					{
						$json->email->appears = 0;
					}
					else
					{
						if ($json->email->confidence < 92.0 &&
							$json->ip->confidence    < 92.0)
						{
							$json->username->appears = 0;
							$json->email->appears = 0;
							$json->ip->appears = 0;
						}
					}

					if (!isset($json->ip->confidence))
					{
						$json->ip->appears = 0;
					}
					else
					{
						if ($json->ip->confidence < 98.0)
							$json->ip->appears = 0;
					}

					if ($json->username->appears ||
						$json->email->appears    ||
						$json->ip->appears)
					{
						AdminMail('registration',
							sprintf("\nSuspected spam:\n%s=%s\n%s=%s\n%s=%s\n".
									"\nReport: https://www.stopforumspam.com/add.php?".
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
								 ($json->email->appears || $json->ip->appears) :
								 ($json->email->appears && $json->ip->appears);

						$message = sprintf($plural ? $lang['spam:plur'] : $lang['spam:sing'], $insert);
					}
				}
			}
		}

		unset($curl);
	}

	return $message;
}

function /* char *error */ RegisterUser($db, /* __out */ &$message)
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
		$_SESSION['ipaddr'] = isset($_SERVER['HTTP_X_REAL_IP']) ?
			$_SERVER['HTTP_X_REAL_IP'] :
			$_SERVER['REMOTE_ADDR'];
	}

	if (isset($_POST['user']) &&
		isset($_POST['email']))		/* else: no request, just display form */
	{
		if (strlen($_POST['user']) < $GLOBALS['USERNAME_MIN'])
		{
			$error = sprintf($lang['usernamelengthmin'], $GLOBALS['USERNAME_MIN']);
		}
		else if (strlen($_POST['user']) > $GLOBALS['USERNAME_MAX'])
		{
			$error = sprintf($lang['usernamelengthmax'], $GLOBALS['USERNAME_MAX']);
		}
		else if (preg_match('/^[ \t\r\n\v]*$/', $_POST['user']))
		{
			$error = $lang['usernameinvalid'];
			$_POST['user'] = '';
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

				if (preg_match('/^[ \t\r\n\v]*$/', $_POST['email']))
					$_POST['email'] = '';
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
							$ipaddr['fwd'] = $_SERVER['HTTP_X_FORWARDED_FOR'];

						$ipaddr['real'] = isset($_SESSION['ipaddr']) ? $_SESSION['ipaddr'] : NULL;

						if (!$ipaddr['real'])
						{
							if (isset($_SERVER['HTTP_X_REAL_IP']))
								$ipaddr['real'] = $_SERVER['HTTP_X_REAL_IP'];
							else if (isset($_SERVER['REMOTE_ADDR']))
								$ipaddr['real'] = $_SERVER['REMOTE_ADDR'];
							else
								$ipaddr['real'] = '<unknown>';
						}

						$ipaddr['fwd'] = str_replace('::1', '127.0.0.1', $ipaddr['fwd']);
						$ipaddr['real'] = str_replace('::1', '127.0.0.1', $ipaddr['real']);

						if (!SuspectedSpam($_POST['user'], $_POST['email'], $ipaddr, $error))
						{
							if (!$error)
							{
								$error = RegisterUserSql($db, $_POST['user'], $_POST['email'], $_POST['passwd'],
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

	return $error;
}

function /* char *error */ RegisterUserSql($db, $user, $email, $password, $ipaddr, $language)
	// __in  $_SERVER['REMOTE_ADDR']
	// __out $_SESSION['lang']
{
	global $lang;

	$uid = null;
	$error = null;
	$expires = null;

	// TODO: join name/email queries
	$query = "/*[Q4]*/ SELECT `id` FROM `users` WHERE `name`=?";
	$st = $db->prepare($query);

	if (!$st)
	{
		$error = sprintf($lang['dberror'], $db->errorCode());
	}
	else
	{
		if (!$st->execute(array($user)))
		{
			$error = sprintf($lang['dberror'], $st->errorCode());
		}
		else
		{
			if ($st->rowCount() != 0)
				$error = $lang['userexists'];

			if (!$error)
			{
				$query = "/*[Q5]*/ SELECT `id` FROM `users` WHERE `email`=?";
				$st = $db->prepare($query);

				if (!$st)
				{
					$error = sprintf($lang['dberror'], $db->errorCode());
				}
				else
				{
					if (!$st->execute(array($email)))
					{
						$error = sprintf($lang['dberror'], $st->errorCode());
					}
					else
					{
						if ($st->rowCount() != 0)
							$error = $lang['emailexists'];
					}
				}
			}
		}
	}

	if (!$error)
	{
		$salt = token();
		$token = token();
		$password = hash_hmac('sha256', $password, $salt);

		if (!$db->beginTransaction())
		{
			$error = $lang['regfailed'];
		}
		else
		{
			$query = <<<SQL
				/*[Q6]*/
				INSERT INTO
				`users`(
					`name`, `email`, `passwd`, `salt`, `language`,
					`token`, `token_type`,
					`token_expires`, `ip`
				)
				VALUES(
					:user, :email, :password, :salt, :language,
					:token, 'activation',
					FROM_UNIXTIME(UNIX_TIMESTAMP(UTC_TIMESTAMP()) + %lu), '%s'
				);
SQL;

			$query = sprintf($query, TOKEN_LIFETIME,
							 isset($_SERVER['HTTP_X_REAL_IP']) ?
								$_SERVER['HTTP_X_REAL_IP'] :
								$_SERVER['REMOTE_ADDR']);

			$st = $db->prepare($query);

			if (!$st)
			{
				$error = sprintf($lang['dberror'], $db->errorCode());
			}
			else
			{
				$st->bindValue(':user', $user);
				$st->bindValue(':email', $email);
				$st->bindValue(':password', $password);
				$st->bindValue(':salt', $salt);
				$st->bindValue(':token', $token);
				$st->bindValue(':language', $language);

				if (!$st->execute())
				{
					$error = sprintf($lang['dberror'], $st->errorCode());
				}
				else
				{
					$query = <<<SQL
						/*[Q7]*/
						SELECT `id`,`token_expires`
						FROM `users`
						WHERE `id`=LAST_INSERT_ID()
SQL;

					$st = $db->query($query);

					if (!$st)
					{
						$error = sprintf($lang['dberror'], $db->errorCode());
					}
					else
					{
						if (0 == $st->rowCount())
						{
							$error = $lang['regfailed'];
						}
						else
						{
							$row = $st->fetchObject();
							//&& $_POST['timezone'] -> localtime($expires)

							if (!$row)
							{
								$error = $st->errorCode();
							}
							else
							{
								$uid = (int)$row->id;
								$expires = $row->token_expires;
								$_SESSION['lang'] = $language;
							}
						}
					}

					if (!$error)
					{
						$query = <<<SQL
							/*[Q8]*/
							INSERT INTO `membership`(`user`, `group`)
							VALUES(
								LAST_INSERT_ID(),
								(SELECT `id` FROM `groups` WHERE `name`='users')
							);
SQL;

						if (!$db->exec($query))
							$error = sprintf($lang['dberror'], $db->errorCode());
					}
				}
			}
		}

		if ($error)
		{
			$db->rollBack();
		}
		else
		{
			if (!$db->commit())
				$error = $db->errorCode();
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

function /* char *error */ ActivateUser($db, /* __out */ &$message)
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
			$error = ActivateUserSql($db, $user, $token);

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
		if (!isset($token))
		{
			$token = NULL;
		}
		else
		{
			define('SHA_256_LEN', 64);

			if (strlen($token) > SHA_256_LEN)
				$token = substr($token, 0, SHA_256_LEN + 1).'...';
		}

		if (strlen($user) > $GLOBALS['USERNAME_MAX'])
			$user = substr($user, 0, $GLOBALS['USERNAME_MAX'] + 1).'...';

		AdminMail('activation',
			sprintf("%s user='%s' token='%s'\n",
					$error, $user, $token));
	}

	return $error;
}

function /* char *error */ ActivateUserSql($db, $user, $token)
{
	global $lang;

	$uid = null;
	$now = null;
	$error = null;

	$query = <<<SQL
		/*[Q9]*/
		SELECT `id`, `token`, `token_type`, UTC_TIMESTAMP() as `now`,
			(SELECT UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(`token_expires`)) AS `expires`
		FROM `users` WHERE `name`=?
SQL;

	$st = $db->prepare($query);

	if (!$st)
	{
		$error = sprintf($lang['dberror'], $db->errorCode());
	}
	else
	{
		if (!$st->execute(array($user)))
		{
			$error = sprintf($lang['dberror'], $st->errorCode());
		}
		else
		{
			if ($st->rowCount() != 1)
			{
				$error = sprintf($lang['activationfailed'], __LINE__);
			}
			else
			{
				$row = $st->fetchObject();

				if (!$row)
				{
					$error = sprintf($lang['dberror'], $st->errorCode());
				}
				else
				{
					$uid = (int)$row->id;
					$now = $row->now;

					if ('none' == $row->token_type ||
						NULL == $row->token ||
						NULL == $row->expires)
					{
						// Already activated, silently accept re-activation
						$token = NULL;
					}
					else
					{
						$expires = $row->expires;

						if ($expires > 0)
						{
							$error = $lang['activationexpired'];
						}
						else
						{
							if ($token != $row->token)
								$error = sprintf($lang['activationfailed'], __LINE__);
						}
					}
				}
			}
		}
	}

	if (NULL == $token)
	{
		// Already activated, silently accept re-activation
	}
	else
	{
		if (!$error)
		{
			$query = <<<SQL
				/*[Q10]*/
				UPDATE `users`
				SET `token`=NULL, `token_type`='none', `token_expires`=NULL
				WHERE `id`=$uid
SQL;

			if (!$db->exec($query))
				$error = $db->errorCode();
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

function /* char *error */ RequestPasswordToken($db, /* __out */ &$message)
	// __in $_POST['user']
	// __in $_POST['email']

	// __out $_GET['req']
	// __out $_GET['user']
	// __out $_GET['user']
{
	global $lang;

	$error = NULL;
	$message = NULL;

	// ToDo: trim($_POST[]) and issue warning, if trimmed
	$user  = isset($_POST['user'])  ? $_POST['user']  : (isset($_GET['user'])  ? $_GET['user']  : NULL);
	$email = isset($_POST['email']) ? $_POST['email'] : (isset($_GET['email']) ? $_GET['email'] : NULL);

	if ($user || $email)	/* else: no post, just display form */
	{
		/* Obviously, we have to check for strings containing only of whitespace */
		if (preg_match('/^[ \t\r\n\v]+$/', $user))
		{
			$error = $lang['usernameinvalid'];
			$_POST['user'] = '';
		}
		else if (preg_match('/^[ \t\r\n\v]+$/', $email))
		{
			$error = $lang['emailinvalid'];
			$_POST['email'] = '';
		}
		else
		{
			$error = RequestPasswordTokenSql($db, $user, $email);
		}

		if (!$error)
		{
			$message = $lang['tokensent'];

			$_GET['req'] = 'changepw';			// Form to be displayed next
			$_GET['user'] = $_POST['user'];		// Pre-set user name in form
		}
	}

	return $error;
}

function /* char *error */ RequestPasswordTokenSql($db, $user, $email)
{
	global $lang;

	$error = null;
	$uid = null;
	$expires = null;
	$where = null;

	if ($user)
	{
		if (0 == strlen($user))
			$user =NULL;
		else
			$where = "`name`=:user";
	}

	if ($email)
	{
		if (0 == strlen($email))
		{
			$email = NULL;
		}
		else
		{
			if ($where)
				$where .= " AND `email`=:email";
			else
				$where = "`email`=:email";
		}
	}

	if (null == $where)
	{
		$error = $lang['nonempty'];
	}
	else
	{
		$query = <<<SQL
			/*[Q11]*/
			SELECT `id`, `name`, `email`, `token_type`,
				IF (ISNULL(`token_expires`), %lu,
					(SELECT UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(`token_expires`))) AS `expires`
			FROM `users` WHERE $where
SQL;

		$query = sprintf($query, TOKEN_LIFETIME);
	}

	if (!$error)
	{
		$st = $db->prepare($query);

		if (!$st)
		{
			$error = sprintf($lang['dberror'], $db->errorCode());
		}
		else
		{
			if ($user)
				$st->bindValue(':user', $user);

			if ($email)
				$st->bindValue(':email', $email);

			if (!$st->execute())
			{
				$error = sprintf($lang['dberror'], $st->errorCode());
			}
			else
			{
				if (0 == $st->rowCount())
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
					$row = $st->fetchObject();

					if (!$row)
					{
						$error = sprintf($lang['dberror'], $st->errorCode());
					}
					else
					{
						$uid = (int)$row->id;
						$user = $row->name;
						$email = $row->email;
						$type = $row->token_type;
						$left = $row->expires;

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
		}
	}

	if (!$error)
	{
		$token = token();

		$query = <<<SQL
			/*[Q12]*/
			UPDATE `users`
			SET `token`='$token', `token_type`='password',
				`token_expires`=FROM_UNIXTIME(UNIX_TIMESTAMP(UTC_TIMESTAMP()) + %lu)
			WHERE `id`=$uid
SQL;

		$query = sprintf($query, TOKEN_LIFETIME);

		if (!$db->exec($query))
		{
			$error = sprintf($lang['dberror'], $db->errorCode());
		}
		else
		{
			$expires = null;
			$query = "/*[Q13]*/ SELECT `token_expires` FROM `users` WHERE `id`=$uid";

			$st = $db->query($query);

			if (!$st)
			{
				$error = sprintf($lang['dberror'], $db->errorCode());
			}
			else
			{
				if (0 == $st->rowCount())
				{
					$error = $st->errorCode();
				}
				else
				{
					$expires = $st->fetchColumn();

					if (!$expires)
						$error = sprintf($lang['dberror'], $st->errorCode());
				}
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

function /* char *error */ ChangePassword($db, $user, /* __out */ &$message)
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
		$error = ChangePasswordSql($db,
								   isset($_POST['user']) ? $_POST['user'] : $user->name(),
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

function /* char *error */ ChangePasswordSql($db, $user, $token, $password)
	// __in $_POST['passwd']
	// __in $_POST['passwd-confirm']
	// __in $_COOKIE['autologin']
{
	global $lang;

	$uid = null;
	$now = null;
	$error = null;
	$column = isset($token) ? ', `token`' : '';

	$query = <<<SQL
		/*[Q14]*/
		SELECT `id`,
			(SELECT UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(`token_expires`)) AS `expires`$column
		FROM `users` WHERE `name`=?
SQL;

	$st = $db->prepare($query);

	if (!$st)
	{
		$error = sprintf($lang['dberror'], $db->errorCode());
	}
	else
	{
		if (!$st->execute(array($user)))
		{
			$error = sprintf($lang['dberror'], $st->errorCode());
		}
		else
		{
			if ($st->rowCount() != 1)
			{
				if ('00000' == $st->errorCode())
					$error = $lang['authfailedpasswdnotch'];
				else
					$error = sprintf($lang['dberror'], $st->errorCode());
			}
			else
			{
				$row = $st->fetchObject();

				if (!$row)
				{
					$error = sprintf($lang['dberror'], $st->errorCode());
				}
				else
				{
					$expires = $row->expires;

					if (isset($token))
					{
						if (!isset($row->token) /* No token has been requested! */ ||
							$token != $row->token)
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
							$uid = (int)$row->id;
							$salt = token();
							$password = hash_hmac('sha256', $password, $salt);

							$query = <<<SQL
								/*[Q15]*/
								UPDATE `users`
								SET `salt`='$salt', `passwd`='$password',
									`token`=NULL, `token_type`='none', `token_expires`=NULL
								WHERE `id`=$uid
SQL;

							if (!$db->exec($query))
							{
								$error = sprintf($lang['dberror'], $db->errorCode());
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
		}
	}

	AdminMail('passwdch',
		sprintf("$uid:$user%s = %s\n",
				$now ? " ($now)" : "",
				$error ? $error : "OK"));

	return $error;
}

function /* char *error */ UserProcessRequest($db, &$user, &$message)
	// __in $_GET['req']
	// __out $_GET['req']
{
	$error = null;

	if (!isset($_GET['req']))
	{
		// try autologin from cookies
		$error = LoginUserAutomatically($db, $user);
	}
	else
	{
		switch ($_GET['req'])
		{
		case 'logout':
			LogoutUser($user);
			break;

		case 'login':
			$error = LoginUser($db, $user);
			break;

		case 'register':
			$error = RegisterUser($db, $message);
			break;

		case 'activate':
			$error = ActivateUser($db, $message);
			break;

		case 'reqtok':
			$error = RequestPasswordToken($db, $message);
			break;

		case 'changepw':
			LoginUserAutomatically($db, $user);
			$error = ChangePassword($db, $user, $message);
			break;

		default:
			LoginUserAutomatically($db, $user);
		}
	}

	if (!$user)
		if (isset($_GET['req']))
			if ('profile' == $_GET['req'])
				unset($_GET['req']);

	return $error;
}

?>
