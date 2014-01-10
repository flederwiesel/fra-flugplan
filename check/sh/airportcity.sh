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

			check $(printf '%u-%02u00-arrival-%u' $day $time $page) "echo '$htm'"
			page=$next
		done

		YYYYmmdd=$(date +%Y%m%d --date="+$day days")
		check $(printf '%u-%02u00-fia' $day $time) curl "$url/check/flugplan/airportcity/?fia=SA260$YYYYmmdd"
		check $(printf '%u-%02u00-fid' $day $time) curl "$url/check/flugplan/airportcity/?fid=SA261$YYYYmmdd"

	done
done
