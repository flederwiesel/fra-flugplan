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
	'cookies' => 'Cookies m&uuml;ssen aktiviert sein, um diese Seite benutzen zu k&ouml;nnen!',
	'liveschedule' => 'Live Flugplan',
	'home' => 'Startseite',
	'watchlist' => 'Beobachtungsliste',
	'addflight' => 'Flug einf&uuml;gen',
	'help' => 'Hilfe',
	'arrival' => 'Ankunft',
	'departure' => 'Ablug',
	'time' => 'Zeit',
	'flight' => 'Flug',
	'airline' => 'Fluggesellschaft',
	'airport' => 'Flughafen',
	'from' => 'Von',
	'to' => 'Nach',
	'type' => 'Typ',
	'reg' => 'Reg',
	'comment' => 'Kommentar',
	'refresh' => 'Aktualisieren',
	'needtype' => 'Flugzeug unbekannt. Bitte Typ angeben.',
	'unexpected' => 'Es ist ein (un)erwarteter Fehler aufgetreten.',
	'untilinvalid' => 'Bitte gültiges Enddatum angeben!',
	'wdays' => 'Mindestens ein Wochentag muß ausgewählt werden!',
	'invalidreg' => 'Ungültige Registrierung!',
	'invalidflight' => 'Flugnummer ungültig!',
	'datefrom' => 'Datumsformat ungültig!',
	'invalidtime' => 'Zeitformat ungültig!',
	'invaliddatetime' => 'Datum/Zeit ungültig!',
	'nosuchairline' => 'Fluggesellschaft unbekannt. Bitte Kürzel und Name angeben!',
	'typeunknown' => 'Flugzeugtyp unbekannt!',
	'addflsuccess' => 'Flug erfolgreich hinzugefügt.',
	'fatal' => 'SCHWERWIEGENDER FEHLER',
	'dberror' => 'Es ist ein Datenbankfehler aufgetreten: %s(%u): %s.',

	'authentication' => 'Anmeldung',
	'welcome' => 'Hallo',
	'login' => 'Anmelden',
	'logout' => 'Abmelden',
	'register' => 'Registrieren',
	'changepw' => 'Benutzerprofil',
	'username' => 'Benutzername',
	'emailaddress' => 'E-Mail-Adresse',
	'password' => 'Passwort',
	'newpassword' => 'Neues Passwort',
	'confirmpassword' => 'Passwort bestätigen',
	'rememberme' => 'Auf diesem Computer angemeldet bleiben (benötigt Cookies).',
	'notamember' => 'Kein Mitglied? Registrieren!',
	'forgotpassword' => 'Passwort vergessen?',
	'hintnumchars' => 'Mindestens %lu, höchstens %lu Zeichen',
	'language' => 'Sprache',
	'hintpassword' => '',
	'registration' => 'Registrierung',
	'onefieldmandatory' => 'Nur eines der obigen Felder muß ausgefüllt werden.',
	'passwdencrypted' => 'Passwörter werden verschlüsselt gespeichert, und können deshalb nicht wiederhergestellt werden. Es kann jedoch ein Token via E-Mail angefordert werden, um ein neues zu vergeben.',
	'submit' => 'Absenden',
	'changepasswd' => 'Passwort ändern',
	'token' => 'Token',
	'tokenemail' => 'Wurde per E-Mail versendet.',
	'passwordchanged' => 'Passwort erfolgreich geändert. Login steht zur Verfügung.',
	'passwordsmismatch' => 'Passwörter stimmen nicht überein!',
	'executionerror' => 'Ausführung fehlgeschlagen',
	'authfailedpasswdnotch' => 'Authentifizierung fehlgeschlagen. Passwort wurde nicht geändert.',
	'passwdchanged' => 'Passwort erfolgreich geändert.',
	'passwdchangedlogin' => 'Password changed successfully. You can now login.',
	'authfailed' => 'Authentifizierung fehlgeschlagen.',
	'userexists' => 'Benutzername bereits vergeben.',
	'emailexists' => 'E-Mailadresse bereits vergeben.',
	'regsuccess' => 'Registrierung erfolgreich. Aktivierungscode eingeben.',
	'regfailed' => 'Registrierung fehlgeschlagen. Bitte Administrator kontaktieren.',
	'activationsuccess' => 'Aktivierung erfolgreich. Login steht zur Verfügung.',
	'nosuchuser' => 'Benutzername unbekannt.',
	'nosuchemail' => 'E-Mailadresse nicht registriert.',
	'nosuchuseremail' => 'E-Mailadresse nicht mit diesem Benutzername verknüpft.',
	'nonempty' => 'Entweder Benutzername oder E-Mail muß ausgefüllt werden.',
	'noretrybefore' => 'Ein Passwort-Token wurde erst kürzlich angefordert. Bitte %s warten.',
	'usernamelengthmin' => 'Benutzername muß mindestens %lu Zeichen enthalten.',
	'usernamelengthmax' => 'Benutzername darf höchstens %lu Zeichen enthalten.',
	'emailinvalid' => 'E-Mailadresse ungültig.',
	'activationfailed' => 'Activation failed',
	'activationcode' => 'Bitte Code aus der Aktivierungs-E-Mail eingeben.',
	'subjpasswdchange' => 'Passwortänderung',
	'emailpasswd' => "Hello,\n\nthis e-mail has been sent to you because you (or someone else at IP address %s) requested a password change for user '%s' at %s\n\nPassword token is:\n%s\n\nYou can also use this link to process your request:\n%s?req=changepw&user=%s&token=%s\nThis token will expire on %s GMT.\n\nPlease ignore this e-mail if you didn't request a change of password. Our apologies for any inconvenience.\n\n\nThe %s Team",	//&&
	'emailactivation' => "Hello,\n\nthis e-mail has been sent to you because you (or someone else at IP address %s) requested user registration as '%s' at %s (%s).\n\nActivation token is:\n%s\n\nYou can also use this link to process your request:\n%s?req=activate&user=%s&token=%s\nThis token will expire on %s GMT.\n\nPlease ignore this e-mail if you didn't request a registration. Our apologies for any inconvenience.\n\n\nThe %s Team",
	'subjactivate' => 'Aktivierung',
	'mailfailed' => 'E-Mail konnnte nicht versendet werden: ',
	'tokensent' => 'Aktivierungs-Token wurde an die angegebene E-mail-Addresse gesendet. Passwort kann nun geändert werden.',
	'activation' => 'Aktivierung',
	'activationexpired' => 'Aktivierungs-Token abgelaufen.',
	'back' => 'Zurück',
	'timezone' => 'Zeitzone',
	'de' => 'Deutsch',
	'en' => 'Englisch',
	'icaomodel' => 'ICAO Type',
	'pax-regular' => 'Normal',
	'cargo' => 'Cargo',
	'ferry' => 'Überführung',
	'date' => 'Datum',
	'local' => 'lokal',
	'once' => 'Einmalig',
	'daily' => 'Täglich',
	'each' => 'Jeden',
	'mon' => 'Mo',
	'tue' => 'Di',
	'wed' => 'Mi',
	'thu' => 'Do',
	'fri' => 'Fr',
	'sat' => 'Sa',
	'sun' => 'So',
	'all' => 'Alle',
	'until' => 'Bis',
	'sta' => 'STA',
	'std' => 'STD',
	'help-content' => 'Hilfeseite... ',
	'notloggedin' => 'Bitte zuerst anmelden.',
	'nopermission' => 'Nicht genügend Rechte, um diese Aktion auszuführen.',
);

?>
