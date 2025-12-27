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

# check url notification ######################################################
## Tests correctness of htaccess as drive by...

check "1" browse "http://fra-flugplan.net/fra-flugplan/?lang=en"
check "2" browse "http://fra-flugplan.net/fra-flugplan/?lang=de"

check "3" browse "http://fra-flugplan.net/fra-schedule/?lang=en"
check "4" browse "http://fra-flugplan.net/fra-schedule/?lang=de"
