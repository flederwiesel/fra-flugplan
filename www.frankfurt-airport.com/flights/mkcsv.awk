#!/bin/sh

###############################################################################
#
#  Copyright © Tobias Kühne
#
#  You may use and distribute this software free of charge for non-commercial
#  purposes. The software must be distributed in its entirety, i.e. containing
#  ALL binary and source files without modification.
#  Publication of modified versions of the source code provided herein,
#  is permitted only with the author's written consent. In this case the
#  copyright notice must not be removed or altered, all modifications to the
#  source code must be clearly marked as such.
#
###############################################################################

# Script for combining all csv files into {arrival,departure}.csv

BEGIN {
	FS = ";";
	OFS = ";";
	last = "";
	nf = 0;
	fid = 0;
	na = 0;
	ac = 0;
	reg = "";
	dir = ""
}

/^#/ && 1 == NR {
	print $0;
}

/^[01]/ {

	if ($11 != "TRN") {

		fid = 0;
		ac = 0;

		# delete C comments from column
		sub(/ *\/\*[^*/]*\*\/ */, "", $3);
		sub(/ *\/\*[^*/]*\*\/ */, "", $12);

		# add querytime day to sched day for flight unique key
		q = gensub(/ .+/, "", "g", $1);
		d = gensub(/ .+$/, "", "g", $3);
		t = gensub(/.+ /, "", "g", $3);

		day = q + d " " t

		if (dir != $2) {
			dir = $2
			flight = "";
		}

		# find flight unique key in array
		for (f in flights) {
			if (flights[f]["uniq"] == day " " $7$8) {
				fid = f;
				break;
			}
		}

		# if flight unknown, insert into array
		if (0 == fid) {
			fid = ++nf;
			flights[fid]["uniq"] = day " " $7$8;
			flights[fid]["fnr"] = $7$8;
			# insert flight id
			$3 = $3 "/*=" fid "*/";
		}

		# if $5 is set, server(lu) will be == db(lu)
		if (0 == length($5)) {
			if (0 == length($12)) {
				## aircraft withdrawn
				if (length(flights[fid]["ac"])) {
					# decrease visits for original equipment
					for (a in aircrafts) {
						if (aircrafts[a]["reg"] == flights[fid]["ac"]) {

							if ("A" == $2)	{
								aircrafts[a]["visits"]--;
								# insert aircraft id,visits
								$12 = "/*" aircrafts[a]["reg"] "=" a "," aircrafts[a]["visits"] "*/";
							}

							flights[fid]["ac"] = "";
							break;
						}
					}
				}
			}
			else {
				# find aircraft in array
				ac = 0;
				# comments must be closed if either `new` or `visit` is entered
				new = "";
				visits = "";
				reg = $12;

				for (a in aircrafts) {
					if (aircrafts[a]["reg"] == $12) {
						ac = a;
						break;
					}
				}

				# if aircraft unknown, insert into array
				if (0 == ac) {
					ac = ++na;
					aircrafts[ac]["reg"] = $12;
					aircrafts[ac]["visits"] = 0;
					new = "/*=" ac;
				}

				if ($13 == "annulliert") {
					$3 = $3 "/*†*/";
				}

				if (0 == length(flights[fid]["ac"])) {
					# no aircraft assigned to flight so far
					if ($13 == "annulliert") {
						if (length(new))
							new = new "*/";
					}
					else {
						flights[fid]["ac"] = reg;

						if ("A" == $2) {
							aircrafts[ac]["visits"]++;
							# insert aircraft visits
							if (0 == length(new))
								visits = "/*=" ac;

							visits = visits "," aircrafts[ac]["visits"] "*/";
						}
						else {
							if (new)
								new = new "*/";
						}
					}
				}
				else {
					if (flights[fid]["ac"] == reg)	{
						if ($13 == "annulliert") {
							if ("A" == $2) {
								visits = "/*";

								# decrease visits for original equipment
								for (a in aircrafts) {
									if (aircrafts[a]["reg"] == flights[fid]["ac"]) {
										aircrafts[a]["visits"]--;
										# insert aircraft id,visits
										visits = visits "=" a "," aircrafts[a]["visits"];
										break;
									}
								}

								visits = visits "*/";

								flights[fid]["ac"] = "";
							}
						}
						else {
							if (new)
								new = new "*/";
						}
					}
					else {
						## aircraft changed
						if ($13 == "annulliert") {
							if (new)
								new = new "*/";
						}
						else {
							if ("A" == $2) {
								# increase visits for newly assigned equipment
								if (0 == length(new))
									visits = "/*=" ac;

								for (a in aircrafts) {
									if (aircrafts[a]["reg"] == reg) {
										aircrafts[a]["visits"]++;
										# insert aircraft id,visits
										visits = visits "," aircrafts[a]["visits"];
										break;
									}
								}

								# decrease visits for original equipment
								for (a in aircrafts) {
									if (aircrafts[a]["reg"] == flights[fid]["ac"]) {

										if ("A" == $2) {
											aircrafts[a]["visits"]--;
											# insert aircraft id,visits
											visits = visits " " flights[fid]["ac"] "=" a "," aircrafts[a]["visits"];
										}

										break;
									}
								}

								visits = visits "*/";
							}

							flights[fid]["ac"] = reg;
						}
					}
				}

				if (length(new) || length(visits))
					$12 = $12 new visits;
			}
		}

		# insert separator for next query cycle
		querytime = $1 $2;

		if (last != querytime) {

			if (last != "")
				print ";";

			last = querytime;
		}
	}

	print $0;
}

END {
	if(0){
	for (f in flights)
		print f "=" flights[f]["uniq"] ":" flights[f]["ac"] > "/dev/stderr"

	n = asorti(aircrafts, indices);

	for (i = 1; i <= n; i++)
		print indices[i] "=" aircrafts[indices[i]]["reg"] ":" aircrafts[indices[i]]["visits"] > "/dev/stderr";
}
}
