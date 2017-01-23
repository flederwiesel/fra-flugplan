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

prefix=$(rawurlencode $(sed s?http://??g <<<"$url"))

###############################################################################

query <<-"SQL"
	USE flederwiesel_fra-schedule;

	INSERT INTO `users`
	(
		`id`, `name`, `email`, `salt`, `passwd`,
		`language`, `tt-`, `tt+`, `tm-`, `tm+`
	)
	VALUES
	(
		2, 'flederwiesel', 'hausmeister@flederwiesel.com',
		'cf78aafd5c5410b7b12c2794a52cda1bccd01316f30df57aa29c5609ba979c15',
		'c4ae99aa0209ce5bea9687cf0548d8ebc942ba14e166c45957a876bcec194fed', # elvizzz
		'de', -75, 86400, -75, 86400
	);

	# grant user permissions
	INSERT INTO `membership`(`user`, `group`)
				 VALUES((SELECT `id` FROM `users`  WHERE `name`='flederwiesel'),
						(SELECT `id` FROM `groups` WHERE `name`='addflights'));

	INSERT INTO `airports`(`id`, `iata`, `icao`, `name`)
	VALUES(1, 'ZZZ', 'ZZZ', 'Zzzz');

	INSERT INTO `models`(`icao`) VALUES ('A321');
SQL

check "1" browse "$url/?req=login\&lang=de" \
	--data-urlencode "user=flederwiesel" \
	--data-urlencode "passwd=elvizzz"

check "2" browse "$url/?page=addflight" \
	"|" sed -r "'
		s/[0-9]{2}:[0-9]{2}/00:00/g
		s/[0-9]{2}\.[0-9]{2}\.[0-9]{4}/00.00.0000/g
		/<option/d
		/<\/select/i <option>empty</option>'"

# Insert flight: once -> 2038-01-19 04:14 local (2147483640)
check "3" browse "$url/?page=addflight" \
		--data-urlencode "reg=D-AIRY" \
		--data-urlencode "model=A321" \
		--data-urlencode "flight=QQ9999" \
		--data-urlencode "code=QQ" \
		--data-urlencode "airline=QAirline" \
		--data-urlencode "type=pax-regular" \
		--data-urlencode "direction=arrival" \
		--data-urlencode "airport=1" \
		--data-urlencode "from=19.01.2038" \
		--data-urlencode "time=03:14" \
		--data-urlencode "interval=once" \
	"|" sed -r "'
		s/[0-9]{2}:[0-9]{2}/00:00/g
		s/[0-9]{2}\.[0-9]{2}\.[0-9]{4}/00.00.0000/g
		/<option/d
		/<\/select/i <option>empty</option>'"

# Insert flight: once -> +1 week
check "4" browse "$url/?page=addflight" \
		--data-urlencode "reg=D-AIRY" \
		--data-urlencode "model=A321" \
		--data-urlencode "flight=QQ9999" \
		--data-urlencode "code=QQ" \
		--data-urlencode "airline=QAirline" \
		--data-urlencode "type=pax-regular" \
		--data-urlencode "direction=arrival" \
		--data-urlencode "airport=1" \
		--data-urlencode "from=$(date +'%d.%m.%Y' --date='+1 week')" \
		--data-urlencode "time=14:00" \
		--data-urlencode "interval=once" \
	"|" sed -r "'
		s/[0-9]{2}:[0-9]{2}/00:00/g
		s/[0-9]{2}\.[0-9]{2}\.[0-9]{4}/00.00.0000/g
		/<option/d
		/<\/select/i <option>empty</option>'"

# check inserted flights
# 7 day preview only, so flight from "7" will not appear...

time=$(date +'%Y-%m-%d %H:%M:%S' --date='14:00')
time=$(rawurlencode "$time")

check "5" browse "$url/?arrival\&time=$time | sed -r '
	s/(\+[0-7]) [0-9]{2}:[0-9]{2}/\1 00:00/g;
	s/(1:D-AIRY\+[0-7])[0-2][0-9]{3}/\10000/g;
	s/$(date +%Y-%m-%d)/0000-00-00/g'"

time=$(date +'%Y-%m-%d %H:%M:%S' --date='14:05')
time=$(rawurlencode "$time")

check "6" browse "$url/?arrival\&time=$time | sed -r '
	s/(\+[0-7]) [0-9]{2}:[0-9]{2}/\1 00:00/g;
	s/(1:D-AIRY\+[0-7])[0-2][0-9]{3}/\10000/g;
	s/$(date +%Y-%m-%d)/0000-00-00/g'"
