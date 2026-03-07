#!/bin/bash

readonly SCRIPTDIR=$(dirname "${BASH_SOURCE[0]}")

if [ $# -lt 1 ]; then
	echo "Usage: $(basename $0) <file>"
else
	case "${1##*.}" in
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
fi
