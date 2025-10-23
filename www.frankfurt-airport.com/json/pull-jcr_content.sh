#!/bin/bash

readonly version=2.4.2

set -euo pipefail

this=$(readlink -f "${BASH_SOURCE[0]}")
scriptdir=$(dirname "$this")

mkdir -p "$scriptdir/$version"
cd "$scriptdir/$version"

time=$(date +'%Y-%m-%dT%H:%M:%S%z' | sed 's/:/%3A/g;s/\+/%2B/g')

# Get the JSON URLs from https://www.frankfurt-airport.com/de/am-flughafen/fluege.html ...

# Unique strings across languages:
curl -fsSL "https://www.frankfurt-airport.com/de/_jcr_content.aircrafts.json" | jq > aircrafts.json
curl -fsSL "https://www.frankfurt-airport.com/de/_jcr_content.airlines.json" | jq > airlines.json
curl -fsSL "https://www.frankfurt-airport.com/de/_jcr_content.airports.json" | jq > airports.json

# Those have pre-language strings, e.g. for "status":
for lang in de-DE en-GB zh-CN
do
	baseurl="https://www.frankfurt-airport.com/${lang%%-*}"
	params="&perpage=10&lang=$lang&time=$time"
	curl -fsSL "$baseurl/_jcr_content.flights.json/filter?flighttype=arrivals$params" | jq > "arrivals.$lang.json"
	curl -fsSL "$baseurl/_jcr_content.flights.json/filter?flighttype=departures$params" | jq > "departures.$lang.json"
	curl -fsSL "$baseurl/_jcr_content.flights.cargo.json/filter?flighttype=arrivals$params" | jq > "arrivals.cargo.$lang.json"
	curl -fsSL "$baseurl/_jcr_content.flights.cargo.json/filter?flighttype=departures$params" | jq > "departures.cargo.$lang.json"
done

sed -r -i "s:json/[0-9.]+/:json/$version/:g" "$scriptdir/../.htaccess"
