mailtodisk --add uid-1@example.com "$mailfile"

###############################################################################

test_1() {
	browse "$url/"
}

test_2() {
	browse --store-csrf-token "$url/?req=register&stopforumspam=${FRA_FLUGPLAN_HOST}"
}

test_3() {
	browse --with-csrf-token \
		--data-urlencode "email=uid-1@example.com" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		"$url/?req=register"
}

# $_POST from <form>
test_4() {
	token=$(query fra-flugplan <<< "SELECT token FROM users WHERE name='uid-1'")

	browse --with-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token= $token " \
		"$url/?req=activate"
}

# $_GET from mail
test_4_1() {
	query fra-flugplan <<< "UPDATE users SET token='$token' WHERE name='uid-1'"
	browse "$url/?req=activate&user=uid-1&token=$token"
}

# Silently ignore re-activation
test_4_2() {
	browse "$url/?req=activate&user=uid-1&token=$token"
}

test_5() {
	browse --with-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		"$url/?req=login"
}

test_6() {
	browse "$url/?req=profile"
}

test_7() {
	browse --with-csrf-token \
		--data-urlencode "tm-=-900" \
		--data-urlencode "tm%2b=7200" \
		--data-urlencode "tt-=-900" \
		--data-urlencode "tt%2b=28800" \
		--data-urlencode "submit=interval" \
		"$url/?req=profile&dispinterval"
}

test_8() {
	browse "$url/?req=profile&notifinterval"
}

test_9() {
	browse --with-csrf-token \
		--data-urlencode "from=00:00" \
		--data-urlencode "until=24:00" \
		--data-urlencode "timefmt=%c" \
		--data-urlencode "submit=notifications" \
		"$url/?req=profile" |
	sed -r "s/[0-9]{2}\/[0-9]{2}\/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}/00\/00\/0000 00:00:00/g"
}

test_10() {
	browse --with-csrf-token \
		--data-urlencode "from=08:00" \
		--data-urlencode "until=22:00" \
		--data-urlencode "timefmt=" \
		--data-urlencode "submit=notifications" \
		"$url/?req=profile" |
	sed -r "s/\+0 [0-9]{2}:[0-9]{2}/+0 00:00/g"
}

test_11() {
	browse --with-csrf-token \
		--data-urlencode "from=08:00" \
		--data-urlencode "until=22:00" \
		--data-urlencode "submit=notifications" \
		"$url/?req=profile" |
	sed -r "s/\+0 [0-9]{2}:[0-9]{2}/+0 00:00/g"
}

sqlInsertIntoWatchlist() {
	query fra-flugplan <<-"SQL"
		SELECT `id` INTO @uid
		FROM `users`
		WHERE `name`='uid-1';

		# watchlist
		INSERT INTO `watchlist`(`user`, `notify`, `reg`, `comment`)
		VALUES
		(@uid, TRUE, 'C-GFAH', 'Air Canada - 932');
	SQL
}

sqlInsertIntoFlights() {
	query fra-flugplan < <(
		sed "s/%{date}/$(date +'%Y-%m-%d' --date='+1 day 00:00')/g" <<-"SQL"
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
	)
}

test_12() {
	sqlInsertIntoWatchlist
	sqlInsertIntoFlights

	browse "$url"
}

test_13() {
	browse "$url/?req=profile&photodb"
}

test_14() {
	browse --with-csrf-token \
		--data-urlencode "submit=photodb" \
		--data-urlencode "photodb=jetphotos.com" \
		"$url/?req=profile&photodb"
}

test_15() {
	browse "$url"
}
