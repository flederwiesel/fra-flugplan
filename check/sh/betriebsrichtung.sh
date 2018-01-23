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

findroot() {
	name=$(dirname "$1")

	while [ -n "$name" ]
	do
		name=$(readlink -f "$name")

		if [ "/" == "$name"  ]; then
			break
		else
			if [ -f "$name/.htaccess" ]; then
					root="$name"
			fi
		fi

		name="$name/.."
	done

	echo "$root"
}

datadir=$(findroot "${BASH_SOURCE[0]}")

check "1" browse "$url/www.frankfurt-airport.com/betriebsrichtung/betriebsrichtungsvg.js" \
	"| tee $datadir/var/run/fra-schedule/betriebsrichtung.ini"