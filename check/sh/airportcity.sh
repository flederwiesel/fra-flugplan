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

###############################################################################

items=4

for day in {0..1}
do

	for time in {5..23}
	do
		YYYYmmdd_0=$(date +%Y-%m-%d)
		YYYYmmdd_1=$(date +%Y-%m-%d --date="+1 day")
		YYYYmmdd_2=$(date +%Y-%m-%d --date="+2 days")
		YYYYmmddTHHMMSSZ=$(date +'%Y-%m-%dT%H:%M:%S%z' --date="$day day $time:00")

		for dir in arrival departure
		do
			# get number of pages
			airport="www.frankfurt-airport.com/flights_copy.${dir}s.json/filter"
			request="type=$dir&time=$(rawurlencode $YYYYmmddTHHMMSSZ)&items=$items&page=1"
			pages=$(browse "$url/$airport?$request" | jq .maxpage)

			if [ $? -eq 0 ]; then
				if [ -n "$pages" ]; then
					page=1

					while [ $page -le $pages ]
					do
						request="type=$dir&time=$(rawurlencode $YYYYmmddTHHMMSSZ)&items=$items&page=$page"
						json=$(browse "$url/$airport?$request" | jq '.data[]|{dir:.dir,sched:.sched,esti:.esti,fnr:.fnr,reg:.reg}')
						check $(printf "$day-%02u00-$dir-$page" $time) "echo '$json'" \
							"| sed -r '
								s/$YYYYmmdd_0/0000-00-00/g
								s/$YYYYmmdd_1/0000-00-01/g
								s/$YYYYmmdd_2/0000-00-02/g
							'$filter"

						 ((page++))
					done
				fi
			fi
		done
	done
done
