#!/bin/sh

sqlexec() {
	mysql --host=localhost --protocol=TCP --user=root --password= --default-character-set=utf8 "$@"
}

if [ $# -lt 1 ]; then
	echo "Usage: $(basename $0) <file>"
else
	schema="flederwi_fra-schedule"

	sqlexec <<< "DROP DATABASE IF EXISTS \`$schema\`"
	sqlexec <<< "CREATE DATABASE \`$schema\`"

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

	sqlexec <<< "USE \`$schema\`; $(eval $cmd)"
fi
