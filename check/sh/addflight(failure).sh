#!/bin/sh

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

check "1" curl "$url/?req=register"

check "2" curl "$url/?req=register" \
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

check "3" curl "$url/?req=activate" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "token=$token"

check "4" curl "$url/?req=login" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "passwd=elvizzz"

check "5" curl "$url/?page=addflight" \
	"|" sed -r "'s/[0-9]{2}:[0-9]{2}/00:00/g; s/[0-9]{2}\.[0-9]{2}\.[0-9]{4}/00.00.0000/g'"

check "6" curl "$url/?page=addflight" \
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

cat <<EOF
check "6" curl "$url/?page=addflight" \
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
EOF
