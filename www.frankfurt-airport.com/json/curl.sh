#/bin/bash

version=1.2.4

this=$(readlink -f "${BASH_SOURCE[0]}")
scriptdir=$(dirname "$this")

mkdir -p "$scriptdir/$version"
cd "$scriptdir/$version"

# | python -mjson.tool
curl --silent --noproxy localhost 'http://www.frankfurt-airport.com/de/_jcr_content.aircrafts.json' > flights_copy.aircrafts.json
curl --silent --noproxy localhost 'http://www.frankfurt-airport.com/de/_jcr_content.airlines.json' > flights_copy.airlines.json
curl --silent --noproxy localhost 'http://www.frankfurt-airport.com/de/_jcr_content.airports.json' > flights_copy.airports.json
curl --silent --noproxy localhost 'http://www.frankfurt-airport.com/de/_jcr_content.arrivals.json/filter?time=2015-12-08T10%3A00%3A00%2B01%3A00&perpage=10&lang=de' > flights_copy.arrivals.json
curl --silent --noproxy localhost 'http://www.frankfurt-airport.com/de/_jcr_content.departures.json/filter?time=2015-12-08T10%3A00%3A00%2B01%3A00&perpage=10&lang=de' > flights_copy.departures.json

mkdir -p beautified

for file in \
	flights_copy.aircrafts \
	flights_copy.airlines \
	flights_copy.airports \
	flights_copy.arrivals \
	flights_copy.departures
do
	jq . "$file.json" > "beautified/$file.json"
done

sed -r -i "s:json/[0-9.]+/flights_copy:json/$version/flights_copy:g" "$scriptdir/../.htaccess"
