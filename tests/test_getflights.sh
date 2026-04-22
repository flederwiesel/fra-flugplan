# DO NOT CHANGE THIS FILE. This file is generated automatically.
# If you want to edit this file, change and execute its counterpart in
# "$SCRIPTDIR/generators" instead, redirecting the output here.

mailtodisk --add uid-1@example.com "$mailfile"
mailtodisk --add uid-2@example.com "$mailfile"
mailtodisk --add flugplan-admin@example.com "$mailfile"

# Insert some default airlines/aicrafts, as well as users and watched regs
query fra-flugplan < <(
	cat "$PRJDIR/sql/data/countries.sql" \
		"$PRJDIR/sql/data/airlines.sql" \
		"$PRJDIR/sql/data/airports.sql" \
		"$PRJDIR/sql/data/models.sql"
)

query fra-flugplan <<-"SQL"
	# Get predictive values...
	ALTER TABLE `airports` AUTO_INCREMENT=2147483642;

	INSERT INTO `users`(`name`, `email`, `salt`, `passwd`, `language`, `ip`)
	VALUES
	(
		'uid-1',
		'uid-1@example.com',
		'cf78aafd5c5410b7b12c2794a52cda1bccd01316f30df57aa29c5609ba979c15',
		'c4ae99aa0209ce5bea9687cf0548d8ebc942ba14e166c45957a876bcec194fed',
		'en',
		'::1'
	),
	(
		'uid-2',
		'uid-2@example.com',
		'cf78aafd5c5410b7b12c2794a52cda1bccd01316f30df57aa29c5609ba979c15',
		'c4ae99aa0209ce5bea9687cf0548d8ebc942ba14e166c45957a876bcec194fed',
		'en',
		'::1'
	);

	SELECT `id` INTO @uid1
	FROM `users`
	WHERE `name`='uid-1';

	SELECT `id` INTO @uid2
	FROM `users`
	WHERE `name`='uid-2';

	INSERT INTO `membership`(`user`, `group`)
	(
		SELECT @uid1 AS `user`, `id` AS `group`
		FROM `groups`
		WHERE `name` IN ('admin', 'addflights', 'specials', 'users')
	);

	INSERT INTO `membership`(`user`, `group`)
	(
		SELECT @uid2 AS `user`, `id` AS `group`
		FROM `groups`
		WHERE `name` IN ('admin', 'addflights', 'specials', 'users')
	);

	# Set notification times
	UPDATE `users`
	SET `notification-from` = '05:00',
		`notification-until` = '23:00'
	WHERE `id`=@uid1;

	UPDATE `users`
	SET `notification-from` = '08:00',
		`notification-until` = '22:00'
	WHERE `id`=@uid2;

	# watchlist
	INSERT INTO `watchlist`(`id`, `user`, `notify`, `reg`, `comment`)
	VALUES
	(1, @uid1, TRUE, 'ZS-SNC', 'South African Airways - Star Alliance'),
	(6, @uid1, TRUE, 'N116UA', 'United'),
	(2, @uid2, TRUE, 'C-GFAH', 'Air Canada - 932'),
	(3, @uid2, TRUE, 'C-GHKW', 'Air Canada - 936'),
	(4, @uid2, TRUE, 'C-GHLM', 'Air Canada - 938'),
	(5, @uid2, TRUE, 'CS-TNP', 'TAP - Star Alliance'),
	(7, @uid2, FALSE, 'ZS-SNB', 'South African Airways'),
	(8, @uid2, TRUE, 'B-KPF', 'Cathay Pacific - Asias world city GRÜN'),
	(9, @uid2, TRUE, '/9K-GB[AB]/', 'State of Kuwait - A345');
SQL

readonly YYYYmmdd_0=$(date +'%Y%m%d')
readonly YYYYmmdd_1=$(date +'%Y%m%d' --date="+1 days")
readonly YYYYmmdd_2=$(date +'%Y%m%d' --date="+2 days")
readonly YYYY_mm_dd_0=$(date +'%Y-%m-%d')
readonly YYYY_mm_dd_1=$(date +'%Y-%m-%d' --date="+1 days")
readonly YYYY_mm_dd_2=$(date +'%Y-%m-%d' --date="+2 days")
readonly YYYY_mm_dd_3=$(date +'%Y-%m-%d' --date="+3 days")

