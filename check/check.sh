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

#url="http://www.flederwiesel.com/vault/fra-schedule/$(svn info . | awk '/^Revision:/ { print $2; }')"
url="http://localhost/fra-schedule"
url=$(echo $url | sed -r 's/\$Rev: ([0-9]+) \$/\1/g')

alias "mysql=mysql -s"
alias "curl=curl -s --cookie .COOKIES --cookie-jar .COOKIES"

unless() {

	line=$1; shift

	eval "$@"

	if [ $? -ne 0 ]; then
		echo -e "\033[1;31mTest seriously failed in line $line.\033[m" >&2
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

	unless $LINENO mysql --host=localhost --user=root --password= \
		--default-character-set=utf8 < ../sql/fra-schedule.sql > /dev/null
}

query() {

	mysql --host=localhost --user=root --password= --skip-column-names \
		--execute="$@"
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

if [ -f ../data/betriebsrichtung.html ]; then
	sed -r 's/(07|25|99) \((Ost|West)-Betrieb\)/99 /g' \
		--in-place ../data/betriebsrichtung.html
else
	cat > ../data/betriebsrichtung.html <<EOF
	<li><div><div class="titel" style=" padding-top:6px;margin-bottom:0px;padding-bottom:3px;">+++ Betriebsrichtung +++ </div><div style="font-size:12px;"><b> 99 </b></div><div style="font-size:12px;padding-right:125px;"> seit 00.00.0000, 00:00:00</div></div>   </li>
	<li><div><div class="titel" style=" padding-top:6px;margin-bottom:0px;padding-bottom:3px;">+++ Startbahn +++</div> <div style="font-size:12px;"><b>18 West</b></div><div style="font-size:12px;padding-right:125px;"> in Betrieb</div></div></li>
EOF
fi

###############################################################################
# </preparation>
###############################################################################

IFS=$'\n'

scripts='
register(perms=addflight)+activate+login+addflight
register(perms=addflight)+activate+login+addflight, lang=de
register(perms=)+activate+addflight+login+addflight
register(perms=)+activate+addflight+login+addflight, lang=de
# failures
register(failure)
register(perms=)+activate(failure)
register(perms=)+activate+login(failure)
addflight(failure)
'

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
