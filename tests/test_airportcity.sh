items=4
path="www.frankfurt-airport.com/_jcr_content.flights.json/filter"

test_json() {
	browse "$url/$path?$request" |
	jq '[.data[]|{dir:.dir,sched:.sched,esti:.esti,fnr:.fnr,reg:.reg}]' |
	sed -r "
		s/$YYYYmmdd_0/0000-00-00/g
		s/$YYYYmmdd_1/0000-00-01/g
		s/$YYYYmmdd_2/0000-00-02/g
		s/$YYYYmmdd_3/0000-00-03/g
		s/(T[0-9]{2}:[0-9]{2}:00\+0)[12](00)/\10\2/g
	"
}

for day in {0..1}
do

	for t in {5..23}
	do
		time=$(printf '%02u:00' $t)
		ddHHMM=$(printf '%02u' $day)-$(date +'%H%M' --date="$time")

		if [ "${airportcity_stop_before:-}" = "$ddHHMM" ]; then
			exit 1
		fi

		YYYYmmdd_0=$(date +%Y-%m-%d)
		YYYYmmdd_1=$(date +%Y-%m-%d --date="+1 day")
		YYYYmmdd_2=$(date +%Y-%m-%d --date="+2 days")
		YYYYmmdd_3=$(date +%Y-%m-%d --date="+3 days")
		YYYYmmddTHHMMSSZ=$(date +'%Y-%m-%dT%H:%M:%S%z' --date="$day day $time:00")

		for dir in arrival departure
		do
			# get number of pages
			request="flighttype=${dir}s&time=$(rawurlencode $YYYYmmddTHHMMSSZ)&items=$items&page=1"
			pages=$(browse "$url/$path?$request" | jq .maxpage)

			if [ $? -eq 0 ]; then
				if [ -n "$pages" ]; then
					page=1

					while [ $page -le $pages ]
					do
						request="flighttype=${dir}s&time=$(rawurlencode $YYYYmmddTHHMMSSZ)&items=$items&page=$page"
						d_HH00_d_p=$(printf "%d_%02u00_%s_%d" "$day" "$t" "$dir" "$page")

						fileext=json check "$d_HH00_d_p" test_json

						((page++))
					done
				fi
			fi
		done
	done
done
