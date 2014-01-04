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
		--data-urlencode "lang=de"

token=$(query "USE fra-schedule;
	SELECT token FROM users WHERE name='flederwiesel'" | sed s/'[ \r\n]'//g)

check "4" curl "$url/?req=activate" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "token=$token"

check "5" curl "$url/?req=login" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "passwd=elvizzz"

check "6" curl "$url/?req=profile"

check "7" curl "$url/?req=profile" \
	--data-urlencode "tm-=-75" \
	--data-urlencode "tm%2b=7200" \
	--data-urlencode "tt-=-75" \
	--data-urlencode "tt%2b=28800" \
	--data-urlencode "submit=interval"
