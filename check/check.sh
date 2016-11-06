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

# Automatic test script master file

###############################################################################

# parse command line options
export debug=0
export verbose=0

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

minversion() {
	# <comment> <expect> <result>
	awk 'BEGIN {
		if (ARGC < 3)
			exit 1

		len["expect"] = split(ARGV[1], expect, ".")
		len["result"] = split(ARGV[2], result, ".")
		len["min"] = len["expect"] < len["result"] ? len["expect"] : len["result"]

		for (i = 1; i <= len["min"]; i++) {
			if (expect[i] < result[i]) {
				exit 0
			}
			if (expect[i] > result[i]) {
				exit 1
			}
		}

		# more elements means suffix -> greater
		if (len["expect"] > len["result"])
				exit 1
	}
' "$2" "$3"
}

chkdep() {
	"$@" &>/dev/null

	if [ $? -ne 0 ]; then
		echo -e "\033[1;31m$1 $2 failed: $3 <-> $4\033[m"
		exit 1
	fi
}

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
	[ 1 == $debug -o 1 == $verbose ] && echo -e "$name" >&2
	[ 1 == $debug ] && echo -e "\033[33m$@\033[m" >&2
	eval "$@" 2>&1 > "$results/$name.${fileext:-htm}" | sed -r $'s~^.+$~\033[1;31mERROR: &\033[m~g'
}

initdb() {

	unless $LINENO query < ../sql/fra-schedule.sql > /dev/null
}

query() {

	[ 1 == $debug ] && echo -e "\033[1;33m$@\033[m" >&2

	mysql --silent --protocol=TCP --host=localhost --user=root --password= \
		--default-character-set=utf8 --skip-column-names "$@"
}

strftime() {
	awk "BEGIN { print strftime(\"$1\", $2); }"
}

browse() {
	curl -s --noproxy localhost --cookie .COOKIES --cookie-jar .COOKIES "$@"
}

