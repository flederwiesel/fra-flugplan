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

check "1" curl "$url/"
check "2" curl "$url/?req=register"

check "3" curl "$url/?req=register" \
		--data-urlencode "email=hausmeister@flederwiesel.com" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en"

token=$(query "USE fra-schedule;
	SELECT token FROM users WHERE name='flederwiesel'" | sed s/'[ \r\n]'//g)

check "4" curl "$url/?req=activate" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "token=$token"

###############################################################################
# registered and activated, not logged in
###############################################################################

check "5" curl "$url/?req=reqtok"

check "5-1" curl "$url/?req=reqtok" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "email=fra-schedule@flederwiesel.com"

check "5-2" curl "$url/?req=reqtok" \
		--data-urlencode "user=unkown"

check "5-3" curl "$url/?req=reqtok" \
		--data-urlencode "email=unknown@flederwiesel.com"

check "6" curl "$url/?req=reqtok" \
		--data-urlencode "user=flederwiesel"

token=$(query "USE fra-schedule;
	SELECT token FROM users WHERE name='flederwiesel'" | sed s/'[ \r\n]'//g)

check "7" curl "$url/?req=changepw" \
		--data-urlencode "user=erwin" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"

check "8" curl "$url/?req=changepw" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "token=" \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"

check "9" curl "$url/?req=changepw" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"

check "10" curl "$url/?req=changepw" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=zwiebl" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"

query "USE fra-schedule;
	UPDATE users SET token_expires=
	FROM_UNIXTIME(UNIX_TIMESTAMP(UTC_TIMESTAMP()) - 3600)
	WHERE name='flederwiesel'"

check "11" curl "$url/?req=changepw" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "token=$token" \
		--data-urlencode "passwd=zwiebel" \
		--data-urlencode "passwd-confirm=zwiebel" \
		--data-urlencode "submit=changepw"

###############################################################################
# try login with new passwd
###############################################################################

check "12" curl "$url/?req=login" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "passwd=elvizzz"