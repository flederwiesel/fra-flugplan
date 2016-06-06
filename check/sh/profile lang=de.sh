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

echo "$mails" > /etc/mailtodisk/hausmeister@flederwiesel.com
echo "$mails" > /etc/mailtodisk/fra-schedule@flederwiesel.com

###############################################################################

check "1" browse "$url/?lang=de"
check "2" browse "$url/?req=register\&stopforumspam=$prefix" \
		" | sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"

check "3" browse "$url/?req=register" \
		--data-urlencode "email=hausmeister@flederwiesel.com" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1"

token=$(query --execute="USE fra-schedule;
	SELECT token FROM users WHERE name='flederwiesel'" | sed s/'[ \r\n]'//g)

check "4" browse "$url/?req=activate" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "token=$token"

check "5" browse "$url/?req=login" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "passwd=elvizzz"

check "6" browse "$url/?req=profile"

check "7" browse "$url/?req=profile\&dispinterval" \
	--data-urlencode "tm-=-75" \
	--data-urlencode "tm%2b=7200" \
	--data-urlencode "tt-=-75" \
	--data-urlencode "tt%2b=28800" \
	--data-urlencode "submit=interval"

check "8" browse "$url/?req=profile\&notifinterval"

check "9" browse "$url/?req=profile" \
	--data-urlencode "from=00:00" \
	--data-urlencode "until=24:00" \
	--data-urlencode "timefmt=%c" \
	--data-urlencode "submit=notifications" \
	"|" sed -r "'s/[0-9]{2}\.[0-9]{2}\.[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}/00.00.0000 00:00:00/g'"

check "10" browse "$url/?req=profile" \
	--data-urlencode "from=08:00" \
	--data-urlencode "until=22:00" \
	--data-urlencode "timefmt=" \
	--data-urlencode "submit=notifications" \
	"|" sed -r "'s/\+0 [0-9]{2}:[0-9]{2}/+0 00:00/g'"

check "11" browse "$url/?req=profile" \
	--data-urlencode "from=08:00" \
	--data-urlencode "until=22:00" \
	--data-urlencode "submit=notifications" \
	"|" sed -r "'s/\+0 [0-9]{2}:[0-9]{2}/+0 00:00/g'"
