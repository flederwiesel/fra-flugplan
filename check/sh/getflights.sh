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

prefix=$(rawurlencode $(sed -r 's|https?://||g' <<<"$url"))

mailtodisk --add flederwiesel@fra-flugplan.de "$mailfile" # admin
mailtodisk --add flederwiesel@fra-flugplan.de "$mailfile" # root
mailtodisk --add hausmeister@flederwiesel.com "$mailfile" # user

###############################################################################

query < <(
	echo 'USE `fra-flugplan`;'
	cat ../sql/data/countries.sql \
		../sql/data/airlines.sql \
		../sql/data/airports.sql \
		../sql/data/models.sql
)

query <<-"SQL"
	USE `fra-flugplan`;

	# Get predictive values...
	ALTER TABLE `airports` AUTO_INCREMENT=2147483642;

	INSERT INTO `users`(`name`, `email`, `salt`, `passwd`, `language`, `ip`)
	VALUES
	(
		'root',
		'flederwiesel@fra-flugplan.de',
		'cf78aafd5c5410b7b12c2794a52cda1bccd01316f30df57aa29c5609ba979c15',
		'c4ae99aa0209ce5bea9687cf0548d8ebc942ba14e166c45957a876bcec194fed',
		'en',
		'::1'
	),
	(
		'flederwiesel',
		'hausmeister@flederwiesel.com',
		'cf78aafd5c5410b7b12c2794a52cda1bccd01316f30df57aa29c5609ba979c15',
		'c4ae99aa0209ce5bea9687cf0548d8ebc942ba14e166c45957a876bcec194fed',
		'en',
		'::1'
	);

	SELECT `id` INTO @root
	FROM `users`
	WHERE `name`='root';

	SELECT `id` INTO @flederwiesel
	FROM `users`
	WHERE `name`='flederwiesel';

	INSERT INTO `membership`(`user`, `group`)
	(
		SELECT @root AS `user`, `id` AS `group`
		FROM `groups`
		WHERE `name` IN ('admin', 'addflights', 'specials', 'users')
	);

	INSERT INTO `membership`(`user`, `group`)
	(
		SELECT @flederwiesel AS `user`, `id` AS `group`
		FROM `groups`
		WHERE `name` IN ('admin', 'addflights', 'specials', 'users')
	);

	# Set notification times
	UPDATE `users`
	SET `notification-from` = '05:00',
		`notification-until` = '23:00'
	WHERE `id`=@root;

	UPDATE `users`
	SET `notification-from` = '08:00',
		`notification-until` = '22:00'
	WHERE `id`=@flederwiesel;

	# watchlist
	INSERT INTO `watchlist`(`id`, `user`, `notify`, `reg`, `comment`)
	VALUES
	(1, @root, TRUE, 'ZS-SNC', 'South African Airways - Star Alliance'),
	(6, @root, TRUE, 'N116UA', 'United'),
	(2, @flederwiesel, TRUE, 'C-GFAH', 'Air Canada - 932'),
	(3, @flederwiesel, TRUE, 'C-GHKW', 'Air Canada - 936'),
	(4, @flederwiesel, TRUE, 'C-GHLM', 'Air Canada - 938'),
	(5, @flederwiesel, TRUE, 'CS-TNP', 'TAP - Star Alliance'),
	(7, @flederwiesel, FALSE, 'ZS-SNB', 'South African Airways'),
	(8, @flederwiesel, TRUE, 'B-KPF', 'Cathay Pacific - Asias world city GRÜN'),
	(9, @flederwiesel, TRUE, '/9K-GB[AB]/', 'State of Kuwait - A345');
SQL

YYYYmmdd_0=$(date +'%Y%m%d')
YYYYmmdd_1=$(date +'%Y%m%d' --date="+1 days")
YYYYmmdd_2=$(date +'%Y%m%d' --date="+2 days")
YYYY_mm_dd_0=$(date +'%Y-%m-%d')
YYYY_mm_dd_1=$(date +'%Y-%m-%d' --date="+1 days")
YYYY_mm_dd_2=$(date +'%Y-%m-%d' --date="+2 days")
YYYY_mm_dd_3=$(date +'%Y-%m-%d' --date="+3 days")

###############################################################################

for day in {0..1}
do
	for t in {5..23}
	do
#[ 1 == $day ] && [ 6 == $t ] && exit 1
		time=$(printf '%02u:00' $t)

		dHHMM=$(printf '%02u' $day)-$(date +'%H%M' --date="$time")
#		mkdir -p "sh/results/getflights/$dHHMM"

		offset=$(date +'%Y-%m-%d' --date="+$day days")
		now=$(date +'%Y-%m-%dT%H:%M:%S%z' --date="$offset $time")
		now=$(rawurlencode $now)

		# Check notification time format string
		case "$day $time" in
		"1 05:00")
			query <<-"SQL"
				USE fra-flugplan;

				UPDATE `users`
				SET `notification-timefmt`='%A, %c'
				WHERE `name`='flederwiesel'
SQL
			;;

		"1 10:00")
			# From bulk INSERT in "fra-flugplan.sql" we do not get `previous`
			# even for `num` > 1, where normally this would be NOT NULL.
			# Need to check for this also...
			# '0000-00-00 22:30:00' -> NULL
			query <<-"SQL"
				USE `fra-flugplan`;

				UPDATE `visits`
				SET `previous` = NULL
				WHERE `aircraft` = (
					SELECT `id`
					FROM `aircrafts`
					WHERE `reg` = 'CS-TNP'
				)
