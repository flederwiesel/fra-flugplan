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

require_once '.config';

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
				 mb_encode_mimeheader("admin:$subject", 'ISO-8859-1', 'Q'),
				 mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8'), $header);
}

class User
{
	private $id = 0;
	private $name = null;
	private $email = null;
	private $timezone = null;
	private $lang = null;
	private $permissions = '';

	public function __construct($id, $name, $email, $tz, $lang, $perms)
	{
		$this->id = $id;
		$this->name = $name;
		$this->email = $email;
		$this->timezone = $tz;
		$this->lang = $lang;
		$this->permissions = $perms;

		return $this;
	}

	public function id() { return $this->id; }
	public function name() { return $this->name; }
	public function email() { return $this->email; }
	public function timezone() { return $this->timezone; }
	public function lang() { return $this->lang; }
	public function language($lang) { $this->lang = $lang; }
	public function permissions() { return $this->permissions; }
}

function /*str*/ token() { return hash('sha256', mcrypt_create_iv(32)); }

function LogoutUser()
{
	global $user;

	unset($user);
	$user = null;

	unset($_COOKIE['userID']);
	unset($_COOKIE['hash']);

	// remove cookies
	setcookie('userID', 0, 0);
	setcookie('hash', null, 0);
	setcookie('autologin', false, 0);
}

function LoginUserAutomatically()
{
	$user = null;

	if (isset($_COOKIE['userID']) &&
		isset($_COOKIE['hash']))
	{
		// verify
		$user = LoginUser($_COOKIE['userID'], $_COOKIE['hash'], true, $_COOKIE['autologin'], $message);
	}

	return $user;
}

function /*bool*/ LoginUser($user, $password, $byid, $remember, /*out*/ &$message)
{
	global $lang;

	if ($byid)
	{
		$id = $user;
		$query = sprintf("SELECT `name`, `passwd`, `salt`, `email`, `timezone`, `language`, `permissions`, `token_type`".
						 " FROM `users` WHERE `id`=$user");
	}
	else
	{
		$name = $user;
		$query = sprintf("SELECT `id`, `passwd`, `salt`, `email`, `timezone`, `language`, `permissions`, `token_type`".
						 " FROM `users` WHERE `name`='$user'");
	}

	$user = null;
	$error = null;
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
						$error = $lang['authfailed'];
						break;

					case 'none':
					default:
						break;
					}
				}

				if (!$error)
				{
					$hash = $byid ? $password : hash_hmac('sha256', $password, $row['salt']);

					if ($row['passwd'] == $hash)
					{
						if ($byid)
							$name = $row['name'];
						else
							$id = $row['id'];

						$user = new User($id, $name, $row['email'], $row['timezone'], $row['language'], $row['permissions']);

						if ($user)
						{
							$expires = (1 == $remember) ? time() + COOKIE_LIFETIME : 0;

							setcookie('userID',    $user->id(),   $expires);
							setcookie('hash',      $hash,         $expires);
							setcookie('autologin', true,          $expires);
							setcookie('lang',      $user->lang(), $expires);
						}
					}
					else
					{
						setcookie('userID',  0, 0);
						setcookie('hash', null, 0);
						setcookie('autologin', false, 0);

						$error = $lang['authfailed'];
					}
				}
			}
		}

		mysql_free_result($result);
	}

	$message = $error ? $error : null;

	return $user;
}

