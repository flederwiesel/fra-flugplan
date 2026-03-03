#!/bin/bash

# Check whether schedule is displayed correctly whether user is (not)
# logged in and device type dependent lookback/lookahead.

# User agent strings for testing lookback and lookahead:
# * Mobile Safari 12@iPad
readonly TABLET="'Mozilla/5.0 (iPad; CPU OS 12_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1 Mobile/15E148 Safari/604.1'"
# * iOS 10@iPhone 7 Plus
readonly PHONE="'Mozilla/5.0 (iphone 7Plus; CPU iphone OS 10_3_2 like Mac IS X) AppleWebKit/603.24 (KHTML, like Gecko) Version/10.0 Mobile/14F89 Safari/8536.25'"

# drop/re-create database
initdb && rm -f .COOKIES

function querytime() {
	rawurlencode "$(date +'%Y-%m-%dT%H:%M:%S%z' --date="$1")"
}

today="$(date +'%Y-%m-%d')"

# preparation #################################################################

sed "
	s/%{YYYYmmd_0}/$(date +'%Y-%m-%d' --date='+0 days 00:00')/g
	s/%{YYYYmmd_1}/$(date +'%Y-%m-%d' --date='+1 days 00:00')/g
	s/%{YYYYmmd_7}/$(date +'%Y-%m-%d' --date='+7 days 00:00')/g
" <<-"SQL" | query
	USE fra-flugplan;

	INSERT INTO `models`(`icao`)
	VALUES ('A310'), ('A333'), ('A343'), ('A346'), ('B77W');

	SELECT `id` INTO @A310 FROM `models` WHERE `icao`='A310';
	SELECT `id` INTO @A333 FROM `models` WHERE `icao`='A333';
	SELECT `id` INTO @A343 FROM `models` WHERE `icao`='A343';
	SELECT `id` INTO @A346 FROM `models` WHERE `icao`='A346';
	SELECT `id` INTO @B77W FROM `models` WHERE `icao`='B77W';

	INSERT INTO `aircrafts`(`model`, `reg`)
	VALUES
	(@A310, 'C-GSAT'),
	(@A333, 'C-GFAH'),
	(@A333, 'C-GHLM'),
	(@A343, 'ZS-SXD'),
	(@A346, 'ZS-SNC'),
	(@B77W, 'B-KPE');

	SELECT `id` INTO @BKPE FROM `aircrafts` WHERE `reg`='B-KPE';
	SELECT `id` INTO @CGFAH FROM `aircrafts` WHERE `reg`='C-GFAH';
	SELECT `id` INTO @CGHLM FROM `aircrafts` WHERE `reg`='C-GHLM';
	SELECT `id` INTO @CGSAT FROM `aircrafts` WHERE `reg`='C-GSAT';
	SELECT `id` INTO @ZSSNC FROM `aircrafts` WHERE `reg`='ZS-SNC';
	SELECT `id` INTO @ZSSXD FROM `aircrafts` WHERE `reg`='ZS-SXD';

	INSERT INTO `airlines`(`code`)
	VALUES ('AC'), ('CX'), ('SA'), ('TS');

	SELECT `id` INTO @AC FROM `airlines` WHERE `code`='AC';
	SELECT `id` INTO @CX FROM `airlines` WHERE `code`='CX';
	SELECT `id` INTO @SA FROM `airlines` WHERE `code`='SA';
	SELECT `id` INTO @TS FROM `airlines` WHERE `code`='TS';

	INSERT INTO `airports`(`iata`, `icao`, `name`)
	VALUES
	('HKG', 'VHHH', ''),
	('JNB', 'FAOR', ''),
	('YUL', 'CYUL', ''),
	('YVR', 'CYVR', ''),
	('YYZ', 'CYYZ', '');

	SELECT `id` INTO @CYUL FROM `airports` WHERE `icao`='CYUL';
	SELECT `id` INTO @CYVR FROM `airports` WHERE `icao`='CYVR';
	SELECT `id` INTO @CYYZ FROM `airports` WHERE `icao`='CYYZ';
	SELECT `id` INTO @VHHH FROM `airports` WHERE `icao`='VHHH';
	SELECT `id` INTO @FAOR FROM `airports` WHERE `icao`='FAOR';

	INSERT INTO `flights`
	(
		`type`, `direction`, `airline`, `code`,
		`scheduled`, `expected`, `airport`, `model`, `aircraft`
	)
	VALUES
	('P', 'arrival', @SA, '260', '%{YYYYmmd_0} 06:15', NULL, @FAOR, @A343, @ZSSXD),
	('P', 'arrival', @SA, '260', '%{YYYYmmd_1} 06:15', NULL, @FAOR, @A346, @ZSSNC),
	('P', 'arrival', @CX, '289', '%{YYYYmmd_1} 06:20', NULL, @VHHH, @B77W, @BKPE),
	('P', 'arrival', @AC, '874', '%{YYYYmmd_1} 07:00', NULL, @CYUL, @A333, @CGFAH),
	('P', 'arrival', @TS, '190', '%{YYYYmmd_7} 06:15', NULL, @CYVR, @A310, @CGSAT),
	('P', 'arrival', @AC, '840', '%{YYYYmmd_7} 07:00', NULL, @CYYZ, @A333, @CGHLM);

	INSERT INTO `users`
	(
		`id`, `name`, `email`, `language`,
		`salt`,
		`passwd`,
		`tt-`, `tt+`, `tm-`, `tm+`
	)
	VALUES
	(
		1, 'uid-1', 'uid-1@example.com', 'en',
		'cf78aafd5c5410b7b12c2794a52cda1bccd01316f30df57aa29c5609ba979c15',
		'c4ae99aa0209ce5bea9687cf0548d8ebc942ba14e166c45957a876bcec194fed',
#		-0T00:15, +3T00:00, -0T00:30, +1T00:00
		-900,     259200,   -1800,    86400
	);

	SELECT `id` into @users FROM `groups` WHERE `name` = 'users';


	INSERT INTO `membership`(`user`, `group`)
	VALUES(1, @users);
SQL

csrftoken=$(
	browse "$url/?req=register" |
	sed -nr '/name="CSRFToken"/ { s/.*value="([^"]+)".*/\1/g; p }'
)

# /preparation ################################################################

declare -Ar user_agents=(
	[desktop]=
	[phone]="'Mozilla/5.0 (iphone 7Plus; CPU iphone OS 10_3_2 like Mac IS X) AppleWebKit/603.24 (KHTML, like Gecko) Version/10.0 Mobile/14F89 Safari/8536.25'"
	[tablet]="'Mozilla/5.0 (iPad; CPU OS 12_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1 Mobile/15E148 Safari/604.1'"
)

for u in "" user
do
	for t in 06:15 06:30 06:45 07:00
	do
		for d in desktop phone tablet
		do
			ua="${user_agents[$d]}"

			check "${t//:/}_${d:0:1}${u:+_u}" \
			browse "$url/?arrival\&time=$(querytime $t)" \
				${ua:+--user-agent "$ua"} \
				"| sed -r '
					s/time=$today/time=0000-00-00/g
					s/(%2B|-)[0-9]{4}/%2B0000/g
				'"
		done
	done

	if [ -z "$user" ]; then
		browse "$url/?req=login" \
			--data-urlencode "user=uid-1" \
			--data-urlencode "passwd=elvizzz" > /dev/null
	fi
done
