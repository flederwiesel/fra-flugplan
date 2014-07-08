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

for dir in arrival departure
do

	cat $dir/* | sort | awk '
		BEGIN {

			FS = ";";
			last = "";
		}

		/^[0-9]/ {

			querytime = gensub(/([01] [0-9][0-9]:[0-9][0-9])[^;]*/, "\\1", "", $1);

			if (last != querytime) {

				if (last != "")
					print ";";

				last = querytime;
			}

			print $0;
		}
	' | iconv -c -f utf-8 -t windows-1252 > $dir.csv

done
