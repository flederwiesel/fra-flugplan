#!/bin/bash

# drop/re-create database
initdb && rm -f .COOKIES

# check url notification ######################################################
## Tests correctness of htaccess as drive by...

check "1" browse "http://fra-flugplan.net/fra-flugplan/?lang=en"
check "2" browse "http://fra-flugplan.net/fra-flugplan/?lang=de"

check "3" browse "http://fra-flugplan.net/fra-schedule/?lang=en"
check "4" browse "http://fra-flugplan.net/fra-schedule/?lang=de"
