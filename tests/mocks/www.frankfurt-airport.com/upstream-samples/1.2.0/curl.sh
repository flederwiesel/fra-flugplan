#/bin/bash

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
