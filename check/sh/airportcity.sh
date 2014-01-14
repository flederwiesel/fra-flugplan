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

###############################################################################

for day in {0..1}
do

	for time in {5..23}
	do

		echo $(printf '%u %02u:00' $day $time) > flugplan/airportcity/querytime

		ddmmYYYY_0=$(date +'%d\.%m\.%Y')
		ddmmYYYY_1=$(date +'%d\.%m\.%Y' --date="+1 day")
		ddmmYYYY_2=$(date +'%d\.%m\.%Y' --date="+2 days")
		dd_mm_YYYY_0=$(date +%d-%m-%Y)
		dd_mm_YYYY_1=$(date +%d-%m-%Y --date="+1 day")
		dd_mm_YYYY_2=$(date +%d-%m-%Y --date="+2 days")
		YYYYmmdd_0=$(date +%Y%m%d)
		YYYYmmdd_1=$(date +%Y%m%d --date="+1 day")
		YYYYmmdd_2=$(date +%Y%m%d --date="+2 days")

		page=1

		while [ $page -gt 0 ]
		do
			htm=$(curl "$url/check/flugplan/airportcity/?type=arrival&items=3&page=$page")
			next=$(awk 'BEGIN {
					page = 0;
				}
				/class="next-page"[ \t]+href="#[0-9]+"/ {
					page = gensub(/[^#]*#([0-9]+)[^#]*/, "\\1", "g", $0);
				}
				END {
					print page;
				}
				' <<<"$htm")

			check $(printf '%u-%02u00-arrival-%u' $day $time $page) "echo '$htm'" \
				"| sed -r '
					s#/dd_mm_YYYY_0/#/00-00-0000/#g
					s#/dd_mm_YYYY_1/#/01-00-0000/#g
					s#/dd_mm_YYYY_2/#/02-00-0000/#g
					s/$ddmmYYYY_0/00.00.0000/g
					s/$ddmmYYYY_1/01.00.0000/g
					s/$ddmmYYYY_2/02.00.0000/g
					s/$dd_mm_YYYY_0/00-00-0000/g
					s/$dd_mm_YYYY_1/01-00-0000/g
					s/$dd_mm_YYYY_2/02-00-0000/g
					s/(fi[ad]=[A-Z0-9]+)$YYYYmmdd_0\&/\100000000\&/g
					s/(fi[ad]=[A-Z0-9]+)$YYYYmmdd_1\&/\100000001\&/g
					s/(fi[ad]=[A-Z0-9]+)$YYYYmmdd_2\&/\100000002\&/g
				'"

			page=$next

		done
	done
done

for day in {0..1}
do

	echo "$day 06:00" > flugplan/airportcity/querytime

	YYYYmmdd=$(date +%Y%m%d --date="+$day days")

	check "$day-fia" curl "$url/check/flugplan/airportcity/?fia=SA260$YYYYmmdd" \
		"| sed -r 's/[0-9]{2}\\.[0-9]{2}\\.[0-9]{4}/00.00.0000/g'"

	check "$day-fid" curl "$url/check/flugplan/airportcity/?fid=SA261$YYYYmmdd" \
		"| sed -r 's/[0-9]{2}\\.[0-9]{2}\\.[0-9]{4}/00.00.0000/g'"

done

echo "1 23:00" > flugplan/airportcity/querytime

YYYYmmdd=$(date +%Y%m%d --date="+$day days")

check "$day-fia" curl "$url/check/flugplan/airportcity/?fia=SA260$YYYYmmdd" \
	"| sed -r 's/[0-9]{2}\\.[0-9]{2}\\.[0-9]{4}/00.00.0000/g'"

check "$day-fid" curl "$url/check/flugplan/airportcity/?fid=SA261$YYYYmmdd" \
	"| sed -r 's/[0-9]{2}\\.[0-9]{2}\\.[0-9]{4}/00.00.0000/g'"
