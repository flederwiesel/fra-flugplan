mailtodisk --add uid-1@example.com "$mailfile"

###############################################################################

csrftoken=$(
	browse "$url/?req=register" |
	sed -nr '/name="CSRFToken"/ { s/.*value="([^"]+)".*/\1/g; p }'
)

test_1() {
	browse "$url/?lang=de"
}

test_2() {
	browse "$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}"
}

test_3() {
	browse "$url/?req=register" \
		--data-urlencode "email=uid-1@example.com" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1"
}

test_4() {
	token=$(query --execute="USE fra-flugplan;
		SELECT token FROM users WHERE name='uid-1'" | sed s/'[ \r\n]'//g)

	browse "$url/?req=activate" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token"
}

test_5() {
	browse "$url/?req=login" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz"
}

test_6() {
	browse "$url/?req=profile"
}

test_7() {
	browse "$url/?req=profile&dispinterval" \
		--data-urlencode "tm-=-900" \
		--data-urlencode "tm%2b=7200" \
		--data-urlencode "tt-=-900" \
		--data-urlencode "tt%2b=28800" \
		--data-urlencode "submit=interval"
}

test_8() {
	browse "$url/?req=profile&notifinterval"
}

test_9() {
	browse "$url/?req=profile" \
		--data-urlencode "from=00:00" \
		--data-urlencode "until=24:00" \
		--data-urlencode "timefmt=%c" \
		--data-urlencode "submit=notifications" |
	sed -r "s/[0-9]{2}\.[0-9]{2}\.[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}/00.00.0000 00:00:00/g"
}

test_10() {
	browse "$url/?req=profile" \
		--data-urlencode "from=08:00" \
		--data-urlencode "until=22:00" \
		--data-urlencode "timefmt=" \
		--data-urlencode "submit=notifications" |
	sed -r "s/\+0 [0-9]{2}:[0-9]{2}/+0 00:00/g"
}

test_11() {
	browse "$url/?req=profile" \
		--data-urlencode "from=08:00" \
		--data-urlencode "until=22:00" \
		--data-urlencode "submit=notifications" |
	sed -r "s/\+0 [0-9]{2}:[0-9]{2}/+0 00:00/g"
}

test_12() {
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

	browse "$url"
}

test_13() {
	browse "$url/?req=profile&photodb"
}

test_14() {
	browse "$url/?req=profile&photodb" \
		--data-urlencode "submit=photodb" \
		--data-urlencode "photodb=jetphotos.com"
}

test_15() {
	browse "$url"
}

check "1" test_1
check "2" test_2
check "3" test_3
check "4" test_4
check "5" test_5
check "6" test_6
check "7" test_7
check "8" test_8
check "9" test_9
check "10" test_10
check "11" test_11
check "12" test_12
check "13" test_13
check "14" test_14
check "15" test_15