SQL
			;;

		"1 12:00")
			query <<-"SQL"
				USE fra-flugplan;

				UPDATE `users`
				SET `notification-timefmt`='%A, %d. %B %Y %H:%M',
					`language`='de'
				WHERE `name`='flederwiesel'
SQL
			;;
		esac

		fileext=txt check "$dHHMM-getflights" browse "$url/getflights.php?prefix=$prefix\&time=$now\&debug=url,json,jflights,sql\&fmt=txt"\
			"| sed -r '
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
			s#/\*\[Q[0-9]+\]\*/ *##g
			/: Inserted airport/d
			s/.*\([0-9]+\): *(Inserted )/\1/g
			'"

		flights=$(query --execute='USE `fra-flugplan`;
			SELECT
			 `flights`.`direction`,
			 `flights`.`scheduled`,
			 `flights`.`expected`,
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
			LEFT JOIN `models` ON `models`.`id` = `flights`.`model`'
		)

		fileext=txt check "$dHHMM-flights" "echo '$flights'"\
			"| sed -r '
				s/arrival/A/g
				s/departure/D/g
				s/$YYYY_mm_dd_0/0000-00-00/g
				s/$YYYY_mm_dd_1/0000-00-01/g
				s/$YYYY_mm_dd_2/0000-00-02/g
				s/$YYYY_mm_dd_3/0000-00-03/g
			'"

		check "$dHHMM-arrival" browse "$url/?arrival\&time=$now"\
			"| sed -r '
				s/$YYYY_mm_dd_0/0000-00-00/g
				s/$YYYY_mm_dd_1/0000-00-01/g
				s/(T[0-9]{2}%3A[0-9]{2}%3A00%2B0)[12](00)/\10\2/g
			'"

		# Note, that RARE A/C will not be marked as such,
		# since we use different a/c in arrival/departure.csv,
		# and only arrivals will evaluate visits
		check "$dHHMM-departure" browse "$url/?departure\&time=$now"\
			"| sed -r '
				s/$YYYY_mm_dd_0/0000-00-00/g
				s/$YYYY_mm_dd_1/0000-00-01/g
				s/(T[0-9]{2}%3A[0-9]{2}%3A00%2B0)[12](00)/\10\2/g
			'"

		notifications=$(query --execute='USE `fra-flugplan`;
			SELECT `flight`, `watch`, `notified`
			FROM `watchlist-notifications`
			ORDER BY `flight`'
		)

		fileext=txt check "$dHHMM-notifications" 'echo "$notifications"'\
			"| sed -r '
				s/$YYYY_mm_dd_0/0000-00-00/g
				s/$YYYY_mm_dd_1/0000-00-01/g
			'"

		visits=$(query --execute='USE fra-flugplan;
			SELECT
			 `aircrafts`.`reg`,
			 `visits`.`num`,
			 `visits`.`current`,
			 `visits`.`previous`
			FROM `visits`
			LEFT JOIN `aircrafts` ON `aircrafts`.`id` = `visits`.`aircraft`
			ORDER BY `reg`
		'
		)

		fileext=txt check "$dHHMM-visits" "echo '$visits'"\
			"| sed -r '
				s/$YYYY_mm_dd_0/0000-00-00/g
				s/$YYYY_mm_dd_1/0000-00-01/g
				s/$YYYY_mm_dd_2/0000-00-02/g
			'"
	done
done

for day in {2..3}
do
	dHHMM=$(printf '%02u' $day)-0500

	offset=$(date +'%Y-%m-%d' --date="+$day days")
	now=$(date +'%Y-%m-%d %H:%M:%S' --date="$offset 05:00")
	now=$(rawurlencode $now)

	fileext=txt check "$dHHMM-getflights" browse "$url/getflights.php?prefix=$prefix\&time=$now\&debug=url,json,sql\&fmt=txt"\
		"| sed -r '
		s/Dauer: [0-9]+.[0-9]+s/Dauer: 0.000s/g
		s/$YYYY_mm_dd_2/0000-00-02/g
		s/$YYYY_mm_dd_3/0000-00-03/g
		s/(T[0-9]{2}:[0-9]{2}:00\+0)[12](00)/\10\2/g
		s/(T[0-9]{2}%3A[0-9]{2}%3A00%2B0)[12](00)/\10\2/g
		s#(FROM_UNIXTIME\()[0-9]+#\10#g
		s#(https://[^/]+/).*/(www.frankfurt-airport.com/.*)#\1.../\2#g
		s#/\*\[Q[0-9]+\]\*/ *##g
		'"

	notifications=$(query --execute='USE `fra-flugplan`;
		SELECT `flight`, `watch`, `notified`
		FROM `watchlist-notifications`
		ORDER BY `flight`'
	)

	fileext=txt check "$dHHMM-notifications" 'echo "$notifications"'\
		"| sed -r '
			s/$YYYY_mm_dd_0/0000-00-00/g
			s/$YYYY_mm_dd_1/0000-00-01/g
		'"
done

visits=$(query --execute='USE fra-flugplan;
	SELECT
	 `aircrafts`.`reg`,
	 `visits`.`num`,
	 `visits`.`current`,
	 `visits`.`previous`
	FROM `visits`
	LEFT JOIN `aircrafts` ON `aircrafts`.`id` = `visits`.`aircraft`
	ORDER BY `reg`
'
)

fileext=txt check "visits" "echo '$visits'"\
	"| sed -r '
		s/$YYYY_mm_dd_0/0000-00-00/g
		s/$YYYY_mm_dd_1/0000-00-01/g
		s/$YYYY_mm_dd_2/0000-00-02/g
	'"