readonly RE_SCHEDULE="
	s/$YYYY_mm_dd_0/0000-00-00/g
	s/$YYYY_mm_dd_1/0000-00-01/g
	s/(T[0-9]{2}%3A[0-9]{2}%3A00%2B0)[12](00)/\10\2/g
"

isotime() {
	local t=${1?}

	# Format as ISO8601 and urlencode ":", "+"
	date --date="$t" +"%Y-%m-%dT%H:%M:%S%z" |
	sed "s/:/%3a/g; s/+/%2b/g"
}

getflights() {
	local now=$1

	browse "$url/getflights.php?prefix=${FRA_FLUGPLAN_HOST}&time=$now&debug=url,json,jflights,sql&fmt=txt" |
	sed -r "
		s/Dauer: [0-9]+.[0-9]+s/Dauer: 0.000s/g
		s/$YYYY_mm_dd_0/0000-00-00/g
		s/$YYYY_mm_dd_1/0000-00-01/g
		s/$YYYY_mm_dd_2/0000-00-02/g
		s/$YYYY_mm_dd_3/0000-00-03/g
		s/(T[0-9]{2}:[0-9]{2}:00\+0)[12](00)/\10\2/g
		s/(T[0-9]{2}%3A[0-9]{2}%3A00%2B0)[12](00)/\10\2/g
		s/([ad])[0-9]{8}(ac|cx|go|ku|lh|s[aq]|t[kp]|ua)([0-9]+)/\100000000\2\3/g
		s#((Mon|Diens|Donners|Frei|Sams|Sonn)tag|Mittwoch), [0-9]+\. (Januar|Februar|M.rz|April|Mai|Ju[nl]i|August|(Sept|Nov|Dez)ember|Oktober) [0-9]+#Tag, 00. Monat 0000#g
		s#((Mon|Tues|Wednes|Thurs|Fri|Satur|Sun)day), [0-9]+/[0-9]+/[0-9]+#Day, 00/00/00#g
		s#(FROM_UNIXTIME\()[0-9]+#\10#g
		s#(https://[^/]+/).*/(www.frankfurt-airport.com/.*)#\1.../\2#g
		s#(\`(current|previous)\`=)[0-9]+#\10#g
		s#^/\*\[Q[0-9]+\]\*/ *##g
		/: Inserted airport/d
		s/.*\([0-9]+\): *(Inserted )/\1/g
	"
}

query_flights() {
	query fra-flugplan <<-"SQL" > >(
		sed -r "
			s/arrival/A/g
			s/departure/D/g
			s/$YYYY_mm_dd_0/0000-00-00/g
			s/$YYYY_mm_dd_1/0000-00-01/g
			s/$YYYY_mm_dd_2/0000-00-02/g
			s/$YYYY_mm_dd_3/0000-00-03/g
		"
	)
		SELECT
			`flights`.`direction`,
			`flights`.`scheduled`,
			`flights`.`estimated`,
			`airlines`.`code`,
			`flights`.`code`,
			`airports`.`iata` AS `airport:iata`,
			`airports`.`icao` AS `airport:icao`,
			`models`.`icao` AS `model`,
			`aircrafts`.`reg` AS `aircraft`
		FROM `flights`
		LEFT JOIN `airlines` ON `airlines`.`id` = `flights`.`airline`
		LEFT JOIN `airports` ON `airports`.`id` = `flights`.`airport`
		LEFT JOIN `aircrafts` ON `aircrafts`.`id` = `flights`.`aircraft`
		LEFT JOIN `models` ON `models`.`id` = `flights`.`model`
	SQL
}

query_notifications() {
	query fra-flugplan <<-"SQL" > >(
		sed -r "
			s/$YYYY_mm_dd_0/0000-00-00/g
			s/$YYYY_mm_dd_1/0000-00-01/g
		"
	)
		SELECT `flight`, `watch`, `notified`
		FROM `watchlist-notifications`
		ORDER BY `flight`
	SQL
}

