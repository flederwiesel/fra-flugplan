#/bin/bash

version=1.7

this=$(readlink -f "${BASH_SOURCE[0]}")
scriptdir=$(dirname "$this")

mkdir -p "$scriptdir/$version"
cd "$scriptdir/$version"

time=$(date +'%Y-%m-%dT%H:%M:%S%z' | sed 's/:/%3A/g;s/\+/%2B/g')

curl --silent --noproxy localhost "https://www.frankfurt-airport.com/de/_jcr_content.aircrafts.json" > aircrafts.json
curl --silent --noproxy localhost "https://www.frankfurt-airport.com/de/_jcr_content.airlines.json" > airlines.json
curl --silent --noproxy localhost "https://www.frankfurt-airport.com/de/_jcr_content.airports.json" > airports.json
# https://www.frankfurt-airport.com/de/am-flughafen/fluege.html
curl --silent --noproxy localhost "https://www.frankfurt-airport.com/de/_jcr_content.flights.json/filter?flighttype=arrivals&perpage=10&lang=de&time=$time" > arrivals.json
curl --silent --noproxy localhost "https://www.frankfurt-airport.com/de/_jcr_content.flights.json/filter?flighttype=departures&perpage=10&lang=de&time=$time" > departures.json
curl --silent --noproxy localhost "https://www.frankfurt-airport.com/de/_jcr_content.flights.cargo.json/filter?flighttype=arrivals&lang=de&time=$time" > arrivals.cargo.json
curl --silent --noproxy localhost "https://www.frankfurt-airport.com/de/_jcr_content.flights.cargo.json/filter?flighttype=departures&lang=de&time=$time" > departures.cargo.json

mkdir -p beautified

for file in \
	aircrafts \
	airlines \
	airports \
	arrivals \
	departures \
	arrivals.cargo \
	departures.cargo
do
	jq . "$file.json" > "beautified/$file.json"
done

sed -r -i "s:json/[0-9.]+/:json/$version/:g" "$scriptdir/../.htaccess"
