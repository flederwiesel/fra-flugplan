#!/bin/sh

###############################################################################
#
#       project: FRA-flights Live Schedule
#                Auomatic test script master file
#
#       $Author: flederwiesel $
#         $Date: 2013-07-04 18:16:27 +0200 (Do, 04 Jul 2013) $
#          $Rev: 341 $
#
###############################################################################

[ -e ../.config ] || cp ../.config.local ../.config
#url="http://www.flederwiesel.com/vault/fra-schedule/$(svn info . | awk '/^Revision:/ { print $2; }')"
url="http://localhost/fra-schedule"
url=$(echo $url | sed -r 's/\$Rev: ([0-9]+) \$/\1/g')

alias "mysql=mysql --silent --host=localhost --user=root --password= --default-character-set=utf8"
alias "curl=curl -s --cookie .COOKIES --cookie-jar .COOKIES"
# Mozilla/5.0 (Windows NT 6.1; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0
# Opera/9.80 (Android 2.3.6; Linux; Opera Mobi/ADR-1301071820) Presto/2.11.355 Version/12.10

unless() {

	line=$1; shift

	eval "$@"

	if [ $? -ne 0 ]; then
		echo -e "\033[1;31mTest seriously failed in line $line:"
		echo -e "$1$@\033[m\n" >&2
		echo -e "\033[1;31mCannot continue.\033[m" >&2
		exit 1
	fi
}

check() {

	name=$1
	shift
	eval "$@" 2>&1 > "$results/$name.htm" | sed -r $'s~^.+$~\033[1;31mERROR: &\033[m~g'
}

initdb() {

	unless $LINENO mysql < ../sql/fra-schedule.sql > /dev/null
}

query() {

	mysql --skip-column-names --execute="$@"
}

strftime() {
	awk "BEGIN { print strftime(\"$1\", $2); }"
}

###############################################################################
# <preparation>
###############################################################################

rm -rf results
mkdir -p results

# Test in release mode
sed -r "s/^[[:space:]]*define.*'DEBUG'.*$/\/\/&/" --in-place ../.config

# On local system, check whether mta is running
if [ 'kowalski' == $(uname --nodename) ]; then
	unless $LINENO tasklist "|" grep -q 'mercury.exe'
fi

# This is the subdirectory where check places results
results=

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
profile
'
fi

echo "$scripts" |
while read script
do
	if [ -n "$script" ]; then
		echo "$script" | grep -vq '^[[:space:]]*#'

		if [ 0 == $? ]; then
			echo -e "\033[36m$script\033[m"

			results="results/$script"
			mkdir -p "$results"

			if [ 0 == $? ]; then
				eval "$(cat 'check/'$script'.sh')"
				# Copy referenced scripts to properly view results
				cp -a ../css ../img ../script "$results"
			fi
		fi
	fi
done

###############################################################################
###############################################################################

rm -f .COOKIES

diff expect results \
		--recursive --ignore-file-name-case \
		--unified=1 \
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
