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

# Automatic test script master file

###############################################################################

# parse command line options
debug=0
verbose=0

declare -A argv=()

while [ $# -gt 0 ]
do
	case "$1" in
		'--help')
			echo -e "\033[1;37m${0}\033[m\n"
			exit 1
			;;
		'-d') ;&
		'--debug')
			debug=1
			;;
		'-v') ;&
		'--verbose')
			verbose=1
			;;
		'--'*'='*)
			eval "${1##--}"
			;;
		*)
			argv[${#argv[@]}]="$1"
			;;
	esac
	shift
done

set -- "${argv[@]}"

###############################################################################

alias "mysql=mysql --silent --host=localhost --user=root --password= --default-character-set=utf8"
alias "curl=curl -s --noproxy localhost --cookie .COOKIES --cookie-jar .COOKIES"

unless() {

	line=$1; shift

	[ 1 == $debug ] && echo "$@"
	eval "$@"

	if [ $? -ne 0 ]; then
		echo -e "\033[1;31mTest seriously failed in line $line:"
		echo -e "$@\033[m\n" >&2
		echo -e "\033[1;31mCannot continue.\033[m" >&2
		exit 1
	fi
}

check() {

	name=$1
	shift
	[ 1 == $verbose ] && echo -e "$name" >&2
	[ 1 == $debug ] && echo -e "\033[33m$@\033[m" >&2
	eval "$@" 2>&1 > "$results/$name.htm" | sed -r $'s~^.+$~\033[1;31mERROR: &\033[m~g'
}

initdb() {

	unless $LINENO mysql < ../sql/fra-schedule.sql > /dev/null
}

query() {

	[ 1 == $debug ] && echo -e "\033[1;33m$@\033[m" >&2
	mysql --skip-column-names --execute="$@"
}

strftime() {
	awk "BEGIN { print strftime(\"$1\", $2); }"
}

rawurlencode() {
	local string="${1}"
	local strlen=${#string}
	local encoded=""

	for (( pos=0 ; pos<strlen ; pos++ ))
	do
		c=${string:$pos:1}
		case "$c" in
			[-_.~a-zA-Z0-9] )
				o="${c}"
				;;
			* )
				printf -v o '%%%02x' "'$c"
		esac

		encoded+="${o}"
	done
	echo "${encoded}"    # You can either set a return variable (FASTER)
	REPLY="${encoded}"   #+or echo the result (EASIER)... or both... :p
}

###############################################################################
# <preparation>
###############################################################################

prj="$(readlink -f ..)"
url=http://localhost/$(rawurlencode "${prj##*/}")

unless $LINENO sed -r "\"s/^(define[ \t]*\('DB_NAME',[ \t]*')[^']+('\);)/\1fra-schedule\2/g\"" \
	../.config.local '>' ../.config

mkdir -p sh/results

# Test in release mode
sed "s/^[[:space:]]*define('DEBUG'.*$/\/\/&/" --in-place ../.config

# No mainteance...
[ -e ../adminmessage.php ] && mv ../adminmessage.php ../~adminmessage.php

# On local system, check whether mta is running
mercury=0

if [ 'kowalski' == $(uname --nodename) ]; then
	tasklist | grep -q 'mercury.exe'
	if [ 1 = $? ]; then
		path=$(reg query "HKEY_CURRENT_USER\Software\Mercury32\Command" |
				grep '(Default)' |
				sed 's/^[ \t]*(Default)[ \t]*REG_SZ[ \t]*//g
					s/\\/\\\\/g
					s/[ \t]*\/m//g')
		path=$(cygpath -u "$path")
		eval "$path /m &"
		mercury=$!
	fi
fi

###############################################################################

cat > ../data/betriebsrichtung.html <<EOF
<li><div><div class="titel" style=" padding-top:6px;margin-bottom:0px;padding-bottom:3px;">+++ Betriebsrichtung +++ </div><div style="font-size:12px;"><b> 99 </b></div><div style="font-size:12px;padding-right:125px;"> seit 00.00.0000, 00:00:00</div></div>   </li>
<li><div><div class="titel" style=" padding-top:6px;margin-bottom:0px;padding-bottom:3px;">+++ Startbahn +++</div> <div style="font-size:12px;"><b>18 West</b></div><div style="font-size:12px;padding-right:125px;"> in Betrieb</div></div></li>
EOF

###############################################################################
# </preparation>
###############################################################################

IFS=$'\n'

if [ $# -gt 0 ]; then
	scripts="$@"
else
	scripts='
# failures
register(failure)
activate(failure)
login(failure)
changepasswd(failure)
#addflight(failure)
# success
addflight(perms=1)
addflight(perms=1), lang=de
addflight(perms=0)
addflight(perms=0), lang=de
changepasswd
changepasswd lang=de
profile
watchlist
airportcity
getflights
'
fi

echo "$scripts" |
while read script
do
	if [ -n "$script" ]; then
		echo "$script" | grep -vq '^[[:space:]]*#'

		if [ 0 == $? ]; then
			echo -e "\033[36m$script\033[m"

			expect="sh/expect/$script"
			results="sh/results/$script"
			rm -rf "$results"
			mkdir -p "$results"

			if [ 0 == $? ]; then
				eval "$(cat 'sh/'$script'.sh')"
				# Copy referenced scripts to properly view results
				cp -a ../css ../img ../script "$results"

			diff "$expect" "$results" \
					--brief \
					--recursive --ignore-file-name-case \
					--exclude=css \
					--exclude=img \
					--exclude=script \
					--suppress-blank-empty \
					--ignore-tab-expansion \
					--ignore-space-change \
					--ignore-all-space \
					--ignore-blank-lines | \
				grep -v '^diff' | \
				sed -r $'s~^-(.*)$~\033[32m< \\1\033[m~g;
						 s~^\+(.*)$~\033[35m> \\1\033[m~g;
						 s~^@@.*@@$~\033[1;33m&\033[m~g'
			fi
		fi
	fi
done

###############################################################################
###############################################################################

rm -f .COOKIES

# Restore admin message
[ -e ../~adminmessage.php ] && mv ../~adminmessage.php ../adminmessage.php
