#!/bin/bash

# drop/re-create database
initdb

mailtodisk --add uid-1@example.com "$mailfile"

###############################################################################

check "0" browse -X POST "$url/?req=register"

csrftoken=$(
	browse "$url/?req=register" |
	sed -nr '/name="CSRFToken"/ { s/.*value="([^"]+)".*/\1/g; p }'
)

check "1" browse "$url/?lang=de"
check "2" browse "$url/?req=register\&stopforumspam=${FRA_FLUGPLAN_HOST}" \
		" | sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"

check "3" browse "$url/?req=register" \
		--data-urlencode "email=uid-1@example.com" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1"

token=$(query --execute="USE fra-flugplan;
	SELECT token FROM users WHERE name='uid-1'" | sed s/'[ \r\n]'//g)

check "4" browse "$url/?req=activate" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token"

check "5" browse "$url/?req=login" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz"

check "6" browse "$url/?req=profile"

check "7" browse "$url/?req=profile\&dispinterval" \
	--data-urlencode "tm-=-900" \
	--data-urlencode "tm%2b=7200" \
	--data-urlencode "tt-=-900" \
	--data-urlencode "tt%2b=28800" \
	--data-urlencode "submit=interval"

check "8" browse "$url/?req=profile\&notifinterval"

check "9" browse "$url/?req=profile" \
	--data-urlencode "from=00:00" \
	--data-urlencode "until=24:00" \
	--data-urlencode "timefmt=%c" \
	--data-urlencode "submit=notifications" \
	"|" sed -r "'s/[0-9]{2}\.[0-9]{2}\.[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}/00.00.0000 00:00:00/g'"

check "10" browse "$url/?req=profile" \
	--data-urlencode "from=08:00" \
	--data-urlencode "until=22:00" \
	--data-urlencode "timefmt=" \
	--data-urlencode "submit=notifications" \
	"|" sed -r "'s/\+0 [0-9]{2}:[0-9]{2}/+0 00:00/g'"

check "11" browse "$url/?req=profile" \
	--data-urlencode "from=08:00" \
	--data-urlencode "until=22:00" \
	--data-urlencode "submit=notifications" \
	"|" sed -r "'s/\+0 [0-9]{2}:[0-9]{2}/+0 00:00/g'"

sed "s/%{date}/$(date +'%Y-%m-%d' --date='+1 day 00:00')/g" <<-"SQL" | query
	USE fra-flugplan;

	SELECT `id` INTO @uid2
	FROM `users`
	WHERE `name`='uid-1';

	# watchlist
	INSERT INTO `watchlist`(`id`, `user`, `notify`, `reg`, `comment`)
	VALUES
	(2, @uid2, TRUE, 'C-GFAH', 'Air Canada - 932');

	INSERT INTO `models`(`icao`)
	VALUES ('B77W'), ('A333'), ('A346'), ('A310');

	INSERT INTO `aircrafts`(`model`, `reg`)
	VALUES
	((SELECT `id` FROM `models` WHERE `icao` = 'B77W'), 'B-KPE'),
	((SELECT `id` FROM `models` WHERE `icao` = 'A333'), 'C-GFAH'),
	((SELECT `id` FROM `models` WHERE `icao` = 'A346'), 'ZS-SNC'),
	((SELECT `id` FROM `models` WHERE `icao` = 'A310'), 'C-GSAT');

	INSERT INTO `airlines`(`code`)
	VALUES ('SA'), ('CX'), ('AC'), ('TS');

	INSERT INTO `airports`(`iata`, `icao`, `name`)
	VALUES
	('JNB', 'FAOR', ''),
	('HKG', 'VHHH', ''),
	('YUL', 'CYUL', ''),
	('YVR', 'CYVR', '');

	INSERT INTO `flights`
	(
		`type`, `direction`, `airline`, `code`,
		`scheduled`, `expected`, `airport`, `model`, `aircraft`
	)
	VALUES
	(
		'P', 'arrival',
		(SELECT `id` FROM `airlines` WHERE `code`='SA'), '260',
		'%{date} 06:15', NULL,
		(SELECT `id` FROM `airports` WHERE `icao`='FAOR'),
		(SELECT `id` FROM `models` WHERE `icao`='A346'),
		(SELECT `id` FROM `aircrafts` WHERE `reg`='ZS-SNC')
	),
	(
		'P', 'arrival',
		(SELECT `id` FROM `airlines` WHERE `code`='CX'), '289',
		'%{date} 06:20', NULL,
		(SELECT `id` FROM `airports` WHERE `icao`='VHHH'),
		(SELECT `id` FROM `models` WHERE `icao`='B77W'),
		(SELECT `id` FROM `aircrafts` WHERE `reg`='B-KPE')
	),
	(
		'P', 'arrival',
		(SELECT `id` FROM `airlines` WHERE `code`='AC'), '874',
		'%{date} 07:00', NULL,
		(SELECT `id` FROM `airports` WHERE `icao`='CYUL'),
		(SELECT `id` FROM `models` WHERE `icao`='A333'),
		(SELECT `id` FROM `aircrafts` WHERE `reg`='C-GFAH')
	),
	(
		'P', 'arrival',
		(SELECT `id` FROM `airlines` WHERE `code`='TS'), 'XXX',
		'%{date} 07:00', NULL,
		(SELECT `id` FROM `airports` WHERE `icao`='CYVR'),
		(SELECT `id` FROM `models` WHERE `icao`='A310'),
		(SELECT `id` FROM `aircrafts` WHERE `reg`='C-GSAT')
	);
SQL

check "12" browse "$url"

check "13" browse "$url/?req=profile\&photodb"

check "14" browse "$url/?req=profile\&photodb" \
	--data-urlencode "submit=photodb" \
	--data-urlencode "photodb=jetphotos.com"

check "15" browse "$url"
