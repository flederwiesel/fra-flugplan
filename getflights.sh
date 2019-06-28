#!/bin/bash

date=$(date --rfc-3339=seconds)
result=$(curl -Ls https://www.fra-flugplan.de/fra-flugplan/getflights.php 2>&1)
status=$?

if [ $status -eq 0 ]; then
	if [ -n "$result" ]; then
		echo "$date $result" | tee >(sed ':a N; s/\n//g; ta' >> ~/httpdocs/var/log/getflights)
		status=1
	fi
fi

echo "$date $status" > ~/httpdocs/var/log/getflights.lastrun

exit $status
