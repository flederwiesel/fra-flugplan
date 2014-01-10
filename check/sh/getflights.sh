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

baseurl=$(rawurlencode localhost/fra-schedule/check)

###############################################################################

for day in {0..1}
do

	for t in {5..23}
	do

		time=$(printf '%02u:00' $t)

		YYYYmmdd_0=$(date +'%Y%m%d' --date="$time")
		YYYYmmdd_1=$(date +'%Y%m%d' --date="$time +1 day")
		dHHMM=$(printf   '%02u' $day)-$(date +'%H%M' --date="$time")

		now=$(date +'%Y-%m-%d %H:%M:%S' --date="$time")
		now=$(rawurlencode $now)

		echo "$day $time" > flugplan/airportcity/querytime

		check "$dHHMM-getflights" curl "$url/getflights.php?baseurl=$baseurl\&now=$now\&debug=url,query\&fmt=html"\
			"| sed -r '"\
			"s/Dauer: [0-9]+.[0-9]+s/Dauer: 0.000s/g;"\
			"s/(fi[ad]=[A-Z0-9]+)$YYYYmmdd_0$/\100000000/g;"\
			"s/(fi[ad]=[A-Z0-9]+)$YYYYmmdd_1$/\100000000/g;"\
			"s/[0-9]{4}-[0-9]{2}-[0-9]{2} ([0-9]{2}:[0-9]{2}(:[0-9]{2})?)/0000-00-00 \1/g"\
			"'"

		sql='USE fra-schedule;

				SELECT
				 `flights`.`direction`,
				 `flights`.`scheduled`,
				 `flights`.`expected`,
				 `airports`.`iata` AS `airport:iata`,
				 `airports`.`icao` AS `airport:icao`,
				 `models`.`icao` AS `model`,
				 `aircrafts`.`reg` AS `aircraft`
				FROM `flights`
				LEFT JOIN `airlines` ON `airlines`.`id` = `flights`.`airline`
				LEFT JOIN `airports` ON `airports`.`id` = `flights`.`airport`
				LEFT JOIN `aircrafts` ON `aircrafts`.`id` = `flights`.`aircraft`
				LEFT JOIN `models` ON `models`.`id` = `flights`.`model`'

		check "$dHHMM-flights" "echo '<pre>'; query '$sql'; echo '</pre>'"

		check "$dHHMM-arrival" curl "$url/?arrival\&now=$now"\
			"| sed -r 's/now=[0-9]{4}-[0-9]{2}-[0-9]{2}/now=0000-00-00/g'"

		# Note, that RARE A/C will not be marked as such,
		# since we use different a/c in arrival/departure.csv,
		# and only arrivals will evaluate visits
		check "$dHHMM-departure" curl "$url/?departure\&now=$now"\
			"| sed -r 's/now=[0-9]{4}-[0-9]{2}-[0-9]{2}/now=0000-00-00/g'"

	done

done

sql='USE fra-schedule;

	SELECT
	 `aircrafts`.`reg`,
	 `visits`.`num`,
	 `visits`.`last`
	FROM `visits`
	LEFT JOIN `aircrafts` ON `aircrafts`.`id` = `visits`.`aircraft`

check "visits" "echo '<pre>'; query '$sql'; echo '</pre>'"
