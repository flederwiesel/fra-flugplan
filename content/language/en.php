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
	'unexpected' => 'An (un)expected error occured.',
	'untilinvalid' => 'Please specify valid end date!',
	'wdays' => 'At least one weekday must be selected!',
	'invalidreg' => 'Invalid Registration!',
	'invalidflight' => 'Flight number invalid!',
	'datefrom' => 'Date format invalid!',
	'invalidtime' => 'Time format invalid!',
	'invaliddatetime' => 'Date/Time invalid!',
	'nosuchairline' => 'Airline unknown. Please specify code and name!',
	'typeunknown' => 'Aircraft type unknown!',
	'addflsuccess' => 'Flight added successfully.',
	'fatal' => 'FATAL ERROR',
	'dberror' => 'A database error occured: %s(%u): %s.',

	'authentication' => 'Authentication',
	'welcome' => 'Welcome, ',
	'login' => 'Login',
	'logout' => 'Logout',
	'register' => 'Register',
	'changepw' => 'User profile',
	'username' => 'Username',
	'emailaddress' => 'E-mail Address',
	'password' => 'Password',
	'newpassword' => 'New Password',
	'confirmpassword' => 'Confirm Password',
	'rememberme' => 'Remember me on this computer (requires cookies)',
	'notamember' => 'Not a member? Register!',
	'forgotpassword' => 'Forgot Password?',
	'hintnumchars' => 'At least %lu, at most %lu characters',
	'language' => 'Language',
	'hintpassword' => '',
	'registration' => 'Registration',
	'onefieldmandatory' => 'Only one of the above fields is mandatory.',
	'passwdencrypted' => 'Your password is stored in encrypted form, so it cannot be retrieved. However, you can request a reset token, which will be sent to your registered e-mail address.',
	'submit' => 'Submit',
	'changepasswd' => 'Change password',
	'token' => 'Token',
	'tokenemail' => 'This should have been sent to you via e-mail.',
	'passwordchanged' => 'Password changed successfully. You can now login.',
	'passwordsmismatch' => 'Passwords do not match!',
	'executionerror' => 'Execution error.',
	'authfailedpasswdnotch' => 'Authentication failed. Password has not been changed.',
	'passwdchanged' => 'Password changed successfully.',
	'passwdchangedlogin' => 'Password changed successfully. You can now login.',
	'authfailed' => 'Authentication failed.',
	'userexists' => 'Username already in use.',
	'emailexists' => 'Email address already in use.',
	'regsuccess' => 'Registration successful. Please enter activation code.',
	'regfailed' => 'Registration failed. Please contact an administrator.',
	'activationsuccess' => 'Activation successful. You may now login.',
	'nosuchuser' => 'No such user.',
	'nosuchemail' => 'E-mail address not registered.',
	'nosuchuseremail' => 'No such user associated with this e-mail address.',
	'nonempty' => 'Either Username or e-mail address must be non-empty.',
	'noretrybefore' => 'A password change token has recently been requested. Retry in %s.',
	'usernamelengthmin' => 'Username must at least be %lu characters long.',
	'usernamelengthmax' => 'Username must be no longer than %lu characters.',
	'emailinvalid' => 'E-mail address invalid.',
	'activationfailed' => 'Activation failed',
	'activationcode' => 'Please enter the activation code sent to you via e-mail.',
	'subjpasswdchange' => 'Change Password',
	'emailpasswd' => "Hello,\n\nthis e-mail has been sent to you because you (or someone else at IP address %s) requested a password change for user '%s' at %s\n\nPassword token is:\n%s\n\nYou can also use this link to process your request:\n%s?req=changepw&user=%s&token=%s\nThis token will expire on %s GMT.\n\nPlease ignore this e-mail if you didn't request a change of password. Our apologies for any inconvenience.\n\n\nThe %s Team",	//&&
	'emailactivation' => "Hello,\n\nthis e-mail has been sent to you because you (or someone else at IP address %s) requested user registration as '%s' at %s (%s).\n\nActivation token is:\n%s\n\nYou can also use this link to process your request:\n%s?req=activate&user=%s&token=%s\nThis token will expire on %s GMT.\n\nPlease ignore this e-mail if you didn't request a registration. Our apologies for any inconvenience.\n\n\nThe %s Team",
	'subjactivate' => 'Account activation',
	'mailfailed' => 'Unable to send e-mail: ',
	'tokensent' => 'A token has been sent to your e-mail address. You can now change your password.',
	'activation' => 'Activation',
	'activationexpired' => 'Activation token expired.',
	'back' => 'Back',
	'timezone' => 'Timezone',
	'de' => 'German',
	'en' => 'English',
	'icaomodel' => 'ICAO Typ',
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
);

?>
