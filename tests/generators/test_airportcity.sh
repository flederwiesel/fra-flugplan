#!/bin/bash

# Generate test suite `test_airportcity.sh` by copying test setup and helpers
# from `exit`..EOF, then generate test cases and write both to output file.

set -euo pipefail

readonly SCRIPTDIR=$(dirname "${BASH_SOURCE[0]}")
readonly BASENAME=$(basename "${BASH_SOURCE[0]}")
readonly PRJDIR=$(git -C "$SCRIPTDIR" rev-parse --show-toplevel)

{
sed '1,/^exit # testsuite:/d' "${BASH_SOURCE[0]}"

# All requests to "https://${prefix}www.frankfurt-airport.com" mockup
# return 5 pages /w `items=4`, unless specified below
declare -A pages=(
	[0_0800_arrival]="6"
	[0_1200_arrival]="6"
	[0_1300_arrival]="6"
	[0_1400_arrival]="6"
	[0_2300_arrival]="6"
	[1_0800_arrival]="6"
	[1_1200_arrival]="6"
	[1_1300_arrival]="6"
	[1_1400_arrival]="6"
	[1_2300_arrival]="6"

	[0_1300_departure]="6"
	[0_1400_departure]="6"
	[1_1300_departure]="6"
	[1_1400_departure]="6"
)

for d in {0..1}; do
	for t in {05..23}; do
		for dir in arrival departure; do
			for ((page = 1; page <= ${pages[${d}_${t}00_${dir}]:-5}; page++)); do
				echo "test_${d}_${t}00_${dir}_${page}() { getflights $d $t $dir $page; }"
			done
		done
	done
done
} > "$PRJDIR/tests/$BASENAME"

exit # testsuite: ==============================================================
# DO NOT CHANGE THIS FILE. This file is generated automatically.
# If you want to edit this file, change and execute its counterpart in
# "$SCRIPTDIR/generators" instead, redirecting the output here.

fileext=json
items=4
path="www.frankfurt-airport.com/_jcr_content.flights.json/filter"

YYYYmmdd_0=$(date +%Y-%m-%d)
YYYYmmdd_1=$(date +%Y-%m-%d --date="+1 day")
YYYYmmdd_2=$(date +%Y-%m-%d --date="+2 day")
YYYYmmdd_3=$(date +%Y-%m-%d --date="+3 day")

isotime() {
	local t=${1?}

	# Format as ISO8601 and urlencode ":", "+"
	date --date="$t" +"%Y-%m-%dT%H:%M:%S%z" |
	sed "s/:/%3a/g; s/+/%2b/g"
}

getflights() {
	local d=${1?}
	local t=${2?}
	local dir=${3?}
	local page=${4?}

	browse "$url/$path?flighttype=${dir}s&time=$(isotime "$d days $t:00")&items=$items&page=$page" |
	jq '[.data[]|{dir:.dir,sched:.sched,esti:.esti,fnr:.fnr,reg:.reg}]' |
	sed -r "
		s/$YYYYmmdd_0/0000-00-00/g
		s/$YYYYmmdd_1/0000-00-01/g
		s/$YYYYmmdd_2/0000-00-02/g
		s/$YYYYmmdd_3/0000-00-03/g
		s/(T[0-9]{2}:[0-9]{2}:00\+0)[12](00)/\10\2/g
	"
}
