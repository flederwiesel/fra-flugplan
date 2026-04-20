# preparation #################################################################

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

		INSERT INTO `users`
		(
			`id`, `name`, `email`, `salt`, `passwd`, `language`
		)
		VALUES
		(
			1, 'uid-1', 'uid-1@example.com',
			'cf78aafd5c5410b7b12c2794a52cda1bccd01316f30df57aa29c5609ba979c15',
			'c4ae99aa0209ce5bea9687cf0548d8ebc942ba14e166c45957a876bcec194fed', # elvizzz
			'en'
		);

		# grant user permissions
		INSERT INTO `membership`(`user`, `group`)
					VALUES((SELECT `id` FROM `users`  WHERE `name`='uid-1'),
							(SELECT `id` FROM `groups` WHERE `name`='addflights'));
	SQL
)

# /preparation ################################################################

time=$(rawurlencode "$(date +'%Y-%m-%d %H:%M:%S' --date='0 days 23:59')")
today="$(date +'%Y-%m-%d' --date='23:55')"

test_1() {
	browse --store-csrf-token \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		"$url/?req=login&time=$time" > >(sed -r "s/time=$today/time=0000-00-00/g")
}

test_2() {
	browse "$url/?arrival&time=$time" |
	sed -r "s/time=$today/time=0000-00-00/g"
}

test_3() {
	add=$(
		# DON'T USE TABS AT THE BEGINNING OF add/del POST VALUES!
		cat <<-"EOF"
			ZS-SNC	South African Airways - Star Alliance	1
			C-????	Air Canada ?	0
			C-FDAT	Air Transat - A310	1
			/C-G(TSTS[FHWY]|[FLPS]AT)/	Air Transat - A310	1
		EOF
	)

	browse --with-csrf-token \
		--data-urlencode "add=$add" \
		"$url/?arrival&time=$time" |
	sed -r "s/time=$today/time=0000-00-00/g"
}

test_4() {
	browse --with-csrf-token \
		--data-urlencode "add=C-*	Air Canada *	0" \
		--data-urlencode "del=C-????" \
		"$url/?arrival&time=$time" |
	sed -r "s/time=$today/time=0000-00-00/g"
}

### add existing reg

sqlInsertWatchlistNotifications() {
	query fra-flugplan < <(
		sed "s/%{date}/$(date +'%Y-%m-%d' --date='+1 day 00:00')/g" <<-"SQL"
			SELECT `id` INTO @uid FROM `users` WHERE `name`='uid-1';

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
				(SELECT `id` FROM `aircrafts` AS `aircraft` WHERE `reg`='ZS-SNC');

			UPDATE `users`
			SET
				`notification-from`='00:00',
				`notification-until`='24:00'
			WHERE
				`id`=@uid;
		SQL
	)
}

test_4_0() {
	sqlInsertWatchlistNotifications

	browse --with-csrf-token \
		--data-urlencode "add=ZS-SNC	SAA - Star Alliance	1" \
		"$url/?arrival&time=$time" |
	sed -r "s/time=$today/time=0000-00-00/g"
}

# del+add same reg

test_4_1() {
	browse --with-csrf-token \
		--data-urlencode "del=ZS-SNC" \
		--data-urlencode "add=ZS-SNC	SAA - Star Alliance	1" \
		"$url/?arrival&time=$time" |
	sed -r "s/time=$today/time=0000-00-00/g"
}

### upd+add same reg

test_4_2() {
	add=$(
		# DON'T USE TABS AT THE BEGINNING OF add/del POST VALUES!
		cat <<-"EOF"
			ZS-SNC	South African Airways - Star Alliance	1
			ZS-SNC	South African Airways - Star Alliance	1
		EOF
	)

	browse --with-csrf-token \
		--data-urlencode "upd=ZS-SNC	ZS-SNC	ZS-SNC	African Airways - Star Alliance	0" \
		--data-urlencode "add=$add" \
		"$url/?arrival&time=$time" |
	sed -r "s/time=$today/time=0000-00-00/g"
}

### add+upd same reg

test_4_3() {
	browse --with-csrf-token \
		--data-urlencode "add=C-FDAT	Air Transat - A310	1" \
		--data-urlencode "upd=C-FDAT	C-FDAT	Air Transat - A310	0" \
		"$url/?arrival&time=$time" |
	sed -r "s/time=$today/time=0000-00-00/g"
}

## del reg

test_5() {
	browse --with-csrf-token \
		--data-urlencode "del=ZS-SNC" \
		"$url/?arrival&time=$time" |
	sed -r "s/time=$today/time=0000-00-00/g"
}

test_6() {
	browse \
		--user-agent "Opera/9.80 (Android 2.3.7; Linux; Opera Mobi/46154) Presto/2.11.355 Version/12.10" \
		"$url/?arrival&time=$time" |
	sed -r "s/time=$today/time=0000-00-00/g"
}

check "1" test_1
check "2" test_2
check "3" test_3
check "4" test_4
check "4-0" test_4_0
check "4-1" test_4_1
check "4-2" test_4_2
check "4-3" test_4_3
check "5" test_5
check "6" test_6
