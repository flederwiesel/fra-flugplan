#!/bin/bash

# Generate test suite `test_getflights.sh` by copying test setup and helpers
# from `exit`..EOF, then generate test cases and write both to output file.

set -euo pipefail

readonly SCRIPTDIR=$(dirname "${BASH_SOURCE[0]}")
readonly BASENAME=$(basename "${BASH_SOURCE[0]}")
readonly PRJDIR=$(git -C "$SCRIPTDIR" rev-parse --show-toplevel)

{
sed '1,/^exit # testsuite:/d' "${BASH_SOURCE[0]}"

# Some test cases require patching before testing the getflights endpoint
exec_1_0500_getflights() {
	query fra-flugplan <<-"SQL"
		UPDATE `users`
		SET `notification-timefmt`='%A, %c'
		WHERE `name`='uid-2'
	SQL
}

# From bulk INSERT in "fra-flugplan.sql" we do not get `previous` -
# even for `num` > 1, where normally this would be NOT NULL.
# Need to check for this also...
# '0000-00-00 22:30:00' -> NULL
exec_1_1000_getflights() {
	query fra-flugplan <<-"SQL"
		UPDATE `visits`
		SET `previous` = NULL
		WHERE `aircraft` = (
			SELECT `id`
			FROM `aircrafts`
			WHERE `reg` = 'CS-TNP'
		)
	SQL
}

# Set another notification timefmt
exec_1_1200_getflights() {
	query fra-flugplan <<-"SQL"
		UPDATE `users`
		SET `notification-timefmt`='%A, %d. %B %Y %H:%M',
			`language`='de'
		WHERE `name`='uid-2'
	SQL
}

for d in {0..1}; do
	for t in {05..23}; do
		# Query string for arrival, departure, getflights
		timespec="+$d days $t:00"

		for name in getflights flights notifications visits arrival departure; do
			testcase="test_${d}_${t}00_${name}"

			case $name in
			arrival|departure)
				;;
			*)
				echo "testcase_fileext[$testcase]=txt"
				;;
			esac

			cat <<-TESTCASE
				$testcase() {
				$(
					case $name in
					arrival|departure)
						sed "s/^/\t/g" <<-TEST
							browse "\$url/?$name&time=\$(isotime '$timespec')" |
							strsubst RE_SCHEDULE
						TEST
						;;
					getflights)
						declare -f "exec_${d}_${t}00_${name}" |
						sed '1,/^{\s*$/d; $d'

						echo -e "\tgetflights \"\$(isotime '$timespec')\""
						;;
					*)
						echo -e "\tquery_$name"
						;;
					esac
				)
				}
			TESTCASE
		done
	done
done

for d in {2..3}
do
	t=05

	for name in getflights notifications; do
		testcase="test_${d}_${t}00_${name}"

		# Query string for getflights
		timespec="+$d days $t:00"

		cat <<-TEST
			testcase_fileext[$testcase]=txt
			$testcase() {
			$(
				if [ "$name" = getflights ]; then
					echo -e "\tgetflights \"\$(isotime '$timespec')\""
				else
					echo -e "\tquery_notifications"
				fi
			)
			}
		TEST
	done
done

cat <<"TEST"
testcase_fileext[test_visits]=txt
test_visits() {
	query_visits
}
TEST
} > "$PRJDIR/tests/$BASENAME"

exit # testsuite: ==============================================================
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
