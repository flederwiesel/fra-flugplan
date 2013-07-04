#!/bin/sh

alias "mysql=mysql -s"
alias "curl=curl -s --cookie .COOKIES --cookie-jar .COOKIES"

#url="http://www.flederwiesel.com/vault/fra-schedule/$(svn info . | awk '/^Revision:/ { print $2; }')"
url="http://localhost/fra-schedule"
url=$(echo $url | sed -r 's/\$Rev: ([0-9]+) \$/\1/g')

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
	eval "$@" 2>&1 > results/$name.htm | sed -r $'s~^.+$~\033[1;31mERROR: &\033[m~g'
}

###############################################################################
# <preparation>
###############################################################################

mkdir -p results
rm -f .COOKIES

# test release version
sed -r "s/^[[:space:]]*define.*'DEBUG'.*$/\/\/&/" --in-place ../.config

# on local system, check whether mta is running
if [ 'kowalski' == $(uname --nodename) ]; then
	unless $LINENO tasklist "|" grep -q 'mercury.exe'
fi

###############################################################################
# drop/re-create database
###############################################################################

unless $LINENO mysql --host=localhost --user=root --password= \
	--default-character-set=utf8 < ../sql/fra-schedule.sql > /dev/null

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

###############################################################################
# default -- no COOKIES set -> default language=en
###############################################################################

check "1" curl "$url/"

###############################################################################

check "2" curl "$url/?req=register"

###############################################################################

check "3" curl "$url/?req=register" \
		--data-urlencode "email=hausmeister@flederwiesel.com" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "passwd=elvizzz" \
		--data-urlencode "passwd-confirm=elvizzz" \
		--data-urlencode "timezone=UTC+1" \
		--data-urlencode "lang=en"

###############################################################################

mysql --host=localhost --user=root --password= --skip-column-names \
	--execute="USE fra-schedule; UPDATE users SET permissions='1' WHERE name='flederwiesel'"

token=$(mysql --host=localhost --user=root --password= --skip-column-names \
	--execute="USE fra-schedule; SELECT token FROM users WHERE name='flederwiesel'" |
	sed s/'[ \r\n]'//g)

###############################################################################

check "4" curl "$url/?req=activate" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "token=$token"

###############################################################################
# click(submit)
###############################################################################

check "5" curl "$url/?req=login" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "passwd=elvizzz"

###############################################################################
# click(addflight)
###############################################################################

check "6" curl "$url/?page=addflight" \
	"|" sed -r "'s/[0-9]{2}:[0-9]{2}/00:00/g; s/[0-9]{2}\.[0-9]{2}\.[0-9]{4}/00.00.0000/g'"

###############################################################################

check "7" curl "$url/?page=addflight" \
		--data-urlencode "reg=D-AIRY" \
		--data-urlencode "model=A321" \
		--data-urlencode "flight=QQ9999" \
		--data-urlencode "code=QQ" \
		--data-urlencode "airline=QAirline" \
		--data-urlencode "type=pax-regular" \
		--data-urlencode "direction=arrival" \
		--data-urlencode "airport=2" \
		--data-urlencode "from=19.01.2038" \
		--data-urlencode "time=03:14" \
		--data-urlencode "interval=once" \
	"|" sed -r "'s/[0-9]{2}:[0-9]{2}/00:00/g; s/[0-9]{2}\.[0-9]{2}\.[0-9]{4}/00.00.0000/g'"

###############################################################################
# click(submit)
###############################################################################

check "8" curl "$url/?arrival" \
	"|" sed -r "s/'\+[0-9]{1,4} [0-9]{2}:[0-9]{2}'/'+0 00:00'/g"

###############################################################################
###############################################################################

rm -f .COOKIES

diff expect results \
		--recursive --ignore-file-name-case \
		--unified=1 \
		--exclude= \
		--suppress-blank-empty \
		--ignore-tab-expansion \
		--ignore-space-change \
		--ignore-all-space \
		--ignore-blank-lines | \
	grep -v '^diff' | \
	sed -r $'s~^-(.*)$~\033[32m< \\1\033[m~g;
			 s~^\+(.*)$~\033[35m> \\1\033[m~g;
			 s~^@@.*@@$~\033[1;33m&\033[m~g'
