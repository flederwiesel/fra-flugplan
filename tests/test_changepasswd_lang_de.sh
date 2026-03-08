#!/bin/bash

# drop/re-create database
initdb

mailtodisk --add uid-1@example.com "$mailfile"

###############################################################################

check "0" browse -X POST "$url/?req=register"

csrftoken=$(
	browse "$url/?req=register" |
	sed -nr '/name="CSRFToken"/ { s/.*value="([^"]+)".*/\1/g; p }'
)

check "1" browse "$url/?lang=de"
check "2" browse "$url/?req=register"

check "3" browse "$url/?req=register\&stopforumspam=${FRA_FLUGPLAN_HOST}" \
		--data-urlencode "email=uid-1@example.com" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=de" "|" \
		"sed -r 's:(stopforumspam=)[^\&\"]+:\1...:g'"

token=$(query --execute="USE fra-flugplan;
	SELECT token FROM users WHERE name='uid-1'" | sed s/'[ \r\n]'//g)

check "4" browse "$url/?req=activate" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token"

###############################################################################
# registered and activated, not logged in
###############################################################################

check "5" browse "$url/?req=reqtok"

check "6" browse "$url/?req=reqtok" \
		--data-urlencode "user=uid-1"

token=$(query --execute="USE fra-flugplan;
	SELECT token FROM users WHERE name='uid-1'" | sed s/'[ \r\n]'//g)

check "7" browse "$url/?req=changepw" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"

###############################################################################
# try login with new passwd
###############################################################################

check "8" browse "$url/?req=login" \
		--data-urlencode "user=uid-1" \
		--data-urlencode "passwd=zwiebel"

###############################################################################
# change password whilst logged in
###############################################################################

check "9" browse "$url/?req=profile\&changepw"

check "10" browse "$url/?req=changepw" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "submit=changepw"
