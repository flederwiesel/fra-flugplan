#!/bin/bash

start='23:00'
fin='05:30'

#url='http://localhost/fra-schedule/sql/transfer/transfer-flights.php'
url='http://www.flederwiesel.com/fra-schedule/sql/transfer/transfer-flights.php'

now() {
	date +%s
	#date -d '2013-11-04 22:00' +%s
	#date -d '2013-11-05 06:00' +%s
}

from=$(date -d "$(date +%Y-%m-%dT$start)" +%s)
until=$(date -d "$(date +%Y-%m-%dT$fin)" +%s)

echo $from | awk '// { print strftime("%Y-%m-%d %H:%M", $0); }'
echo $(now) | awk '// { print strftime("%Y-%m-%d %H:%M", $0); }'
echo $until | awk '// { print strftime("%Y-%m-%d %H:%M", $0); }'

if [[ "$(now)" < "$from" ]] &&
   [[ "$(now)" < "$until" ]]; then
	from=$(date -d "$(date -d '-1 day' +%Y-%m-%dT$start)" +%s)
fi

if [[ "$from" > "$until" ]]; then
	until=$(date -d "$(date -d '1 day' +%Y-%m-%dT$fin)" +%s)
	echo "=="
	echo $from | awk '// { print strftime("%Y-%m-%d %H:%M", $0); }'
	echo $(now) | awk '// { print strftime("%Y-%m-%d %H:%M", $0); }'
	echo $until | awk '// { print strftime("%Y-%m-%d %H:%M", $0); }'
fi

diff=0
n=0

while [[ "$(now)" < "$from" ]];
do
	echo -n .
	sleep 60
done

date +%Y-%m-%d~%H:%M:%S >&2

while [[ "$(now)" < "$until" ]];
do
	if [ $diff -gt 20 ]; then
		sleep 60
		fmt="~%3u,"
	else
		fmt=" %3u,"
	fi

	start="$(date +'%s %N')"
	e=$(curl -# "$url" 2>/dev/null)

	# diff to $start time in ms
#	diff=$(echo "$(date +'%s %N') $start" | awk '// { if ($2 < $4) { $3 = $3 + 1; $4 = $4 - 1000000000; } printf("%u.%03u", $1 - $3, ($2 - $4) / 1000000);}')
	# diff to $start time in s
	diff=$(echo "$(date +'%s %N') $start" | awk '// { printf("%u", $1 - $3);}')
	printf "$fmt" $diff

	if [[ 0 == $((++n % 10)) ]]; then
		printf "\n"
	fi

	if [ -n "$e" ]; then
		echo "$e" >&2
		break
	fi

done

[[ 0 == "$diff" ]] || printf "\n\n"

date +%Y-%m-%d~%H:%M:%S >&2

if [[ "$(now)" >= "$until" ]]; then
	echo "Finished due to runtime restrictions." >&2
else
	echo "Finished." >&2
fi
