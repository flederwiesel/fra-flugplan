#!/bin/bash

###############################################################################
#
#       project: FRA-flights Live Schedule
#                Auomatic test script
#
#       $Author: flederwiesel $
#         $Date: 2013-07-04 18:16:27 +0200 (Do, 04 Jul 2013) $
#          $Rev: 341 $
#
###############################################################################

# drop/re-create database
initdb && rm -f .COOKIES

baseurl=$(rawurlencode $(echo $url | sed s?http://??g)/check)

# `initdb` only creates 'root'...
mysql <<-"SQL"
	USE fra-schedule;

	INSERT INTO `users`
	(
		`id`, `name`, `email`, `salt`, `passwd`,
		`language`, `permissions`
	)
	VALUES
	(
		2,
		'flederwiesel',
		'hausmeister@flederwiesel.com',
		'cf78aafd5c5410b7b12c2794a52cda1bccd01316f30df57aa29c5609ba979c15',
		'c4ae99aa0209ce5bea9687cf0548d8ebc942ba14e166c45957a876bcec194fed',
		'en',
		'1'
	);

	SELECT `id` INTO @root
	FROM `users`
	WHERE `name`='root';

	SELECT `id` INTO @flederwiesel
	FROM `users`
	WHERE `name`='flederwiesel';

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
	(7, @flederwiesel, FALSE, 'ZS-SNB', 'South African Airways');
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

		time=$(printf '%02u:00' $t)

		dHHMM=$(printf '%02u' $day)-$(date +'%H%M' --date="$time")

		offset=$(date +'%Y-%m-%d' --date="+$day days")
		now=$(date +'%Y-%m-%d %H:%M:%S' --date="$offset $time")
		now=$(rawurlencode $now)

		echo "$day $time" > flugplan/airportcity/querytime

		#[ 1 == $day ] && [ 10 == $t ] && exit 1

		# From bulk INSERT in "fra-schedule.sql" we do not get `previous`
		# even for `num` > 1, where normally this would be NOT NULL.
		# Need to check for this also...
		if [ 1 == $day ] && [ 10 == $t ]; then
			# '0000-00-00 22:30:00' -> NULL
			query "USE fra-schedule; UPDATE visits SET previous=NULL WHERE aircraft=7"
		fi

		check "$dHHMM-getflights" curl "$url/getflights.php?baseurl=$baseurl\&now=$now\&debug=url,query\&fmt=html"\
			"| sed -r '
			s/Dauer: [0-9]+.[0-9]+s/Dauer: 0.000s/g
			s/(fi[ad]=[A-Z0-9]+)$YYYYmmdd_0$/\100000000/g
			s/(fi[ad]=[A-Z0-9]+)$YYYYmmdd_1$/\100000001/g
			s/(fi[ad]=[A-Z0-9]+)$YYYYmmdd_2$/\100000002/g
			s/$YYYY_mm_dd_0 ([0-9]{2}:[0-9]{2}(:[0-9]{2})?)/0000-00-00 \1/g
			s/$YYYY_mm_dd_1 ([0-9]{2}:[0-9]{2}(:[0-9]{2})?)/0000-00-01 \1/g
			s/$YYYY_mm_dd_2 ([0-9]{2}:[0-9]{2}(:[0-9]{2})?)/0000-00-02 \1/g
			s#(http://[^/]+/).*/check/(.*)#\1.../\2#g
			'"

		flights=$(query 'USE `fra-schedule`;
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

		check "$dHHMM-flights" "echo '$flights'"\
			"| sed -r '
				1 i <html>
				1 i <head>
				1 i <title>$dHHMM-flights</title>
				1 i </head>
				1 i <body>
				1 i <pre>
				s/arrival/A/g
				s/departure/D/g
				s/$YYYY_mm_dd_0/0000-00-00/g
				s/$YYYY_mm_dd_1/0000-00-01/g
				s/$YYYY_mm_dd_2/0000-00-02/g
				$ a </pre>
				$ a </body>
				$ a </html>
			'"

		check "$dHHMM-arrival" curl "$url/?arrival\&now=$now"\
			"| sed -r '
				s/now=$YYYY_mm_dd_0/now=0000-00-00/g
				s/now=$YYYY_mm_dd_1/now=0000-00-01/g
			'"

		# Note, that RARE A/C will not be marked as such,
		# since we use different a/c in arrival/departure.csv,
		# and only arrivals will evaluate visits
		check "$dHHMM-departure" curl "$url/?departure\&now=$now"\
			"| sed -r '
				s/now=$YYYY_mm_dd_0/now=0000-00-00/g
				s/now=$YYYY_mm_dd_1/now=0000-00-01/g
			'"

		notifications=$(query 'USE `fra-schedule`;
			SELECT `flight`, `watch`, `notified`
			FROM `watchlist-notifications`'
		)

		check "$dHHMM-notifications" 'echo "$notifications"'\
			"| sed -r '
				1 i <html>
				1 i <head>
				1 i <title>$dHHMM-notifications</title>
				1 i </head>
				1 i <body>
				1 i <pre>
				s/$YYYY_mm_dd_0/0000-00-00/g
				s/$YYYY_mm_dd_1/0000-00-01/g
				$ a </pre>
				$ a </body>
				$ a </html>
			'"

	done

done

for day in {2..3}
do
	dHHMM=$(printf '%02u' $day)-0500

	offset=$(date +'%Y-%m-%d' --date="+$day days")
	now=$(date +'%Y-%m-%d %H:%M:%S' --date="$offset 05:00")
	now=$(rawurlencode $now)

	echo "$day 05:00" > flugplan/airportcity/querytime

	check "$dHHMM-getflights" curl "$url/getflights.php?baseurl=$baseurl\&now=$now\&debug=url,query\&fmt=html"\
		"| sed -r '
		s/Dauer: [0-9]+.[0-9]+s/Dauer: 0.000s/g
		s/(fi[ad]=[A-Z0-9]+)$YYYYmmdd_0$/\100000000/g
		s/(fi[ad]=[A-Z0-9]+)$YYYYmmdd_1$/\100000001/g
		s/(fi[ad]=[A-Z0-9]+)$YYYYmmdd_2$/\100000002/g
		s/$YYYY_mm_dd_2 ([0-9]{2}:[0-9]{2}(:[0-9]{2})?)/0000-00-02 \1/g
		s/$YYYY_mm_dd_3 ([0-9]{2}:[0-9]{2}(:[0-9]{2})?)/0000-00-03 \1/g
		s#(http://[^/]+/).*/check/(.*)#\1.../\2#g
		'"

	notifications=$(query 'USE `fra-schedule`;
		SELECT `flight`, `watch`, `notified`
		FROM `watchlist-notifications`'
	)

	check "$dHHMM-notifications" 'echo "$notifications"'\
		"| sed -r '
			1 i <html>
			1 i <head>
			1 i <title>$dHHMM-notifications</title>
			1 i </head>
			1 i <body>
			1 i <pre>
			s/$YYYY_mm_dd_0/0000-00-00/g
			s/$YYYY_mm_dd_1/0000-00-01/g
			$ a </pre>
			$ a </body>
			$ a </html>
		'"

done

visits=$(query 'USE fra-schedule;

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

check "visits" "echo '$visits'"\
	"| sed -r '
		1 i <html>
		1 i <head>
		1 i <title>visits</title>
		1 i </head>
		1 i <body>
		1 i <pre>
		s/$YYYY_mm_dd_0/0000-00-00/g
		s/$YYYY_mm_dd_1/0000-00-01/g
		s/$YYYY_mm_dd_2/0000-00-02/g
		$ a </pre>
		$ a </body>
		$ a </html>
	'"
