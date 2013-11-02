#!/bin/bash

url='http://localhost/fra-schedule/sql/transfer/transfer-flights.php'

from=$(date "+%Y-%m-%d 23:30")
until=$(date -d "1 day " "+%Y-%m-%d 04:30")

from=$(date -d "$from" +%s)
until=$(date -d "$until" +%s)
diff=0
n=0

while [[ $(date +%s) < $from ]];
do
	echo -n .
	sleep 60
done

date +%Y-%m-%d~%H:%M:%S >&2

while [[ $(date +%s) < $until ]];
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

[ 0 == $diff ] || printf "\n\n"

date +%Y-%m-%d~%H:%M:%S >&2

if [[ "$(date +%s") >= "$until" ]]; then
	echo "Finished due to runtime restrictions." >&2
else
	echo "Finished." >&2
fi
