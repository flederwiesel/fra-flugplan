#!/bin/sh

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

check "1" curl "$url/"
check "2" curl "$url/?req=register"

check "3" curl "$url/?req=register" \
		--data-urlencode "email=hausmeister@flederwiesel.com" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en"

# grant addflight permission
query "USE fra-schedule; UPDATE users SET permissions='1' WHERE name='flederwiesel'"

token=$(query "USE fra-schedule;
	SELECT token FROM users WHERE name='flederwiesel'" | sed s/'[ \r\n]'//g)

check "4" curl "$url/?req=activate" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "token=$token"

check "5" curl "$url/?req=login" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "passwd=elvizzz"

check "6" curl "$url/?page=addflight" \
	"|" sed -r "'s/[0-9]{2}:[0-9]{2}/00:00/g; s/[0-9]{2}\.[0-9]{2}\.[0-9]{4}/00.00.0000/g'"

check "7" curl "$url/?page=addflight" \
		--data-urlencode "reg=D-AIRY" \
		--data-urlencode "model=A321" \
		--data-urlencode "flight=QQ9999" \
		--data-urlencode "code=QQ" \
		--data-urlencode "airline=QAirline" \
		--data-urlencode "type=pax-regular" \
		--data-urlencode "direction=arrival" \
		--data-urlencode "airport=2" \
		--data-urlencode "from=19.01.2038" \
		--data-urlencode "time=03:14" \
		--data-urlencode "interval=once" \
	"|" sed -r "'s/[0-9]{2}:[0-9]{2}/00:00/g; s/[0-9]{2}\.[0-9]{2}\.[0-9]{4}/00.00.0000/g'"

# check inserted flight
check "8" curl "$url/?arrival" \
	"|" sed -r "s/'\+[0-9]{1,4} [0-9]{2}:[0-9]{2}'/'+0 00:00'/g"