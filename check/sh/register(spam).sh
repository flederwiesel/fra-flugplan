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

echo "$mails" > /etc/mailtodisk/flederwiesel@fra-flugplan.de # admin
echo "$mails" > /etc/mailtodisk/nospam@flederwiesel.com
echo "$mails" > /etc/mailtodisk/spam@gmail.com

sed='s/(ip=)[0-9]+(,email=)[0-9]+(,username=)[0-9]+/\1*\2*\3*/g'

###############################################################################

check "1" browse "$url/?req=register\&stopforumspam=$prefix" \
		--data-urlencode "email=nospam@flederwiesel.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		" | sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"

check "2" browse "$url/?req=register\&stopforumspam=$prefix" \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		" | sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"

check "3" browse "$url/?req=register\&stopforumspam=$prefix" \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		" | sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"

check "4" browse "$url/?req=register\&stopforumspam=$prefix\&ip=46.118.155.73" \
		--data-urlencode "email=nospam@flederwiesel.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		" | sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"

check "5" browse "$url/?req=register\&stopforumspam=$prefix\&ip=46.118.155.73" \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		" | sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"

check "6" browse "$url/?req=register\&stopforumspam=$prefix\&ip=46.118.155.73" \
		--data-urlencode "email=spam@gmail.com" \
		--data-urlencode "user=spammer" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		" | sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"

check "7" browse "$url/?req=register\&stopforumspam=$prefix\&ip=46.118.155.73" \
		--data-urlencode "email=nospam@flederwiesel.com" \
		--data-urlencode "user=nospam" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" \
		" | sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"
