#!/bin/sh

curl "http://localhost/fra-schedule/" > index.htm
curl "http://localhost/fra-schedule/?lang=en" > index.en.htm
curl "http://localhost/fra-schedule/?lang=de" > index.de.htm