rawurlencode() {
	local string="${1}"
	local strlen=${#string}
	local encoded=""
	local retain="${2}"

	for (( pos=0 ; pos<strlen ; pos++ ))
	do
		c=${string:$pos:1}
		case "$c" in
			[-_.~a-zA-Z0-9"$retain"] )
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

export -f check
export -f unless
export -f initdb
export -f strftime
export -f query
export -f browse
export -f rawurlencode

###############################################################################
# <preparation>
###############################################################################

chkdep readlink --version
chkdep minversion sed        "4.2.1"  "$(sed --version | sed -nr '/^(GNU *)?sed/ { s/^[^0-9]*//g; /^$/d; p }')"
chkdep minversion awk        "4.1.1"  "$(awk --version)" "||" \
       minversion mawk       "1.3.3"  "$(awk -W version 2>&1 | sed -nr '/mawk/ { s/^[^0-9]*([^ ]+).*/\1/g; p }')"
chkdep minversion curl       "7.26.0" "$(curl --version | sed -nr '/^curl/ { s/^[^0-9]*([^ ]+).*/\1/g; p }')"
chkdep minversion php        "5.4"    "$(curl --silent --head http://localhost | sed -n '/PHP\//{s#.*PHP/##g; s# .*$##g; p}')"
chkdep minversion mysql      "14.14"  "$(mysql --version | sed -r 's/^[^0-9]*([^ ]+).*/\1/g')"
chkdep minversion jq         "1.5"    "$(jq --version 2>&1 | sed 's/^[^0-9]*//g')"
chkdep minversion python     "2.7.3"  "$(python --version 2>&1 | sed 's/^[^0-9]*//g')"
chkdep minversion mailtodisk "1028"   "$(mailtodisk --version)"

IFS=$'\n'

export prj="$(readlink -f ..)"
export url=http://localhost/$(rawurlencode "${prj##*htdocs/}" "/")

mkdir -p sh/results

# Test in release mode
sed "s/^[[:space:]]*define('DEBUG'.*$/\/\/&/" --in-place ../.config

# No mainteance...
[ -e ../adminmessage.php ] && mv ../adminmessage.php ../~adminmessage.php

###############################################################################

datadir="${prj%%/htdocs/*}/var/run/fra-schedule"

mkdir -p "$datadir"
cat > "$datadir/betriebsrichtung.html" <<EOF
<li><div><div class="titel" style=" padding-top:6px;margin-bottom:0px;padding-bottom:3px;">+++ Betriebsrichtung +++ </div><div style="font-size:12px;"><b> 99 </b></div><div style="font-size:12px;padding-right:125px;"> seit 00.00.0000, 00:00:00</div></div>   </li>
<li><div><div class="titel" style=" padding-top:6px;margin-bottom:0px;padding-bottom:3px;">+++ Startbahn +++</div> <div style="font-size:12px;"><b>18 West</b></div><div style="font-size:12px;padding-right:125px;"> in Betrieb</div></div></li>
EOF

###############################################################################
# </preparation>
###############################################################################

if [ $# -gt 0 ]; then
	scripts="$@"
else
	scripts='
betriebsrichtung
# failures
register(failure)
activate(failure)
login(failure)
changepasswd(failure)
#addflight(failure)
# success
register(spam)
addflight(perms=1)
addflight(perms=1), lang=de
addflight(perms=0)
addflight(perms=0), lang=de
changepasswd
changepasswd lang=de
profile
profile lang=de
watchlist
airportcity
getflights
'
fi

status=0

echo "$scripts" |
while read script
do
	if [ -n "$script" ]; then
		echo "$script" | grep -vq '^[[:space:]]*#'

		if [ 0 == $? ]; then
			echo -e "\033[36m$script\033[m"

			export expect="sh/expect/$script"
			export results="sh/results/$script"

			rm -rf "$results"
			mkdir -p "$results"

			if [ 0 == $? ]; then

				rm -f /etc/mailtodisk/*

				export mails=$(readlink -m "$results/mail.txt")

				eval "$(cat sh/$script.sh)"

				# wait only to suppress `check.sh: Zeile NNN:
				#   NNNN Terminated /tmp/mailer.py "$results/mail.txt"`

				if [ -e "$results/mail.txt" ]; then
					# See user.php:
					#     http://www.outlookfaq.net/index.php?action=artikel&cat=6&id=84&artlang=de
					iconv -f iso-8859-1 -t utf-8 "$results/mail.txt" |
					sed -r "
						s#::1#<localhost>#g
						s#127.0.0.1#<localhost>#g
						s#(X-Mailer: PHP/).*\$#\1*#g
						s#(http://[^/]+/).*/([^/?]+\?.*)#\1.../\2#g
						s#((Mon|Tues|Wednes|Thurs|Fri|Satur|Sun)day), [0-9]+/[0-9]+/[0-9]+#Day, 00/00/00#g
						s/((Mon|Diens|Donners|Frei|Sams|Sonn)tag|Mittwoch), [0-9]+\. (Januar|Februar|März|April|Mai|Ju[nl]i|August|(Sept|Nov|Dez)ember|Oktober) [0-9]+/Tag, 00. Monat 0000/g
						s/^(Date:[ \t]+).+\$/\1Day, 0 Month 0000 00:00:00 +0000/g
						s/[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/0000-00-00 00:00:00/g
						s/[0-9]{4}-[0-9]{2}-[0-9]{2}T/0000-00-00T/g
						s/token=[0-9a-f]+/token=***/g
						s/token='[0-9a-f.]+'/token='***'/g
						s/\(code [0-9]+\)/(code ***)/g
						/(Activation|Password) token is:/ { N; s/\n.+\$/\n***/g }
						/Das (Aktivierungs-)?Token( dafür)? ist:/ { N; s/\n.+\$/\n***/g }
					" > "$results/mail-utf-8.txt" &&
					mv "$results/mail-utf-8.txt" "$results/mail.txt"
				fi

				# Copy referenced scripts to properly view results
				cp -a ../css ../img ../script "$results"

				diff=$(diff "$expect" "$results" \
					--brief \
					--recursive --ignore-file-name-case \
					--exclude=css \
					--exclude=img \
					--exclude=script \
					--suppress-blank-empty \
					--ignore-tab-expansion \
					--ignore-space-change \
					--ignore-all-space \
					--ignore-blank-lines)

				result=$?

				if [ $result -ne 0 ]; then
					[ $status -eq 0 ] && status=$result
				fi

				if [ -n "$diff" ]; then
					grep -v '^diff' <<<"$diff" | \
					sed -r $'s~^-(.*)$~\033[32m< \\1\033[m~g;
							 s~^\+(.*)$~\033[35m> \\1\033[m~g;
							 s~^@@.*@@$~\033[1;33m&\033[m~g'
				fi
			fi
		fi
	fi
done

###############################################################################
###############################################################################

rm -f .COOKIES

# Restore admin message
[ -e ../~adminmessage.php ] && mv ../~adminmessage.php ../adminmessage.php

exit $status