query_visits() {
	query fra-flugplan <<-"SQL" > >(
		sed -r "
			s/$YYYY_mm_dd_0/0000-00-00/g
			s/$YYYY_mm_dd_1/0000-00-01/g
			s/$YYYY_mm_dd_2/0000-00-02/g
		"
	)
		SELECT
			`aircrafts`.`reg`,
			`visits`.`num`,
			`visits`.`current`,
			`visits`.`previous`
		FROM `visits`
		LEFT JOIN `aircrafts` ON `aircrafts`.`id` = `visits`.`aircraft`
		ORDER BY `reg`
	SQL
}
# @fileext=txt
test_0_0500_getflights() {
	getflights "$(isotime '+0 days 05:00')"
}
# @fileext=txt
test_0_0500_flights() {
	query_flights
}
# @fileext=txt
test_0_0500_notifications() {
	query_notifications
}
# @fileext=txt
test_0_0500_visits() {
	query_visits
}
test_0_0500_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 05:00')" |
	strsubst RE_SCHEDULE
}
test_0_0500_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 05:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_0600_getflights() {
	getflights "$(isotime '+0 days 06:00')"
}
# @fileext=txt
test_0_0600_flights() {
	query_flights
}
# @fileext=txt
test_0_0600_notifications() {
	query_notifications
}
# @fileext=txt
test_0_0600_visits() {
	query_visits
}
test_0_0600_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 06:00')" |
	strsubst RE_SCHEDULE
}
test_0_0600_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 06:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_0700_getflights() {
	getflights "$(isotime '+0 days 07:00')"
}
# @fileext=txt
test_0_0700_flights() {
	query_flights
}
# @fileext=txt
test_0_0700_notifications() {
	query_notifications
}
# @fileext=txt
test_0_0700_visits() {
	query_visits
}
test_0_0700_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 07:00')" |
	strsubst RE_SCHEDULE
}
test_0_0700_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 07:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_0800_getflights() {
	getflights "$(isotime '+0 days 08:00')"
}
# @fileext=txt
test_0_0800_flights() {
	query_flights
}
# @fileext=txt
test_0_0800_notifications() {
	query_notifications
}
# @fileext=txt
test_0_0800_visits() {
	query_visits
}
test_0_0800_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 08:00')" |
	strsubst RE_SCHEDULE
}
test_0_0800_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 08:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_0900_getflights() {
	getflights "$(isotime '+0 days 09:00')"
}
# @fileext=txt
test_0_0900_flights() {
	query_flights
}
# @fileext=txt
test_0_0900_notifications() {
	query_notifications
}
# @fileext=txt
test_0_0900_visits() {
	query_visits
}
test_0_0900_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 09:00')" |
	strsubst RE_SCHEDULE
}
test_0_0900_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 09:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_1000_getflights() {
	getflights "$(isotime '+0 days 10:00')"
}
# @fileext=txt
test_0_1000_flights() {
	query_flights
}
# @fileext=txt
test_0_1000_notifications() {
	query_notifications
}
# @fileext=txt
test_0_1000_visits() {
	query_visits
}
test_0_1000_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 10:00')" |
	strsubst RE_SCHEDULE
}
test_0_1000_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 10:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_1100_getflights() {
	getflights "$(isotime '+0 days 11:00')"
}
# @fileext=txt
test_0_1100_flights() {
	query_flights
}
# @fileext=txt
test_0_1100_notifications() {
	query_notifications
}
# @fileext=txt
test_0_1100_visits() {
	query_visits
}
test_0_1100_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 11:00')" |
	strsubst RE_SCHEDULE
}
test_0_1100_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 11:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_1200_getflights() {
	getflights "$(isotime '+0 days 12:00')"
}
# @fileext=txt
test_0_1200_flights() {
	query_flights
}
# @fileext=txt
test_0_1200_notifications() {
	query_notifications
}
# @fileext=txt
test_0_1200_visits() {
	query_visits
}
test_0_1200_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 12:00')" |
	strsubst RE_SCHEDULE
}
test_0_1200_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 12:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_1300_getflights() {
	getflights "$(isotime '+0 days 13:00')"
}
# @fileext=txt
test_0_1300_flights() {
	query_flights
}
# @fileext=txt
test_0_1300_notifications() {
	query_notifications
}
# @fileext=txt
test_0_1300_visits() {
	query_visits
}
test_0_1300_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 13:00')" |
	strsubst RE_SCHEDULE
}
test_0_1300_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 13:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_1400_getflights() {
	getflights "$(isotime '+0 days 14:00')"
}
# @fileext=txt
test_0_1400_flights() {
	query_flights
}
# @fileext=txt
test_0_1400_notifications() {
	query_notifications
}
# @fileext=txt
test_0_1400_visits() {
	query_visits
}
test_0_1400_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 14:00')" |
	strsubst RE_SCHEDULE
}
test_0_1400_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 14:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_1500_getflights() {
	getflights "$(isotime '+0 days 15:00')"
}
# @fileext=txt
test_0_1500_flights() {
	query_flights
}
# @fileext=txt
test_0_1500_notifications() {
	query_notifications
}
# @fileext=txt
test_0_1500_visits() {
	query_visits
}
test_0_1500_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 15:00')" |
	strsubst RE_SCHEDULE
}
test_0_1500_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 15:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_1600_getflights() {
	getflights "$(isotime '+0 days 16:00')"
}
# @fileext=txt
test_0_1600_flights() {
	query_flights
}
# @fileext=txt
test_0_1600_notifications() {
	query_notifications
}
# @fileext=txt
test_0_1600_visits() {
	query_visits
}
test_0_1600_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 16:00')" |
	strsubst RE_SCHEDULE
}
test_0_1600_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 16:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_1700_getflights() {
	getflights "$(isotime '+0 days 17:00')"
}
# @fileext=txt
test_0_1700_flights() {
	query_flights
}
# @fileext=txt
test_0_1700_notifications() {
	query_notifications
}
# @fileext=txt
test_0_1700_visits() {
	query_visits
}
test_0_1700_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 17:00')" |
	strsubst RE_SCHEDULE
}
test_0_1700_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 17:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_1800_getflights() {
	getflights "$(isotime '+0 days 18:00')"
}
# @fileext=txt
test_0_1800_flights() {
	query_flights
}
# @fileext=txt
test_0_1800_notifications() {
	query_notifications
}
# @fileext=txt
test_0_1800_visits() {
	query_visits
}
test_0_1800_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 18:00')" |
	strsubst RE_SCHEDULE
}
test_0_1800_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 18:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_1900_getflights() {
	getflights "$(isotime '+0 days 19:00')"
}
# @fileext=txt
test_0_1900_flights() {
	query_flights
}
# @fileext=txt
test_0_1900_notifications() {
	query_notifications
}
# @fileext=txt
test_0_1900_visits() {
	query_visits
}
test_0_1900_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 19:00')" |
	strsubst RE_SCHEDULE
}
test_0_1900_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 19:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_2000_getflights() {
	getflights "$(isotime '+0 days 20:00')"
}
# @fileext=txt
test_0_2000_flights() {
	query_flights
}
# @fileext=txt
test_0_2000_notifications() {
	query_notifications
}
# @fileext=txt
test_0_2000_visits() {
	query_visits
}
test_0_2000_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 20:00')" |
	strsubst RE_SCHEDULE
}
test_0_2000_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 20:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_2100_getflights() {
	getflights "$(isotime '+0 days 21:00')"
}
# @fileext=txt
test_0_2100_flights() {
	query_flights
}
# @fileext=txt
test_0_2100_notifications() {
	query_notifications
}
# @fileext=txt
test_0_2100_visits() {
	query_visits
}
test_0_2100_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 21:00')" |
	strsubst RE_SCHEDULE
}
test_0_2100_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 21:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_2200_getflights() {
	getflights "$(isotime '+0 days 22:00')"
}
# @fileext=txt
test_0_2200_flights() {
	query_flights
}
# @fileext=txt
test_0_2200_notifications() {
	query_notifications
}
# @fileext=txt
test_0_2200_visits() {
	query_visits
}
test_0_2200_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 22:00')" |
	strsubst RE_SCHEDULE
}
test_0_2200_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 22:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_0_2300_getflights() {
	getflights "$(isotime '+0 days 23:00')"
}
# @fileext=txt
test_0_2300_flights() {
	query_flights
}
# @fileext=txt
test_0_2300_notifications() {
	query_notifications
}
# @fileext=txt
test_0_2300_visits() {
	query_visits
}
test_0_2300_arrival() {
	browse "$url/?arrival&time=$(isotime '+0 days 23:00')" |
	strsubst RE_SCHEDULE
}
test_0_2300_departure() {
	browse "$url/?departure&time=$(isotime '+0 days 23:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_0500_getflights() {
    query fra-flugplan <<-'SQL'
UPDATE `users`
SET `notification-timefmt`='%A, %c'
WHERE `name`='uid-2'
SQL

	getflights "$(isotime '+1 days 05:00')"
}
# @fileext=txt
test_1_0500_flights() {
	query_flights
}
# @fileext=txt
test_1_0500_notifications() {
	query_notifications
}
# @fileext=txt
test_1_0500_visits() {
	query_visits
}
test_1_0500_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 05:00')" |
	strsubst RE_SCHEDULE
}
test_1_0500_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 05:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_0600_getflights() {
	getflights "$(isotime '+1 days 06:00')"
}
# @fileext=txt
test_1_0600_flights() {
	query_flights
}
# @fileext=txt
test_1_0600_notifications() {
	query_notifications
}
# @fileext=txt
test_1_0600_visits() {
	query_visits
}
test_1_0600_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 06:00')" |
	strsubst RE_SCHEDULE
}
test_1_0600_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 06:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_0700_getflights() {
	getflights "$(isotime '+1 days 07:00')"
}
# @fileext=txt
test_1_0700_flights() {
	query_flights
}
# @fileext=txt
test_1_0700_notifications() {
	query_notifications
}
# @fileext=txt
test_1_0700_visits() {
	query_visits
}
test_1_0700_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 07:00')" |
	strsubst RE_SCHEDULE
}
test_1_0700_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 07:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_0800_getflights() {
	getflights "$(isotime '+1 days 08:00')"
}
# @fileext=txt
test_1_0800_flights() {
	query_flights
}
# @fileext=txt
test_1_0800_notifications() {
	query_notifications
}
# @fileext=txt
test_1_0800_visits() {
	query_visits
}
test_1_0800_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 08:00')" |
	strsubst RE_SCHEDULE
}
test_1_0800_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 08:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_0900_getflights() {
	getflights "$(isotime '+1 days 09:00')"
}
# @fileext=txt
test_1_0900_flights() {
	query_flights
}
# @fileext=txt
test_1_0900_notifications() {
	query_notifications
}
# @fileext=txt
test_1_0900_visits() {
	query_visits
}
test_1_0900_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 09:00')" |
	strsubst RE_SCHEDULE
}
test_1_0900_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 09:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_1000_getflights() {
    query fra-flugplan <<-'SQL'
UPDATE `visits`
SET `previous` = NULL
WHERE `aircraft` = (
SELECT `id`
FROM `aircrafts`
WHERE `reg` = 'CS-TNP'
)
SQL

	getflights "$(isotime '+1 days 10:00')"
}
# @fileext=txt
test_1_1000_flights() {
	query_flights
}
# @fileext=txt
test_1_1000_notifications() {
	query_notifications
}
# @fileext=txt
test_1_1000_visits() {
	query_visits
}
test_1_1000_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 10:00')" |
	strsubst RE_SCHEDULE
}
test_1_1000_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 10:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_1100_getflights() {
	getflights "$(isotime '+1 days 11:00')"
}
# @fileext=txt
test_1_1100_flights() {
	query_flights
}
# @fileext=txt
test_1_1100_notifications() {
	query_notifications
}
# @fileext=txt
test_1_1100_visits() {
	query_visits
}
test_1_1100_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 11:00')" |
	strsubst RE_SCHEDULE
}
test_1_1100_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 11:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_1200_getflights() {
    query fra-flugplan <<-'SQL'
UPDATE `users`
SET `notification-timefmt`='%A, %d. %B %Y %H:%M',
`language`='de'
WHERE `name`='uid-2'
SQL

	getflights "$(isotime '+1 days 12:00')"
}
# @fileext=txt
test_1_1200_flights() {
	query_flights
}
# @fileext=txt
test_1_1200_notifications() {
	query_notifications
}
# @fileext=txt
test_1_1200_visits() {
	query_visits
}
test_1_1200_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 12:00')" |
	strsubst RE_SCHEDULE
}
test_1_1200_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 12:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_1300_getflights() {
	getflights "$(isotime '+1 days 13:00')"
}
# @fileext=txt
test_1_1300_flights() {
	query_flights
}
# @fileext=txt
test_1_1300_notifications() {
	query_notifications
}
# @fileext=txt
test_1_1300_visits() {
	query_visits
}
test_1_1300_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 13:00')" |
	strsubst RE_SCHEDULE
}
test_1_1300_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 13:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_1400_getflights() {
	getflights "$(isotime '+1 days 14:00')"
}
# @fileext=txt
test_1_1400_flights() {
	query_flights
}
# @fileext=txt
test_1_1400_notifications() {
	query_notifications
}
# @fileext=txt
test_1_1400_visits() {
	query_visits
}
test_1_1400_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 14:00')" |
	strsubst RE_SCHEDULE
}
test_1_1400_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 14:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_1500_getflights() {
	getflights "$(isotime '+1 days 15:00')"
}
# @fileext=txt
test_1_1500_flights() {
	query_flights
}
# @fileext=txt
test_1_1500_notifications() {
	query_notifications
}
# @fileext=txt
test_1_1500_visits() {
	query_visits
}
test_1_1500_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 15:00')" |
	strsubst RE_SCHEDULE
}
test_1_1500_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 15:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_1600_getflights() {
	getflights "$(isotime '+1 days 16:00')"
}
# @fileext=txt
test_1_1600_flights() {
	query_flights
}
# @fileext=txt
test_1_1600_notifications() {
	query_notifications
}
# @fileext=txt
test_1_1600_visits() {
	query_visits
}
test_1_1600_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 16:00')" |
	strsubst RE_SCHEDULE
}
test_1_1600_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 16:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_1700_getflights() {
	getflights "$(isotime '+1 days 17:00')"
}
# @fileext=txt
test_1_1700_flights() {
	query_flights
}
# @fileext=txt
test_1_1700_notifications() {
	query_notifications
}
# @fileext=txt
test_1_1700_visits() {
	query_visits
}
test_1_1700_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 17:00')" |
	strsubst RE_SCHEDULE
}
test_1_1700_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 17:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_1800_getflights() {
	getflights "$(isotime '+1 days 18:00')"
}
# @fileext=txt
test_1_1800_flights() {
	query_flights
}
# @fileext=txt
test_1_1800_notifications() {
	query_notifications
}
# @fileext=txt
test_1_1800_visits() {
	query_visits
}
test_1_1800_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 18:00')" |
	strsubst RE_SCHEDULE
}
test_1_1800_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 18:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_1900_getflights() {
	getflights "$(isotime '+1 days 19:00')"
}
# @fileext=txt
test_1_1900_flights() {
	query_flights
}
# @fileext=txt
test_1_1900_notifications() {
	query_notifications
}
# @fileext=txt
test_1_1900_visits() {
	query_visits
}
test_1_1900_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 19:00')" |
	strsubst RE_SCHEDULE
}
test_1_1900_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 19:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_2000_getflights() {
	getflights "$(isotime '+1 days 20:00')"
}
# @fileext=txt
test_1_2000_flights() {
	query_flights
}
# @fileext=txt
test_1_2000_notifications() {
	query_notifications
}
# @fileext=txt
test_1_2000_visits() {
	query_visits
}
test_1_2000_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 20:00')" |
	strsubst RE_SCHEDULE
}
test_1_2000_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 20:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_2100_getflights() {
	getflights "$(isotime '+1 days 21:00')"
}
# @fileext=txt
test_1_2100_flights() {
	query_flights
}
# @fileext=txt
test_1_2100_notifications() {
	query_notifications
}
# @fileext=txt
test_1_2100_visits() {
	query_visits
}
test_1_2100_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 21:00')" |
	strsubst RE_SCHEDULE
}
test_1_2100_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 21:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_2200_getflights() {
	getflights "$(isotime '+1 days 22:00')"
}
# @fileext=txt
test_1_2200_flights() {
	query_flights
}
# @fileext=txt
test_1_2200_notifications() {
	query_notifications
}
# @fileext=txt
test_1_2200_visits() {
	query_visits
}
test_1_2200_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 22:00')" |
	strsubst RE_SCHEDULE
}
test_1_2200_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 22:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_1_2300_getflights() {
	getflights "$(isotime '+1 days 23:00')"
}
# @fileext=txt
test_1_2300_flights() {
	query_flights
}
# @fileext=txt
test_1_2300_notifications() {
	query_notifications
}
# @fileext=txt
test_1_2300_visits() {
	query_visits
}
test_1_2300_arrival() {
	browse "$url/?arrival&time=$(isotime '+1 days 23:00')" |
	strsubst RE_SCHEDULE
}
test_1_2300_departure() {
	browse "$url/?departure&time=$(isotime '+1 days 23:00')" |
	strsubst RE_SCHEDULE
}
# @fileext=txt
test_2_0500_getflights() {
	getflights "$(isotime '+2 days 05:00')"
}
# @fileext=txt
test_2_0500_notifications() {
	query_notifications
}
# @fileext=txt
test_3_0500_getflights() {
	getflights "$(isotime '+3 days 05:00')"
}
# @fileext=txt
test_3_0500_notifications() {
	query_notifications
}
# @fileext=txt
test_visits() {
	query_visits
}
