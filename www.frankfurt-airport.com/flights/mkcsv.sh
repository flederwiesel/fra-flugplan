#!/bin/sh

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
