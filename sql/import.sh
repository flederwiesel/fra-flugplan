#!/bin/bash

getpass() {
	sed -rn '/^machine[ \t]+mysql:\/\/localhost\/fra-flugplan\/?$/ {
		:next n; s/^[ \t]*password[ \t]*//g; Tnext; p }
	' ~/.netrc
}

sqlexec() {
	[[ "$(uname -o)" =~ "Cygwin" ]] && proto="--protocol=TCP"
	mysql --host=localhost $proto --user=flugplan --password="$(getpass)" --default-character-set=utf8 "$@"
}

if [ $# -lt 1 ]; then
	echo "Usage: $(basename $0) <file>"
else
	case "${1##*.}" in
	'bz2')
		cmd="bunzip2 -c '$1'"
		;;
	'gz')
		cmd="gunzip -c '$1'"
		;;
	'sql')
		cmd="cat '$1'"
		;;
	'zip')
		cmd="unzip -cp '$1'"
		;;
	*)
		echo "Don't know what to do for ${1##*.}." >&2
		exit 1
		;;
	esac

	sqlexec <<< "$(eval $cmd | sed 's/flederwi_/flederwiesel_/g')"
fi
