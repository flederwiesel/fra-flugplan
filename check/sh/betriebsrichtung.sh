#!/bin/bash

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

check "1" browse "$url/sslapps.fraport.de/betriebsrichtung/betriebsrichtungsvg.js"
