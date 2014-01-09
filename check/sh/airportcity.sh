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

echo '0 05:00' > flugplan/airportcity/querytime

check "arrival-1" curl "$url/check/flugplan/airportcity/?type=arrival\&items=3\&page=1"
check "arrival-2" curl "$url/check/flugplan/airportcity/?type=arrival\&items=3\&page=2"
check "arrival-3" curl "$url/check/flugplan/airportcity/?type=arrival\&items=3\&page=3"
check "arrival-4" curl "$url/check/flugplan/airportcity/?type=arrival\&items=3\&page=4"
check "arrival-5" curl "$url/check/flugplan/airportcity/?type=arrival\&items=3\&page=5"

check "departure-1" curl "$url/check/flugplan/airportcity/?type=departure\&items=3\&page=1"
check "departure-2" curl "$url/check/flugplan/airportcity/?type=departure\&items=3\&page=2"
check "departure-3" curl "$url/check/flugplan/airportcity/?type=departure\&items=3\&page=3"
check "departure-4" curl "$url/check/flugplan/airportcity/?type=departure\&items=3\&page=4"
check "departure-5" curl "$url/check/flugplan/airportcity/?type=departure\&items=3\&page=5"

check "fia-00-0500" curl "$url/check/flugplan/airportcity/?fia=SA260$(date +%Y%m%d)"
check "fid-00-0500" curl "$url/check/flugplan/airportcity/?fid=SA261$(date +%Y%m%d)"
