#!/bin/bash

set -euo pipefail

readonly SCRIPTDIR=$(dirname "${BASH_SOURCE[0]}")

if [ $# -lt 1 ]; then
	echo "Usage: $(basename $0) <file>"
else
	salt=$(head -c 32 /dev/urandom | openssl dgst -sha256 -r | grep -io '^[^ ]*')
	passwd=$(echo -n elvizzz | openssl dgst -sha256 -hmac "$salt" -r | grep -io '^[^ ]*')

	case "${1##*.}" in
	--download)
		filename="fra-flugplan-$(date +%Y-%m-%d_%H).sql.xz"
		archive="fra-flugplan/0-hourly/$filename"
		scp "fra-flugplan.de:${FRA_FLUGPLAN_BACKUPDIR?}/$archive" "$SCRIPTDIR"
		xzcat "$SCRIPTDIR/$filename"
		;;
	'sql')
		cat "$1"
		;;
	'xz')
		xzcat "$1"
		;;
	*)
		echo "Don't know what to do for ${1##*.}." >&2
		exit 1
		;;
	esac |
	sed '1 s/!999999\\-//g' |
	mysql --default-character-set=utf8
	mysql fra-flugplan <<< \
		'UPDATE `users` SET `salt` = '"'$salt'"', `passwd` = '"'$passwd'"''`
		`' WHERE `name` = "flederwiesel"'
	mysql fra-flugplan <<< \
		'UPDATE `users` SET `name` = `salt`, `email` = `passwd`'`
		`' WHERE `name` != "flederwiesel"'
fi
