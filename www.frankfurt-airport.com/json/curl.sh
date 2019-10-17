#/bin/bash

version=1.4.4

this=$(readlink -f "${BASH_SOURCE[0]}")
scriptdir=$(dirname "$this")

mkdir -p "$scriptdir/$version"
cd "$scriptdir/$version"

time=$(date +'%Y-%m-%dT%H:%M:%S%z' | sed 's/:/%3A/g;s/\+/%2B/g')

curl --silent --noproxy localhost "https://www.frankfurt-airport.com/de/_jcr_content.aircrafts.json" > aircrafts.json
curl --silent --noproxy localhost "https://www.frankfurt-airport.com/de/_jcr_content.airlines.json" > airlines.json
curl --silent --noproxy localhost "https://www.frankfurt-airport.com/de/_jcr_content.airports.json" > airports.json
curl --silent --noproxy localhost "https://www.frankfurt-airport.com/de/_jcr_content.arrivals.json/filter?perpage=10&lang=de&time=$time" > arrivals.json
curl --silent --noproxy localhost "https://www.frankfurt-airport.com/de/_jcr_content.departures.json/filter?perpage=10&lang=de&time=$time" > departures.json
curl --silent --noproxy localhost "https://www.frankfurt-airport.com/de/_jcr_content.arrivals.cargo.json/filter?type=departure&lang=de&time=$time" > arrivals.cargo.json
curl --silent --noproxy localhost "https://www.frankfurt-airport.com/de/_jcr_content.departures.cargo.json/filter?type=departure&lang=de&time=$time" > departures.cargo.json

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
