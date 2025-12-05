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

prefix=$(rawurlencode $(sed -r 's|https?://||g' <<<"$url"))

mailtodisk --add hausmeister@flederwiesel.com "$mailfile" # user
mailtodisk --add flederwiesel@fra-flugplan.de "$mailfile" # admin

###############################################################################

check "0" browse -X POST "$url/?req=register"

csrftoken=$(
	browse "$url/?req=register" |
	sed -nr '/name="CSRFToken"/ { s/.*value="([^"]+)".*/\1/g; p }'
)

check "1" browse "$url/"
check "2" browse "$url/?req=register"

check "3" browse "$url/?req=register\&stopforumspam=$prefix" \
		--data-urlencode "email=hausmeister@flederwiesel.com" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en" "|" \
		"sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"

token=$(query --execute="USE fra-flugplan;
	SELECT token FROM users WHERE name='flederwiesel'" | sed s/'[ \r\n]'//g)

check "4" browse "$url/?req=activate" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "token=$token"

###############################################################################
# registered and activated, not logged in
###############################################################################

check "5" browse "$url/?req=reqtok"

check "6" browse "$url/?req=reqtok" \
		--data-urlencode "user=flederwiesel"

token=$(query --execute="USE fra-flugplan;
	SELECT token FROM users WHERE name='flederwiesel'" | sed s/'[ \r\n]'//g)

check "7" browse "$url/?req=changepw" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"

###############################################################################
# try login with new passwd
###############################################################################

check "8" browse "$url/?req=login" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "passwd=zwiebel"

###############################################################################
# change password whilst logged in
###############################################################################

check "9" browse "$url/?req=profile\&changepw"

check "10" browse "$url/?req=changepw" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "submit=changepw"
