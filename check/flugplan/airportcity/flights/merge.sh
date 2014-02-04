#!/bin/sh

for dir in arrival departure
do

	cat $dir/* | sort | awk '
		BEGIN {

			FS = ";";
			last = "";
		}

		/^[0-9]/ {

			querytime = gensub(/([01] [0-9][0-9]:[0-9][0-9])[^;]*/, "\\1", "", $1);

			if (last != querytime) {

				if (last != "")
					print ";";

				last = querytime;
			}

			print $0;
		}
	' | iconv -c -f utf-8 -t windows-1252 > $dir.csv

done
