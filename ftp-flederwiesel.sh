#!/bin/bash

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

root=
target=fra-schedule

cd $(dirname "$0")
sed -i "/define[ \t]*([ \t]*'DEBUG'/d" .config

svn upgrade

info=$(LC_MESSAGES=en_US svn info . 2>&1)

if [ $? -ne 0 ]; then
	echo -e "\033[1;31m$info\033[m"
	exit 1
fi

rev=$(awk '/^Revision:/ { print $2; }' <<<"$info")

if [ -z "$rev" ]; then
	echo -e "\033[1;31mNo revision found:\033[m"
	echo -e "\033[1;31m$info\033[m"
	exit 1
else
	echo -e "\033[36mRevision: $rev\033[m"
fi

# As there is no user/password given here, lftp will look in ~/.netrc
lftp <<EOF
set ftp:ssl-allow true
set ftp:ssl-force true
set ftp:ssl-protect-data yes
set ftp:ssl-protect-list yes
set ftp:ssl-auth TLS
set ftps:initial-prot P

set ssl-allow true
set ssl:check-hostname true
set ssl:verify-certificate false

open fra-flugplan.de

!LC_MESSAGES=en_US svn info . | awk '/^Last Changed (Rev|Date):/ { print \$0; }' > revision
!LC_MESSAGES=en_US svn info . | awk '/^URL:/ { print \$2; }' > history
!echo '------------------------------------------------------------------------' >> history
!LC_MESSAGES=en_US svn log | sed -nr 'H; :a /---/ { x; s/\([a-z]+,[^)]+\) //g; s/[0-9]+ lines?//g; s/-*-\$//g; s/\n//g; p; n; h; ba }' >> history

rm -rf ${root}/$rev
mkdir -p ${root}/$rev
cd ${root}/$rev

mirror --reverse \
	--verbose \
	--no-perms \
	--exclude-glob *.sql.gz \
	--exclude-glob .config.* \
	--exclude-glob data/*.* \
	--exclude-glob img/src/*.* \
	--exclude-glob ftp-*.sh \
	--exclude .svn/ \
	--exclude api.stopforumspam.org \
	--exclude check/ \
	--exclude content/img/src/ \
	--exclude etc/ \
	--exclude img/src/ \
	--exclude logs/ \
	--exclude METAR/ \
	--exclude ssl/ \
	--exclude sql/ \
	--exclude www.frankfurt-airport.com \
	--exclude adminmessage.php \
	--exclude flagcounter.txt \
	--exclude phped.sh \
	--exclude ToDo.txt
#	--dry-run \

chmod +x *.sh
!echo $rev > target
cd ${root}
put target
!rm target

bye
EOF

rm history

echo -e "\033[36mRevision: $rev\033[m"
