#!/bin/sh

if [ $# -lt 1 ]; then
	echo "Usage: $(basename $0) <file>"
else
	schema="flederwi_fra-schedule"

	alias "mysql=mysql --host=localhost --user=root --password= --default-character-set=utf8"

	eval mysql <<<$(echo "DROP DATABASE IF EXISTS \`$schema\`")
	eval mysql <<<$(echo "CREATE DATABASE \`$schema\`")

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

	eval mysql <<<"USE \`$schema\`; $(eval $cmd)"

fi