function /*bool*/ RegisterUser($user, $email, $password, $language, /*out*/ &$message)
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
		{
			$error = $lang['userexists'];
		}
		else
		{
			$result1 = mysql_query("SELECT `id` FROM `users` WHERE `email`='$email'");

			if (!$result1)
			{
				$error = mysql_error();
			}
			else
			{
				if (mysql_num_rows($result1) != 0)
				{
					$error = $lang['emailexists'];
				}
				else
				{
					$salt = token();
					$token = token();
					$password = hash_hmac('sha256', $password, $salt);

					$query = sprintf(
						"INSERT INTO ".
						"`users`(".
						" `name`, `email`, `passwd`, `salt`, `language`, `permissions`,".
						" `token`, `token_type`, `token_expires`, `ip`)".
						"VALUES(".
						" '$user', '$email', '$password', '$salt', '$language', '0', ".
						" '$token', 'activation', ".
						" FROM_UNIXTIME(UNIX_TIMESTAMP(UTC_TIMESTAMP()) + %lu), '%s' )",
						TOKEN_LIFETIME, $_SERVER['REMOTE_ADDR']);

					if (!mysql_query($query))
					{
						$error = mysql_user_error($lang['regfailed']);
					}
					else
					{
						$result2 = mysql_query("SELECT `id`,`token_expires` ".
											   "FROM `users` ".
											   "WHERE `id`=LAST_INSERT_ID()");

						if (!$result2)
						{
							$error = mysql_error();
						}
						else
						{
							if (0 == mysql_num_rows($result2))
							{
								$error = $lang['regfailed'];
							}
							else
							{
								$row = mysql_fetch_row($result2);
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

							mysql_free_result($result2);
						}
					}
				}

				mysql_free_result($result1);
			}
		}

		mysql_free_result($result);
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

		$subject = mb_encode_mimeheader($lang['subjactivate'], 'ISO-8859-1', 'Q');

		$body = sprintf($lang['emailactivation'], $client_ip, $user, ORGANISATION, SITE_URL,
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

	$message = $error ? $error : null;

	return $error ? false : true;
}

function /*bool*/ ActivateUser($user, $token, /*out*/ &$message)
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
			$error = $lang['activationfailed'];
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
				$now = $row['now'];

				if ('none' == $row['token_type'] ||
					NULL == $row['token'] ||
					NULL == $row['expires'])
				{
					$error = $lang['badrequest'];
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
						if ($token == $row['token'])
							$uid = $row['id'];
						else
							$error = $lang['activationfailed'];
					}
				}
			}
		}

		mysql_free_result($result);
	}

	if (!$error)
	{
		$query = "UPDATE `users`".
				 " SET `token`=NULL, `token_type`='none', `token_expires`=NULL".
				 " WHERE `id`=$uid";

		if (!mysql_query($query))
			$error = mysql_error();
	}

	AdminMail('activation',
		sprintf("$uid:$user%s = %s\n",
				 $now ? " ($now)" : "",
				 $error ? $error : "OK"));

	$message = $error ? $error : null;

	return $error ? false : true;
}

function /*bool*/ RequestPasswordChange($user, $email, /*out*/ &$message)
{
	global $lang;

	define('Q_ID', 1);
	define('Q_NAME', 2);
	define('Q_MAIL', 3);

	$uid = null;
	$expires = null;
	$error = null;

	$query = sprintf("SELECT `id`, `name`, `email`, `token_type`, IF (ISNULL(`token_expires`), %lu, ".
			 		 "(SELECT UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(`token_expires`))) AS `expires`".
			 		 "FROM `users` WHERE ",
					 TOKEN_LIFETIME);

	if (0 == strlen($user))
	{
		if (0 == strlen($email))
			$error = $lang['nonempty'];
		else
			$query .= "`email`='$email'";
	}
	else
	{
		if (0 == strlen($email))
			$query .= "`name`='$user'";
		else
			$query .= "`name`='$user' AND `email`='$email'";
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
					$left = $row['expires'];

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

	$message = $error ? $error : null;

	return $error ? false : true;
}

function /*bool*/ ChangePassword($user, $token, $password, /*out*/ &$message)
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
							$error = $lang['authfailedpasswdnotch'];
					}
				}

				if (!$error)
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

		mysql_free_result($result);
	}

	AdminMail('passwdch',
		sprintf("$uid:$user%s = %s\n",
				$now ? " ($now)" : "",
				$error ? $error : "OK"));

	$message = $error ? $error : null;

	return $error ? false : true;
}

function /*str*/ mysql_user_error($default)
{
	$error = mysql_error();

	$expl = explode(": ", $error, 2);
	$errno = $expl[0];

	switch ($errno)
	{
	case '400':
		$error = sprintf($lang['usernamelengthmin'], USERNAME_MIN);
		break;

	case '401':
		$error = sprintf($lang['usernamelengthmax'], USERNAME_MAX);
		break;

	case '402':
	case '403':
		$error = $default;
		break;
	}

	return $error;
}

define('INP_FORCE', 0x1);
define('INP_POST', 0x2);
define('INP_GET',  0x4);

function Input_SetValue($name, $whence, $debug)
{
	$value = null;

	if (INP_POST & $whence)
	{
		if (isset($_POST[$name]))
			$value = $_POST[$name];
	}

	if (INP_GET & $whence)
	{
		if (!$value)
 			if (isset($_GET[$name]))
 				$value = $_GET[$name];
	}

	if (null == $value)
	{
		if (INP_FORCE & $whence)
			$value = $debug;
	}

	if (defined('DEBUG'))
		if (!$value)
			$value = $debug;

	if ($value)
		echo $value;
}

?>
