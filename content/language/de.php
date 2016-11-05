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

$lang = array
(
	'$id' => 'de',
	'noscript' => 'JavaScript wird entweder nicht vom Browser unterstützt oder ist abgeschaltet.<br>'.
		'Um diese in vollem Umfang nutzen zu können, bitte '.
		'<a href="http://www.enable-javascript.com/de/">Javascript aktivieren</a>.',
	'cookies' => 'Cookies müssen aktiviert sein, um diese Seite benutzen zu können!',
	'liveschedule' => 'Live Flugplan',
	'schedule' => 'Flugplan',
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
	'from' => 'von',
	'to' => 'nach',
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
	'registernote' => 'Um die Funktionalität dieser Seite in vollem Umfang benutzen zu können, '.
		'bedarf es einer einmaligen, kostenlosen Registrierung. Es entstehen hierbei keine Folgekosten, '.
		'angegebene Daten werden entsprechend den Datenschutzrichtlinien behandelt.<br><br>'.
		'Aus Sicherheitsgründen muß das Benutzerkonto mittels einem per E-Mail zugesendeten Token '.
		'anschließend einmalig aktiviert werden. Nicht aktivierte Konten werden regelmäßig gelöscht.',
	'profile' => 'Benutzerprofil',
	'changepw' => 'Passwort ändern',
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
	'and' => 'und',
	'passwd-min' => 'Passwort muß aus mindestens %u Zeichen bestehen',
	'passwd-letter' => '%%s mindestens einen Buchstaben',
	'passwd-upper' => '%%s mindestens einen Großbuchstaben',
	'passwd-lower' => '%%s mindestens einen Kleinbuchstaben',
	'passwd-digit' => '%%s mindestens eine Ziffer',
	'passwd-special' => '%%s eines der Zeichen &laquo;%s&raquo;',
	'passwd-separator-0' => ',',
	'passwd-postfix-0' => '.',
	'passwd-postfix-N' => ' enthalten.',
	'registration' => 'Registrierung',
	'onefieldmandatory' => 'Nur eines der obigen Felder muß ausgefüllt werden.',
	'passwdencrypted' => 'Passwörter werden verschlüsselt gespeichert, und können deshalb '.
		'nicht wiederhergestellt werden. Es kann jedoch ein Token via E-Mail angefordert werden, '.
		'um ein neues zu vergeben.',
	'submit' => 'Absenden',
	'changepasswd' => 'Passwort ändern',
	'token' => 'Token',
	'tokenemail' => 'Kombination aus 64 Ziffern/Buchstaben aus der Registrierungs-E-Mail.',
	'passwordchanged' => 'Passwort erfolgreich geändert. Login steht zur Verfügung.',
	'passwordsmismatch' => 'Passwörter stimmen nicht überein!',
	'executionerror' => 'Ausführung fehlgeschlagen',
	'authfailedpasswdnotch' => 'Authentifizierung fehlgeschlagen. Passwort wurde nicht geändert.',
	'passwdchanged' => 'Passwort erfolgreich geändert.',
	'passwdchangedlogin' => 'Passwort erfolgreich geändert. Login steht zur Verfügung.',
	'authfailed' => 'Authentifizierung fehlgeschlagen.',
	'authfailedtoken' => 'Authentifizierung abgelehnt, da Passwortänderungs-Token angefordert wurde.',
	'userexists' => 'Benutzername bereits vergeben.',
	'emailexists' => 'E-Mailadresse bereits vergeben.',
	'regsuccess' => 'Registrierung erfolgreich. Aktivierungs-Token eingeben.',
	'snailmail' => 'Bitte Token aus der E-Mail eigeben, die an die angegebene Adresse gesendet wurde.<br><br>'.
		'Sollte keine Aktivierungs-Mail im Posteingang auftauchen, bitte den Spam-Ordner prüfen.<br><br>'.
		'Je nach Internetanbieter und Mailserver kann es vorkommen, daß die Zustellung von E-Mails '.
		'länger dauert als das Token gültig ist. In diesem Fall, bitte Kontakt mit mir unter '.
		'<a href="content/emil.php?subject=fra-schedule%20Aktivierungsproblem"><img alt="email" '.
		'src="content/mkpng.php?font=verdana&amp;size=10&amp;bg=white&amp;fg=%2300007f&amp;res=ADMIN_EMAIL"></a> aufnehmen.<br>',
	'regfailed' => 'Registrierung fehlgeschlagen. Bitte Administrator kontaktieren.',
	'activationsuccess' => 'Aktivierung erfolgreich. Login steht zur Verfügung.',
	'nosuchuser' => 'Benutzername unbekannt.',
	'nosuchemail' => 'E-Mailadresse nicht registriert.',
	'nosuchuseremail' => 'E-Mailadresse nicht mit diesem Benutzername verknüpft.',
	'nonempty' => 'Entweder Benutzername oder E-Mail muß ausgefüllt werden.',
	'noretrybefore' => 'Ein Passwort-Token wurde erst kürzlich angefordert. Bitte %s warten.',
	'passwdlengthmin' => 'Passwort muß mindestens %lu Zeichen lang sein.',
	'usernamelengthmin' => 'Benutzername muß mindestens %lu Zeichen enthalten.',
	'usernamelengthmax' => 'Benutzername darf höchstens %lu Zeichen enthalten.',
	'usernameinvalid' => 'Benutzername ungültig.',
	'emailinvalid' => 'E-Mailadresse ungültig.',
	'activationfailed' => 'Aktivierung fehlgeschlagen (code %lu).',
	'activationfailed_t' => 'Activation failed: Token muss ausgefüllt werden.',
	'activationfailed_u' => 'Activation failed: Benutzername muss ausgefüllt werden.',
	'activationrequired' => 'Konto nicht aktiviert.',
	'activatefirst' => 'Bitte Konto zuvor <a href="?req=activate&amp;user=%s">aktivieren</a>!',
	'subjpasswdchange' => 'Passwortänderung',
	'emailpasswd' => "Hallo,\n\nDu erhälst diese E-Mail, weil Du (oder jemand anders mit IP-Addresse %s) ".
		"eine Anfrage gesendet hast, das Passwort für Benutzer '%s' auf %s zurückzusetzen.\n\n".
		"Das Token dafür ist:\n%s\n\nBenutze diesen Link, um fortzufahren:\n%s?req=changepw&user=%s&token=%s\n".
		"Dieses Token läuft um %s GMT ab.\n\nFalls Du das Zurücksetzen Deines Passworts nicht veranlaßt hast, ".
		"ignoriere diese Mail einfach.\n\n\nDas %s Team",
	'emailactivation' => "Hallo,\n\nDu erhälst diese E-Mail, weil Du (oder jemand anders mit IP-Addresse %s) ".
		"eine Anfrage zur Registrierung als Benutzer '%s' auf %s (%s) gesendet hast.\n\n".
		"Das Aktivierungs-Token ist:\n%s\n\nBenutze diesen Link, um fortzufahren:\n%s?req=activate&user=%s&token=%s\n".
		"Dieses Token läuft um %s GMT ab.\n\nFalls Du keine Registrierung veranlaßt hast, ignoriere diese Mail einfach.\n\n\nDas %s Team",
	'subjactivate' => 'Aktivierung',
	'mailfailed' => 'E-Mail konnnte nicht versendet werden: ',
	'tokensent' => 'Aktivierungs-Token wurde an die angegebene E-mail-Addresse gesendet. Passwort kann nun geändert werden.',
	'activation' => 'Aktivierung',
	'activationexpired' => 'Aktivierungs-Token abgelaufen.',
	'passwdtokenexpired' => 'Token abgelaufen. Passwort wurde nicht geändert.',
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
	'dispinterval' => 'Anzeigeintervall',
	'dispintervaldesc' => 'Für mobile Geräte &ndash; welche sich nicht unbedingt in Reichweite '.
		'eines WLAN-Netzwerkes befinden mögen &ndash;, kann die Reaktionsgeschwindigkeit erhöht und die '.
		'Bandbreite durch ein angepaßtes Anzeigeintervall verringert werden. Das Standardervall ist -15 min ... +24 h.',
	'cellphone' => 'Mobiltelefon',
	'tablet' => 'Tablet',
	'settingsssaved' => 'Einstellungen wurden gespeichert.',
	'vtf' => ' Besuch in FRA',
	'notifinterval' => 'Benachrichtigungen',
	'notifintervaldesc' => 'Sobald eine der in der Beobachtungsliste festgelegten Kennungen im Flugplan auftaucht,
		wird eine Benachrichtigung an die hinterlegte E-Mail-Adresse versendet.<br><br>
		Bitte Zeiten festlegen, zwischen denen Benachrichtigungen gesendet werden sollen.<br>
		Soll keine Benachrichtigung erfolgen, bitte für beide Zeiten "00:00" auswählen.',
	'notif-from-until' => 'Benachrichtigen zwischen: ',
	'notif-setinterval' => 'Es wurden Benachrichtigungen aktiviert, jedoch kein Benachrichtigungsintervall ausgewählt.
		Um Benachrichtigungen zu erhalten, muß im <a href="?req=profile&amp;notifinterval">Benutzerprofil</a>
		ein Zeitraum angegeben werden.',
	'notification-timefmt' => 'Formatierung Zeitangabe:',
	'notification-strftime_1' => 'Für Experten: Formatangabe gemäß der PHP-Funktion %s.<br>Im Zweifelsfall leer lassen.',
	'notification-strftime_2' => 'Einige strftime()-Formatangaben (können kombiniert werden):',
	'notification-strftime_3' => 'Zusätzliche Formatangaben:',
	'notification-strftime_4' => 'Tag "Ankunftszeit" als Differenz zu heute.',
	'notification-strftime_a' => 'Abgekürzter Wochentag',
	'notification-strftime_A' => 'Wochentag',
	'notification-strftime_b' => 'Abgekürzter Monat',
	'notification-strftime_B' => 'Monat',
	'notification-strftime_c' => 'Datum und Zeit',
	'notification-strftime_d' => 'Tag als 2-stellige Zahl',
	'notification-strftime_e' => 'Tag mit vorangestelltem Leerzeichen, wenn 1-stellig',
	'notification-strftime_H' => 'Stunde im 24-h-Format',
	'notification-strftime_I' => 'Stunde im 12-h-Format',
	'notification-strftime_m' => 'Monat als Zahl',
	'notification-strftime_p' => '"AM" or "PM"',
	'notification-strftime_S' => 'Sekunde als 2-stellige Zahl',
	'strftime-true' => 'Zeiten werden nach folgendem Schema angezeigt: "%s"',
	'strftime-false' => '"%s" kann nicht korrekt ausgewertet werden. Änderungen wurden nicht gespeichert.',
	'invalidsession' => 'Ungültige Sitzung.',
	'spam:sing' => 'Die Registrierung wurde abgelehnt, da %s als Spam identifiziert wurde.',
	'spam:plur' => 'Die Registrierung wurde abgelehnt, da %s als Spam identifiziert wurden.',
	'ipaddress' => 'ip address',
);

?>
