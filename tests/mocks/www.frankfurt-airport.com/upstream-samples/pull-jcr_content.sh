#!/bin/bash

set -euo pipefail

readonly VERSION=2.6.1
readonly SCRIPTDIR=$(realpath "$(dirname "${BASH_SOURCE[0]}")")

mkdir -p "$SCRIPTDIR/$VERSION"

cd "$SCRIPTDIR/$VERSION"

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

# Check JSON for unknown keys

known=$(
	sed 's:\s*//.*$::g' "$SCRIPTDIR/known-keys.jsonc"
)

echo -n "Unknown keys in captured JSON: "

# Read all captured arrivals/departures, extract keys
# as array and subtract known keys from it.
jq --slurp \
	--argjson known "$known" \
	'[ .[].data[] | keys ] | flatten | sort | unique | . - $known' \
	"$SCRIPTDIR/$VERSION/arrivals".*.json \
	"$SCRIPTDIR/$VERSION/departures".*.json
