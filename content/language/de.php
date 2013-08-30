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
	'noscript' => 'JavaScript wird entweder nicht vom Browser unterstützt oder ist abgeschaltet.<br>Um diese in vollem Umfang nutzen zu können, bitte <a href="http://www.enable-javascript.com/de/">Javascript aktivieren</a>.',
	'cookies' => 'Cookies müssen aktiviert sein, um diese Seite benutzen zu können!',
	'liveschedule' => 'Live Flugplan',
	'home' => 'Startseite',
	'watchlist' => 'Beobachtungsliste',
	'addflight' => 'Flug einfügen',
	'help' => 'Hilfe',
	'arrival' => 'Ankunft',
	'departure' => 'Abflug',
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
	'datefrom' => 'Datumsformat (TT.MM.JJJJ) ungültig!',
	'invalidtime' => 'Zeitformat (HH:MM) ungültig!',
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
	'hintpassword' => 'Mindestens '.PASSWORD_MIN.' Zeichen',
	'shortpassword' => 'Passwort muß mindestens '.PASSWORD_MIN.' Zeichen lang sein.',
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
	'activationfailed' => 'Aktivierung fehlgeschlagen.',
	'activationrequired' => 'Konto nicht aktiviert.',
	'subjpasswdchange' => 'Passwortänderung',
	'emailpasswd' => "Hallo,\n\nDu erhälst diese E-Mail, weil Du (oder jemand anders mit IP-Addresse %s) eine Anfrage gesendet hast, das Passwort für Benutzer '%s' auf %s zurückzusetzen.\n\nDas Token dafür ist:\n%s\n\nBenutze diesen Link, um fortzufahren:\n%s?req=changepw&user=%s&token=%s\nDieses Token läuft um %s GMT ab.\n\nFalls Du das Zurücksetzen Deines Passworts nicht veranlaßt hast, ignoriere diese Mail einfach.\n\n\nDas %s Team",
	'emailactivation' => "Hallo,\n\nDu erhälst diese E-Mail, weil Du (oder jemand anders mit IP-Addresse %s) eine Anfrage zur Registrierung als Benutzer '%s' auf %s (%s) gesendet hast.\n\nDas Aktivierungs-Token ist:\n%s\n\nBenutze diesen Link, um fortzufahren:\n%s?req=activate&user=%s&token=%s\nDieses Token läuft um %s GMT ab.\n\nFalls Du keine Registrierung veranlaßt hast, ignoriere diese Mail einfach.\n\n\nDas %s Team",
	'subjactivate' => 'Aktivierung',
	'mailfailed' => 'E-Mail konnnte nicht versendet werden: ',
	'tokensent' => 'Aktivierungs-Token wurde an die angegebene E-mail-Addresse gesendet. Passwort kann nun geändert werden.',
	'activation' => 'Aktivierung',
	'activationexpired' => 'Aktivierungs-Token abgelaufen.',
	'badrequest' => 'Ungültige Anfrage.',
	'back' => 'Zurück',
	'timezone' => 'Zeitzone',
	'de' => 'Deutsch',
	'en' => 'Englisch',
	'icaomodel' => 'ICAO Typ',
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
	'rwydir' => 'Betriebsrichtung',
	'emil' => 'Danke, daß Du Dir die Zeit für eine Resonanz bezüglich unserer Seite nimmst.',
	'dlflights' => 'Flüge herunterladen',
);

?>
