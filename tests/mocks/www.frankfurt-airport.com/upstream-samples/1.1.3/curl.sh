#/bin/bash

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
