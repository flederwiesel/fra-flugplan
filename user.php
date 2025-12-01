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

function /* char* */ token()
{
	$version = explode('.', phpversion());

	if ('5' == $version[0])
		return hash('sha256', mcrypt_create_iv(32));
	else
		return hash('sha256', random_bytes(32));
}

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
	global $STRINGS;

	$sep = 0;
	$text = sprintf($STRINGS['passwd-min'], $GLOBALS['PASSWORD_MIN']);

	if ($GLOBALS['PASSWORD_REQUIRES_LETTER'])
	{
		$text .= sprintf($STRINGS['passwd-letter']);
		$sep++;
	}

	if ($GLOBALS['PASSWORD_REQUIRES_UPPER'])
	{
		$text .= sprintf($STRINGS['passwd-upper']);
		$sep++;
	}

	if ($GLOBALS['PASSWORD_REQUIRES_LOWER'])
	{
		$text .= sprintf($STRINGS['passwd-lower']);
		$sep++;
	}

	if ($GLOBALS['PASSWORD_REQUIRES_DIGIT'])
	{
		$text .= sprintf($STRINGS['passwd-digit']);
		$sep++;
	}

	if (strlen($GLOBALS['PASSWORD_REQUIRES_SPECIAL']))
	{
		$text .= sprintf($STRINGS['passwd-special'], str_replace('%', '%%', $specials));
		$sep++;
	}

	$separators = [];

	if ($sep > 0)
	{
		if ($sep > 1)
			$separators[] = $STRINGS['passwd-separator-0'];

		for ($i = 1; $i < $sep - 1; $i++)
			$separators[] = ',';

		$separators[] = ' '.$STRINGS['and'];
	}

	$text = vsprintf($text, $separators);
	$text .= $sep ? $STRINGS['passwd-postfix-N'] : $STRINGS['passwd-postfix-0'];

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
	private $options = [];
	private $groups = [];

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

	public function language($lang = null)
	{
		if (null == $lang)
			return $this->lang;
		else
			$this->lang = $lang;

		return null;
	}

	public function IsMemberOf($group)
	{
		return in_array($group, $this->groups);
	}

	public function opt($name, $value = null)
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

		return null;
	}

	// Wrapper to set cookie using "secure", "httponly" and "samesite".
	// This should do for the whole site.
	// As we want to phase out cookies set at subdir "/fra-flugplan"
	// (which will be an internal redirect in the future), this function
	// comes in quite handy to update cookie paths as well. :)
	public static function setcookie(string $name, ?string $value = "", int $expires = 0)
	{
		// Explicitly set cookie at "/"
		\setcookie($name, $value, [
			"path" => "/",
			"expires" => $expires,
			"domain" => $_SERVER["SERVER_NAME"],
			"secure" => true,
			"httponly" => true,
			"samesite" => "lax",
		]);

		// Delete cookie at "/fra-flugplan"
		\setcookie($name, $value, [
			"path" => "/fra-flugplan",
			"expires" => 0
		]);
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
	User::setcookie("userID",    0,     0);
	User::setcookie("hash",      null,  0);
	User::setcookie("autologin", false, 0);

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
			User::setcookie("hash", null, 0);
		}
		else
		{
			$expires = isset($_COOKIE['autologin']) ? time() + COOKIE_LIFETIME : 0;

			User::setcookie("userID",    $user->id(), $expires);
			User::setcookie("hash",      $hash,       $expires);
			User::setcookie("autologin", true,        $expires);
		}
	}

	return $error;
}

function /*bool*/ LoginUser($db, /* __out */ &$user)
	// __out $_POST['user']
	// __out $_POST['passwd']
{
	global $STRINGS;

	$error = null;

	if (isset($_POST['user']))		/* else: no post, just display form */
	{
		if (!isset($_POST['passwd']))
		{
			$error = $STRINGS['authfailed'];
		}
		else
		{
			$hash = $_POST['passwd'];
			$error = LoginUserSql($db, false, $_POST['user'], $hash, $user);

			if ($error)
			{
				User::setcookie("hash", null, 0);
			}
			else
			{
				$expires = isset($_POST['autologin']) ? time() + COOKIE_LIFETIME : 0;

				User::setcookie("userID",    $user->id(), $expires);
				User::setcookie("hash",      $hash,       $expires);
				User::setcookie("autologin", true,        $expires);

				unset($_GET['req']);
			}
		}
	}

	return $error;
}

