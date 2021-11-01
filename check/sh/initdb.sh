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

query < <(
	echo 'USE `fra-flugplan`;'
	cat ../sql/data/countries.sql \
		../sql/data/airlines.sql \
		../sql/data/airports.sql \
		../sql/data/models.sql
)
