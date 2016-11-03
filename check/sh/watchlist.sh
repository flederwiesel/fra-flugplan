#!/bin/bash

###############################################################################
#
#  Copyright © Tobias Kühne
#
#  You may use and distribute this software free of charge for non-commercial
#  purposes. The software must be distributed in its entirety, i.e. containing
#  ALL binary and source files without modification.
#  Publication of modified versions of the source code provided herein,
#  is permitted only with the author's written consent. In this case the
#  copyright notice must not be removed or altered, all modifications to the
#  source code must be clearly marked as such.
#
###############################################################################

# drop/re-create database
initdb && rm -f .COOKIES

# preparation #################################################################

sed "s/%{date}/$(date +'%Y-%m-%d' --date='+1 day 00:00')/g" <<-"SQL" | query
	USE flederwiesel_fra-schedule;

	SELECT `id` INTO @uid FROM `users` WHERE `name`='root';

	INSERT INTO `aircrafts`
	(
		`uid`, `model`, `reg`
	)
	VALUES
	(@uid, (SELECT `id` FROM `models` WHERE `icao` = 'B77W'), 'B-KPE'),
	(@uid, (SELECT `id` FROM `models` WHERE `icao` = 'A333'), 'C-GFAH'),
	(@uid, (SELECT `id` FROM `models` WHERE `icao` = 'A346'), 'ZS-SNC'),
	(@uid, (SELECT `id` FROM `models` WHERE `icao` = 'A310'), 'C-GSAT');

	INSERT INTO `flights`
	(
		`uid`, `type`, `direction`, `airline`, `code`,
		`scheduled`, `expected`, `airport`, `model`, `aircraft`
	)
	VALUES
	(
		@uid, 'P', 'arrival',
		(SELECT `id` FROM `airlines` WHERE `code`='SA'), '260',
		'%{date} 06:15', NULL,
		(SELECT `id` FROM `airports` WHERE `icao`='FAOR'),
		(SELECT `id` FROM `models` WHERE `icao`='A346'),
		(SELECT `id` FROM `aircrafts` WHERE `reg`='ZS-SNC')
	),
	(
		@uid, 'P', 'arrival',
		(SELECT `id` FROM `airlines` WHERE `code`='CX'), '289',
		'%{date} 06:20', NULL,
		(SELECT `id` FROM `airports` WHERE `icao`='VHHH'),
		(SELECT `id` FROM `models` WHERE `icao`='B77W'),
		(SELECT `id` FROM `aircrafts` WHERE `reg`='B-KPE')
	),
	(
		@uid, 'P', 'arrival',
		(SELECT `id` FROM `airlines` WHERE `code`='AC'), '874',
		'%{date} 07:00', NULL,
		(SELECT `id` FROM `airports` WHERE `icao`='CYUL'),
		(SELECT `id` FROM `models` WHERE `icao`='A333'),
		(SELECT `id` FROM `aircrafts` WHERE `reg`='C-GFAH')
	),
	(
		@uid, 'P', 'arrival',
		(SELECT `id` FROM `airlines` WHERE `code`='TS'), 'XXX',
		'%{date} 07:00', NULL,
		(SELECT `id` FROM `airports` WHERE `icao`='CYVR'),
		(SELECT `id` FROM `models` WHERE `icao`='A310'),
		(SELECT `id` FROM `aircrafts` WHERE `reg`='C-GSAT')
	);

	INSERT INTO `users`
	(
		`id`, `name`, `email`, `salt`, `passwd`,
		`language`, `tt-`, `tt+`, `tm-`, `tm+`
	)
	VALUES
	(
		2, 'flederwiesel', 'hausmeister@flederwiesel.com',
		'cf78aafd5c5410b7b12c2794a52cda1bccd01316f30df57aa29c5609ba979c15',
		'c4ae99aa0209ce5bea9687cf0548d8ebc942ba14e166c45957a876bcec194fed', # elvizzz
		'en', -75, 86400, -75, 86400
	);

	# grant user permissions
	INSERT INTO `membership`(`user`, `group`)
				 VALUES((SELECT `id` FROM `users`  WHERE `name`='flederwiesel'),
						(SELECT `id` FROM `groups` WHERE `name`='addflights'));
SQL

# /preparation ################################################################

time=$(rawurlencode "$(date +'%Y-%m-%d %H:%M:%S' --date='0 days 23:59')")
today="$(date +'%Y-%m-%d' --date='23:55')"

check "1" browse "$url/?req=login\&time=$time" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "passwd=elvizzz" \
	"| sed -r '
		s/time=$today/time=0000-00-00/g
	'"

check "2" browse "$url/?arrival\&time=$time" \
	"| sed -r '
		s/time=$today/time=0000-00-00/g
	'"

# DON'T USE TABS AT THE BEGINNING OF add/del POST VALUES!
check "3" browse "$url/?arrival\&time=$time" \
		--data-urlencode "add='$(cat <<-EOF
			ZS-SNC	South African Airways - Star Alliance	1
			C-????	Air Canada ?	0
			C-FDAT	Air Transat - A310	1
			/C-G(TSTS[FHWY]|[FLPS]AT)/	Air Transat - A310	1
EOF
			)'" \
	"| sed -r '
		s/time=$today/time=0000-00-00/g
	'"

# DON'T USE TABS AT THE BEGINNING OF add/del POST VALUES!
check "4" browse "$url/?arrival\&time=$time" \
		--data-urlencode "add='C-*	Air Canada *	0'" \
		--data-urlencode "del='C-????'" \
	"| sed -r '
		s/time=$today/time=0000-00-00/g
	'"

sed "s/%{date}/$(date +'%Y-%m-%d' --date='+1 day 00:00')/g" <<-"SQL" | query
	USE flederwiesel_fra-schedule;

	SELECT `id` INTO @uid FROM `users` WHERE `name`='flederwiesel';

	INSERT INTO `watchlist-notifications`(`watch`, `flight`)

	SELECT `flights`.`id`, `watchlist`.`id`
	FROM `flights` AS `flights`
	LEFT JOIN `aircrafts`
	       ON `aircrafts`.`id`=`flights`.`aircraft`
	LEFT JOIN (
					SELECT `watchlist`.`id`, `watchlist`.`reg`
					FROM `watchlist` AS `watchlist`
					WHERE `reg`='ZS-SNC') AS `watchlist`
	       ON `watchlist`.`reg`=`aircrafts`.`reg`
	WHERE `aircraft`=
		(SELECT `id` FROM `aircrafts` AS `aircraft` WHERE `reg`='ZS-SNC')
SQL

# DON'T USE TABS AT THE BEGINNING OF add/del POST VALUES!
check "5" browse "$url/?arrival\&time=$time" \
		--data-urlencode "del='ZS-SNC'" \
	"| sed -r '
		s/time=$today/time=0000-00-00/g
	'"

check "6" browse "$url/?arrival\&time=$time" \
	--user-agent "'Opera/9.80 (Android 2.3.7; Linux; Opera Mobi/46154) Presto/2.11.355 Version/12.10'" \
	"| sed -r '
		s/time=$today/time=0000-00-00/g
	'"
