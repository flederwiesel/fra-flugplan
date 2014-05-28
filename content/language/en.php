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

$lang = array
(
	'noscript' => 'If you obtain this message, JavaScript is either not supported or disabled '.
		'in in your browser options.<br>In order to use full functionality of this site we advise you '.
		'to <a href="http://www.enable-javascript.com">enable Javascript</a>.',
	'cookies' => 'Cookies must be enabled in order to use this site!',
	'liveschedule' => 'Live Schedule',
	'home' => 'Home',
	'watchlist' => 'Watchlist',
	'addflight' => 'Add flight',
	'help' => 'Help',
	'arrival' => 'Arrival',
	'departure' => 'Departure',
	'time' => 'Time',
	'flight' => 'Flight',
	'airline' => 'Airline',
	'airport' => 'Airport',
	'from' => 'From',
	'to' => 'To',
	'type' => 'Type',
	'reg' => 'Reg',
	'comment' => 'Comment',
	'refresh' => 'Refresh',
	'needtype' => 'Aircraft unknown. Please specify type.',
	'unexpected' => 'An (un)expected error occurred.',
	'untilinvalid' => 'Please specify valid end date!',
	'wdays' => 'At least one weekday must be selected!',
	'invalidreg' => 'Invalid Registration!',
	'invalidflight' => 'Flight number invalid!',
	'datefrom' => 'Date invalid (format dd.mm.YYYY)!',
	'invalidtime' => 'Time invalid (format HH:MM)!',
	'invaliddatetime' => 'Date/Time invalid!',
	'nosuchairline' => 'Airline unknown. Please specify code and name!',
	'typeunknown' => 'Aircraft type unknown!',
	'addflsuccess' => 'Flight added successfully.',
	'fatal' => 'FATAL ERROR',
	'dberror' => 'A database error occurred: %s(%u): %s.',

	'authentication' => 'Authentication',
	'welcome' => 'Welcome, ',
	'login' => 'Login',
	'logout' => 'Logout',
	'register' => 'Register',
	'registernote' => 'In order to use the complete set of features this site provides, '.
		'you need to register once, which is free of charge. No consequential charges will arise, '.
		'data entered here will be handled according to the German Federal Data Protection Act.<br><br>'.
		'For security reasons, you need to activate your account with a token, which will be sent to your '.
		'email address in the next step. Accounts not having been activated will be deleted on a regular basis.',
	'profile' => 'User profile',
	'changepw' => 'Change password',
	'username' => 'User name',
	'emailaddress' => 'E-mail Address',
	'password' => 'Password',
	'newpassword' => 'New Password',
	'confirmpassword' => 'Confirm Password',
	'rememberme' => 'Remember me on this computer (requires cookies)',
	'notamember' => 'Not a member? Register!',
	'forgotpassword' => 'Forgot Password?',
	'hintnumchars' => 'At least %lu, at most %lu characters',
	'language' => 'Language',
	'and' => 'and',
	'passwd-min' => 'Password must consist of %u or more characters',
	'passwd-letter' => '%%s at least one letter',
	'passwd-upper' => '%%s at least one upper case letter',
	'passwd-lower' => '%%s at least one lower case letter',
	'passwd-digit' => '%%s at least one digit',
	'passwd-special' => '%%s one of the characters &laquo;%s&raquo;',
	'passwd-separator-0' => ' and contain',
	'passwd-postfix-0' => '.',
	'passwd-postfix-N' => '.',
	'registration' => 'Registration',
	'onefieldmandatory' => 'Only one of the above fields is mandatory.',
	'passwdencrypted' => 'Your password is stored in encrypted form, so it cannot be retrieved. '.
		'However, you can request a reset token, which will be sent to your registered e-mail address.',
	'submit' => 'Submit',
	'changepasswd' => 'Change password',
	'token' => 'Token',
	'tokenemail' => 'A combination of 64 numbers/letters from your registration e-mail.',
	'passwordchanged' => 'Password changed successfully. You can now login.',
	'passwordsmismatch' => 'Passwords do not match!',
	'executionerror' => 'Execution error.',
	'authfailedpasswdnotch' => 'Authentication failed. Password has not been changed.',
	'passwdchanged' => 'Password changed successfully.',
	'passwdchangedlogin' => 'Password changed successfully. You can now login.',
	'authfailed' => 'Authentication failed.',
	'authfailedtoken' => 'Authentication denied because password change token has been requested.',
	'userexists' => 'User name already in use.',
	'emailexists' => 'Email address already in use.',
	'regsuccess' => 'Registration successful. Please enter activation token.',
	'snailmail' => 'Please enter the token which you should have received on the e-mail address you provided.<br><br>'.
		'If no activation mail arrives on your inbox, please also check your spam folder.<br><br>'.
		'Depending on your internet service provider and mail transport agent settings, delivery of e-mails can take '.
		'longer than the activation token is valid. In this case, don\'t hesitate to contact us at '.
		'<a href="content/emil.php?subject=fra-schedule%20activation trouble"><img alt="email" src="content/emil-img.php"></a>.',
	'regfailed' => 'Registration failed. Please contact an administrator.',
	'activationsuccess' => 'Activation successful. You may now login.',
	'nosuchuser' => 'No such user.',
	'nosuchemail' => 'E-mail address not registered.',
	'nosuchuseremail' => 'No such user associated with this e-mail address.',
	'nonempty' => 'Either User name or e-mail address must be non-empty.',
	'noretrybefore' => 'A password change token has recently been requested. Retry in %s.',
	'passwdlengthmin' => 'Password must at least be %lu characters long.',
	'usernamelengthmin' => 'User name must at least be %lu characters long.',
	'usernamelengthmax' => 'User name must be no longer than %lu characters.',
	'emailinvalid' => 'E-mail address invalid.',
	'activationfailed' => 'Activation failed (code %lu).',
	'activationrequired' => 'Activation required.',
	'activatefirst' => 'Please <a href="?req=activate&user=%s">activate</a> account first!',
	'subjpasswdchange' => 'Change Password',
	'emailpasswd' => "Hello,\n\nthis e-mail has been sent to you because you (or someone else at IP address %s) '.
		'requested a password change for user '%s' at %s\n\nPassword token is:\n%s\n\n'.
		'You can also use this link to process your request:\n%s?req=changepw&user=%s&token=%s\n'.
		'This token will expire on %s GMT.\n\nPlease ignore this e-mail if you didn't request a change of password. '.
		'Our apologies for any inconvenience.\n\n\nThe %s Team",	//&&
	'emailactivation' => "Hello,\n\nthis e-mail has been sent to you because you (or someone else at IP address %s) '.
		'requested user registration as '%s' at %s (%s).\n\nActivation token is:\n%s\n\nYou can also use this link '.
		'to process your request:\n%s?req=activate&user=%s&token=%s\nThis token will expire on %s GMT.\n\n'.
		'Please ignore this e-mail if you didn't request a registration. Our apologies for any inconvenience.\n\n\nThe %s Team",
	'subjactivate' => 'Account activation',
	'mailfailed' => 'Unable to send e-mail: ',
	'tokensent' => 'A token has been sent to your e-mail address. You can now change your password.',
	'activation' => 'Activation',
	'activationexpired' => 'Activation token expired.',
	'passwdtokenexpired' => 'Token expired. Password has not been changed',
	'badrequest' => 'Bad request.',
	'back' => 'Back',
	'timezone' => 'Timezone',
	'de' => 'German',
	'en' => 'English',
	'icaomodel' => 'ICAO Type',
	'pax-regular' => 'Regular',
	'cargo' => 'Cargo',
	'ferry' => 'Ferry',
	'date' => 'Date',
	'local' => 'local',
	'once' => 'Once',
	'daily' => 'Daily',
	'each' => 'Each',
	'mon' => 'Mon',
	'tue' => 'Tue',
	'wed' => 'Wed',
	'thu' => 'Thu',
	'fri' => 'Fri',
	'sat' => 'Sat',
	'sun' => 'Sun',
	'all' => 'All',
	'until' => 'Until',
	'sta' => 'STA',
	'std' => 'STD',
	'help-content' => 'This is the help page... ',
	'notloggedin' => 'You must first login.',
	'nopermission' => 'You do not have the permissions required for this operation.',
	'rwydir' => 'active runway',
	'emil' => 'Thank you for taking the time for feedback on our site.',
	'dlflights' => 'Download flights',
	'displayinterval' => 'Display Interval',
	'displayintervaldesc' => 'For mobile devices &ndash; which might not always be within range to a WLAN network '.
		'&ndash;, it is possible to increase response times and reduce band width by adjusting the display interval. '.
		'The default values are -15 min ... +24 hours.',
	'cellphone' => 'Cell Phone',
	'tablet' => 'Tablet',
	'settingsssaved' => 'Settings have been saved.',
	'vtf' => ' visit to FRA',
);

?>