function /* char *error */ LoginUserSql($db, $byid, $id, /* __in __out */ &$password, /* __out */ &$user)
{
	global $STRINGS;

	$user = null;
	$error = null;

	try
	{
		$query = <<<SQL
			/*[Q1]*/
			SELECT `%s`, `passwd`, `salt`, `email`, `timezone`, `language`,
				`token_type`, `tm-`, `tm+`, `tt-`, `tt+`,
				`notification-from`, `notification-until`, `notification-timefmt`,
				`photodb`
			FROM `users`
			WHERE `%s` = ?
			SQL;

		$query = sprintf($query,
						 $byid ? 'name' : 'id',
						 $byid ? 'id' : 'name');

		$st = $db->prepare($query);

		$st->execute([$id]);

		if ($st->rowCount() != 1)
		{
			$error = $STRINGS['authfailed'];
		}
		else
		{
			// TODO: fetch as User class object
			$row = $st->fetchObject();

			if (isset($row->token_type))
			{
				if ('activation' == $row->token_type)
					$error = $STRINGS['activationrequired'];
			}

			if (!$error)
			{
				$hash = $byid ? $password : hash_hmac('sha256', $password, $row->salt);

				if ($row->passwd != $hash)
				{
					$error = $STRINGS['authfailed'];
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

		if (!$error)
		{
			$st = $db->prepare(<<<SQL
				/*[Q2]*/
				SELECT `groups`.`name`
				FROM `membership`
				INNER JOIN `groups` ON `membership`.`group` = `groups`.`id`
				WHERE `user` = ?
				SQL
			);

			$st->execute([$id]);

			if (0 == $st->rowCount())
			{
				$error = $STRINGS['nopermission'];
			}
			else
			{
				while ($group = $st->fetchColumn())
					$groups[] = $group;

				if (!$groups)
				{
					$error = $STRINGS['nopermission'];
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
						$user->opt('photodb', $row->{'photodb'});

						$st = $db->prepare(<<<SQL
							/*[Q3]*/
							UPDATE `users`
							SET
								`last login` = :ll,
								`token` = NULL,
								`token_type` = 'none',
								`token_expires` = NULL
							WHERE
								`id` = :id
							SQL
						);

						$st->execute([
							"id" => $user->id(),
							"ll" => strftime('%Y-%m-%d %H:%M:%S'),
						]);
					}
				}
			}
		}
	}
	catch (PDOException $ex)
	{
		$error = PDOErrorInfo($ex, $STRINGS['dberror']);
	}

	return $error;
}

/* Check for forum spam and provide hrefs for reporting */
function /* bool */ SuspectedSpam(/* __in */ $user,
								 /* __in */ $email,
								 /* __in */ $ipaddr /* ['real','fwd'] */,
								 /* __out */ /* char *error */ &$message)
{
	global $STRINGS;

 	$suspicion = null;
	$curl = new curl();

	if ($curl)
	{
		/* We need to save 'stopforumspam' in the session, as the POST
		 will not contain the value initially set in the GET... */
		$stopforumspam = isset($_SESSION['stopforumspam']) ? urldecode("$_SESSION[stopforumspam]/") : null;
		$stopforumspam = "https://${stopforumspam}api.stopforumspam.org/api?".
						 "f=json".($ipaddr ? "&ip=$ipaddr" : "").
						 "&email=".urlencode($email).
						 "&username=".urlencode($user);

		$error = $curl->exec($stopforumspam, $json, 5);

		if ($error)
		{
			$message = $STRINGS['spamcheckfailed'];
		}
		else
		{
			$spamchk = (object)json_decode($json);

			if ($spamchk)
			{
				if ($spamchk->success)
				{
					if (!isset($spamchk->ip->confidence))
					{
						$spamchk->ip->confidence = 0;
						$spamchk->ip->appears = 0;
					}
					else
					{
						if ($spamchk->ip->confidence < 98.0)
							$spamchk->ip->appears = 0;
					}

					if (!isset($spamchk->username->confidence))
					{
						$spamchk->username->appears = 0;
					}
					else
					{
						if ($spamchk->username->confidence < 96.0)
							$spamchk->username->appears = 0;
					}

					if (!isset($spamchk->email->confidence))
					{
						$spamchk->email->appears = 0;
					}
					else
					{
						if ($spamchk->email->confidence < 92.0 &&
							$spamchk->ip->confidence    < 92.0)
						{
							$spamchk->username->appears = 0;
							$spamchk->email->appears = 0;
							$spamchk->ip->appears = 0;
						}
					}

					if ($spamchk->username->appears ||
						$spamchk->email->appears    ||
						$spamchk->ip->appears)
					{
						$stopforumspam =
							sprintf("https://www.stopforumspam.com/add.php?".
									"api_key=nrt20iomfc34sz&ip_addr=%s&email=%s&username=%s".
									"&evidence=Automated%%20registration%%2e",
									$ipaddr, urlencode($email), urlencode($user));

						$curl->exec($stopforumspam, $unused, 5);

						$suspicion = [];

						if ($spamchk->username->appears)
							$suspicion[0] = $STRINGS['username'];

						if ($spamchk->email->appears)
							$suspicion[1] = $STRINGS['emailaddress'];

						if ($spamchk->ip->appears)
							$suspicion[2] = $STRINGS['ipaddress'];

						/* Join suspicions to string, separated with command and "and" */
						$last  = array_slice($suspicion, -1);
						$first = join(', ', array_slice($suspicion, 0, -1));
						$both  = array_filter(array_merge([$first], $last), 'strlen');
						$insert = join(" $STRINGS[and] ", $both);

						$plural = $spamchk->username->appears ?
								 ($spamchk->email->appears || $spamchk->ip->appears) :
								 ($spamchk->email->appears && $spamchk->ip->appears);

						$message = sprintf($plural ? $STRINGS['spam:plur'] : $STRINGS['spam:sing'], $insert);
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
	global $STRINGS;

	$error = null;
	$message = null;

	/* We need to save 'stopforumspam' in the session, as the POST
	 will not contain the value initially set in the GET... */
	if (isset($_GET['stopforumspam']))
		$_SESSION['stopforumspam'] = $_GET['stopforumspam'];

	// Get remote IP address.
	$ipaddr = null;
	// First try proxy
	if (isset($_SERVER['HTTP_X_REAL_IP']))
	{
		if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/',
					   $_SERVER['HTTP_X_REAL_IP']))
			$ipaddr = $_SERVER['HTTP_X_REAL_IP'];
	}
	if (!$ipaddr && isset($_SERVER['HTTP_X_FORWARDED_FOR']))
	{
		if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/',
					   $_SERVER['HTTP_X_FORWARDED_FOR'], $m))
			$ipaddr = $m[0];
	}

	if (!$ipaddr)
		$ipaddr = $_SERVER['REMOTE_ADDR'];

	if (isset($_POST['user']) &&
		isset($_POST['email']))		/* else: no request, just display form */
	{
		if (strlen($_POST['user']) < $GLOBALS['USERNAME_MIN'])
		{
			$error = sprintf($STRINGS['usernamelengthmin'], $GLOBALS['USERNAME_MIN']);
		}
		else if (strlen($_POST['user']) > $GLOBALS['USERNAME_MAX'])
		{
			$error = sprintf($STRINGS['usernamelengthmax'], $GLOBALS['USERNAME_MAX']);
		}
		else if (preg_match('/^[ \t\r\n\v]*$/', $_POST['user']))
		{
			$error = $STRINGS['usernameinvalid'];
			$_POST['user'] = '';
		}
		else
		{
			if (strlen($_POST['email']) > $GLOBALS['EMAIL_MAX'])
			{
				$error = sprintf($STRINGS['emailinvalid']);
			}
			else if (preg_match('/^([A-Z0-9._%+-]+)@'.
								'([A-Z0-9-]+\.)*([A-Z0-9-]{2,})\.'.
								'[A-Z]{2,6}$/i', $_POST['email'], $match) != 1)
			{
				$error = sprintf($STRINGS['emailinvalid']);

				if (preg_match('/^[ \t\r\n\v]*$/', $_POST['email']))
					$_POST['email'] = '';
			}
			else
			{
				for ($m = 1; $m < count($match); $m++)
				{
					if (strlen($match[$m]) > 1024)
					{
						$error = sprintf($STRINGS['emailinvalid']);
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
						$error = $STRINGS['passwordsmismatch'];
					}
					else
					{
						if (!SuspectedSpam($_POST['user'], $_POST['email'], $ipaddr, $error))
						{
							if (!$error)
							{
								$error = RegisterUserSql($db, $_POST['user'], $_POST['email'], $_POST['passwd'],
														 $ipaddr,
														 isset($_POST['lang']) ? $_POST['lang'] :
														 (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en'));

								if (!$error)
								{
									$message = $STRINGS['regsuccess'];

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
	global $STRINGS;

	$uid = null;
	$error = null;
	$expires = null;

	// TODO: join name/email queries
	try
	{
		$st = $db->prepare("/*[Q4]*/ SELECT `id` FROM `users` WHERE `name` = ?");

		$st->execute([$user]);

		if ($st->rowCount() != 0)
		{
			$error = $STRINGS['userexists'];
		}
		else
		{
			$st = $db->prepare("/*[Q5]*/ SELECT `id` FROM `users` WHERE `email` = ?");

			$st->execute([$email]);

			if ($st->rowCount() != 0)
				$error = $STRINGS['emailexists'];
		}

		if (!$error)
		{
			$salt = token();
			$token = token();
			$expires = time() + TOKEN_LIFETIME;
			$password = hash_hmac('sha256', $password, $salt);

			$st = $db->prepare(<<<SQL
				/*[Q6]*/
				INSERT INTO `users`(
					`name`,
					`email`,
					`passwd`,
					`salt`,
					`language`,
					`token`,
					`token_type`,
					`token_expires`,
					`ip`
				)
				VALUES(
					:user,
					:email,
					:password,
					:salt,
					:language,
					:token,
					'activation',
					FROM_UNIXTIME(:expires),
					:ipaddr
				);
				SQL
			);

			$st->execute([
				"user" => $user,
				"email" => $email,
				"password" => $password,
				"salt" => $salt,
				"token" => $token,
				"language" => $language,
				"ipaddr" => $ipaddr,
				"expires" => $expires,
			]);

			$uid = (int)$db->lastInsertId();

			$_SESSION['lang'] = $language;

			$db->exec(<<<SQL
				/*[Q8]*/
				INSERT INTO `membership`(`user`, `group`)
				VALUES(
					LAST_INSERT_ID(),
					(SELECT `id` FROM `groups` WHERE `name`='users')
				);
				SQL
			);
		}
	}
	catch (PDOException $ex)
	{
		$error = PDOErrorInfo($ex, $STRINGS['dberror']);
	}

	$ExpDate = strftime('%Y-%m-%d %H:%M:%S', $expires);

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

		$subject = mb_encode_mimeheader($STRINGS['subjactivate'], 'ISO-8859-1', 'Q');

		$body = sprintf($STRINGS['emailactivation'],
						$ipaddr,
						$user, ORGANISATION, SITE_URL,
						$token, php_self(), $user, $token, $ExpDate, ORGANISATION);

		/* http://www.outlookfaq.net/index.php?action=artikel&cat=6&id=84&artlang=de */
		$body = mb_convert_encoding($body, 'ISO-8859-1', 'UTF-8');

		if (!@mail($email, $subject, $body, $header))
		{
			$error = error_get_last();
			$error = $STRINGS['mailfailed'].$error['message'];
		}
	}

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
	global $STRINGS;

	$error = null;
	$message = null;
	$user = null;

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
			$error = sprintf($STRINGS['activationfailed_u']);
		}
	}
	else
	{
		if (isset($_POST['user']) &&
			isset($_POST['token']))		/* else: no request, just display form */
		{
			if (!$_POST['user'])
			{
				$error = sprintf($STRINGS['activationfailed_u']);
			}
			else if (!$_POST['token'])
			{
				$error = sprintf($STRINGS['activationfailed_t']);
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
				$token = null;
				$message = $STRINGS['activationsuccess'];

				$_GET['req'] = 'login';	// Form to be displayed next
				$_GET['user'] = $user;	// Pre-set user name in form
			}
		}
	}

	if ($error)
	{
		if (!isset($token))
		{
			$token = null;
		}
		else
		{
			define('SHA_256_LEN', 64);

			if (strlen($token) > SHA_256_LEN)
				$token = substr($token, 0, SHA_256_LEN + 1).'...';
		}

		if (strlen($user) > $GLOBALS['USERNAME_MAX'])
			$user = substr($user, 0, $GLOBALS['USERNAME_MAX'] + 1).'...';
	}

	return $error;
}

function /* char *error */ ActivateUserSql($db, $user, $token)
{
	global $STRINGS;

	$uid = null;
	$now = null;
	$error = null;

	try
	{
		$st = $db->prepare(<<<SQL
			/*[Q9]*/
			SELECT
				`id`,
				`token`,
				`token_type`,
				UTC_TIMESTAMP() as `now`,
				(
					SELECT UNIX_TIMESTAMP(UTC_TIMESTAMP()) -
						UNIX_TIMESTAMP(`token_expires`)
				) AS `expires`
			FROM
				`users`
			WHERE
				`name` = ?
			SQL
		);

		$st->execute([$user]);

		if ($st->rowCount() != 1)
		{
			$error = sprintf($STRINGS['activationfailed'], __LINE__);
		}
		else
		{
			$row = $st->fetchObject();

			$uid = (int)$row->id;
			$now = $row->now;

			if ('none' == $row->token_type ||
				null == $row->token ||
				null == $row->expires)
			{
				// Already activated, silently accept re-activation
				$token = null;
			}
			else
			{
				$expires = $row->expires;

				if ($expires > 0)
				{
					$error = $STRINGS['activationexpired'];
				}
				else
				{
					if ($token != $row->token)
						$error = sprintf($STRINGS['activationfailed'], __LINE__);
				}
			}
		}

		if (!$error)
		{
			if (null == $token)
			{
				// Already activated, silently accept re-activation
			}
			else
			{
				$st = $db->prepare(<<<SQL
					/*[Q10]*/
					UPDATE `users`
					SET
						`token`=NULL,
						`token_type`='none',
						`token_expires`=NULL
					WHERE
						`id` = ?
					SQL
				);

				$st->execute([$uid]);
			}
		}
	}
	catch (PDOException $ex)
	{
		$error = PDOErrorInfo($ex, $STRINGS['dberror']);
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
	global $STRINGS;

	$error = null;
	$message = null;

	// ToDo: trim($_POST[]) and issue warning, if trimmed
	$user  = isset($_POST['user'])  ? $_POST['user']  : (isset($_GET['user'])  ? $_GET['user']  : null);
	$email = isset($_POST['email']) ? $_POST['email'] : (isset($_GET['email']) ? $_GET['email'] : null);

	if ($user || $email)	/* else: no post, just display form */
	{
		/* Obviously, we have to check for strings containing only of whitespace */
		if (preg_match('/^[ \t\r\n\v]+$/', $user))
		{
			$error = $STRINGS['usernameinvalid'];
			$_POST['user'] = '';
		}
		else if (preg_match('/^[ \t\r\n\v]+$/', $email))
		{
			$error = $STRINGS['emailinvalid'];
			$_POST['email'] = '';
		}
		else
		{
			$error = RequestPasswordTokenSql($db, $user, $email);
		}

		if (!$error)
		{
			$message = $STRINGS['tokensent'];

			$_GET['req'] = 'changepw';			// Form to be displayed next
			$_GET['user'] = $_POST['user'];		// Pre-set user name in form
		}
	}

	return $error;
}

function /* char *error */ RequestPasswordTokenSql($db, $user, $email)
{
	global $STRINGS;

	$error = null;
	$uid = null;
	$expires = null;
	$where = null;

	if ($user)
	{
		if (0 == strlen($user))
			$user = null;
		else
			$where = "`name` = :user";
	}

	if ($email)
	{
		if (0 == strlen($email))
		{
			$email = null;
		}
		else
		{
			if ($where)
				$where .= " AND `email` = :email";
			else
				$where = "`email` = :email";
		}
	}

	if (null == $where)
	{
		$error = $STRINGS['nonempty'];
	}
	else
	{
		try
		{
			$st = $db->prepare(<<<SQL
				/*[Q11]*/
				SELECT
					`id`,
					`name`,
					`email`,
					`token_type`,
					IF (
						ISNULL(`token_expires`),
						:lifetime,
						(
							SELECT UNIX_TIMESTAMP(UTC_TIMESTAMP()) -
								UNIX_TIMESTAMP(`token_expires`)
						)
					) AS `expires`
				FROM
					`users`
				WHERE
					$where
				SQL
			);

			if ($user)
				$st->bindValue('user', $user);

			if ($email)
				$st->bindValue('email', $email);

			$st->bindValue('lifetime', TOKEN_LIFETIME);

			$st->execute();

			if (0 == $st->rowCount())
			{
				if ($user)
				{
					if ($email)
						$error = $STRINGS['nosuchuseremail'];
					else
						$error = $STRINGS['nosuchuser'];
				}
				else
				{
					if ($email)
						$error = $STRINGS['nosuchemail'];
					else
						$error = $STRINGS['nosuchuseremail'];
				}
			}
			else
			{
				$row = $st->fetchObject();

				$uid = (int)$row->id;
				$user = $row->name;
				$email = $row->email;
				$type = $row->token_type;
				$left = $row->expires;

				if ('activation' == $type)
				{
					$error = sprintf($STRINGS['activatefirst'], $user);
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

						$error = sprintf($STRINGS['noretrybefore'], $retry);
					}
				}
			}

			if (!$error)
			{
				$token = token();
				$expires = null;

				$st = $db->prepare(<<<SQL
					/*[Q12]*/
					UPDATE `users`
					SET
						`token` = :token,
						`token_type`='password',
						`token_expires` = FROM_UNIXTIME(
							UNIX_TIMESTAMP(UTC_TIMESTAMP()) + :lifetime
						)
					WHERE
						`id` = :uid
					SQL
				);

				$st->execute([
					"token" => $token,
					"lifetime" => TOKEN_LIFETIME,
					"uid" => $uid,
				]);

				$st = $db->prepare(
					"/*[Q13]*/ SELECT `token_expires` FROM `users` WHERE `id` = ?"
				);

				$st->execute([$uid]);

				if (0 == $st->rowCount())
				{
					throw new PDOException("unexpected");
				}
				else
				{
					$expires = $st->fetchColumn();

					if (!$expires)
						throw new PDOException("unexpected");
				}
			}
		}
		catch (PDOException $ex)
		{
			$error = PDOErrorInfo($ex, $STRINGS['dberror']);
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

		$body = sprintf($STRINGS['emailpasswd'], $client_ip, $user, ORGANISATION, $token,
						   php_self(), $user, $token, $expires, ORGANISATION);

		/* http://www.outlookfaq.net/index.php?action=artikel&cat=6&id=84&artlang=de */
		$body = mb_convert_encoding($body, 'ISO-8859-1', 'UTF-8');

		if (!@mail($email, mb_encode_mimeheader($STRINGS['subjpasswdchange'], 'ISO-8859-1', 'Q'), $body, $header))
		{
			$error = error_get_last();
			$error = $STRINGS['mailfailed'].$error['message'];
		}
	}

	return $error;
}

function /* char *error */ ChangePassword($db, $user, /* __out */ &$message)
	// __in $_POST['user']
	// __in $_POST['passwd']
	// __in $_POST['passwd-confirm']

	// __out $_GET['req']
	// __out $_GET['user']
{
	global $STRINGS;

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
				$message = $STRINGS['passwdchanged'];

				$_GET['req'] = 'profile';			// Form to be displayed next
			}
			else
			{
				$message = $STRINGS['passwdchangedlogin'];

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
	global $STRINGS;

	$uid = null;
	$now = null;
	$error = null;
	$column = isset($token) ? ', `token`' : '';

	try
	{
		$st = $db->prepare(<<<SQL
			/*[Q14]*/
			SELECT
				`id`,
				(
					SELECT UNIX_TIMESTAMP(UTC_TIMESTAMP()) -
						UNIX_TIMESTAMP(`token_expires`)
				) AS `expires`$column
			FROM
				`users`
			WHERE
				`name` = ?
			SQL
		);

		$st->execute([$user]);

		if ($st->rowCount() != 1)
		{
			$error = $STRINGS['authfailedpasswdnotch'];
		}
		else
		{
			$row = $st->fetchObject();

			$expires = $row->expires;

			if (isset($token))
			{
				if (!isset($row->token) /* No token has been requested! */ ||
					$token != $row->token)
				{
					$error = $STRINGS['authfailedpasswdnotch'];
				}
				else
				{
					// check token exiration time
					if ($expires > 0)
						$error = $STRINGS['passwdtokenexpired'];
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
					$error = $STRINGS['passwordsmismatch'];
				}
				else
				{
					$uid = (int)$row->id;
					$salt = token();
					$password = hash_hmac('sha256', $password, $salt);

					$st = $db->prepare(<<<SQL
						/*[Q15]*/
						UPDATE `users`
						SET
							`salt` = :salt,
							`passwd` = :password,
							`token`=NULL,
							`token_type`='none',
							`token_expires`=NULL
						WHERE `id` = :uid
						SQL
					);

					$st->execute([
						"salt" => $salt,
						"password" => $password,
						"uid" => $uid,
					]);

					if (isset($_COOKIE['autologin']))
						if ($_COOKIE['autologin'])
							User::setcookie("hash", $password, time() + COOKIE_LIFETIME);
				}
			}
		}
	}
	catch (PDOException $ex)
	{
		$error = PDOErrorInfo($ex, $STRINGS['dberror']);
	}

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
