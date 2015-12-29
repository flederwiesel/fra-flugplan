#!/bin/sh

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

# Script for combining all csv files into {arrival,departure}.csv

this="${BASH_SOURCE[0]}"
scriptdir=$(dirname "$this")

export LC_ALL=C
export LANG=de_DE.utf8

for dir in arrival departure
do
	cat "$scriptdir/$dir/"*.csv
done |
sort |
awk -f "$scriptdir/mkcsv.awk" |
iconv -c -f utf-8 -t windows-1252 > "$scriptdir/flights.csv"
