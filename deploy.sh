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

root=httpdocs/vault/fra-flugplan

cd $(dirname "$0")
sed -i "s#^[^/]*define[ \t]*([ \t]*'DEBUG'#//&#g" .config

svn upgrade
svn update

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

LC_MESSAGES=en_US svn info . | awk '/^Last Changed (Rev|Date):/ { print $0; }' > revision
(
LC_MESSAGES=en_US svn info . | awk '/^URL:/ { print $2; }'
echo '------------------------------------------------------------------------'
LC_MESSAGES=en_US svn log | sed -nr 'H; :a /---/ { x; s/\([a-z]+,[^)]+\) //g; s/[0-9]+ lines?//g; s/-*-$//g; s/\n//g; p; n; h; ba }'
) > history

ssh fra-flugplan.de "rm -rf $root/$rev"
rsync -rv . fra-flugplan.de:"$root/$rev" \
--exclude=*.dsk \
--exclude=*.ppw \
--exclude=.phped \
--exclude=.svn \
--exclude=api.stopforumspam.org \
--exclude=check \
--exclude=etc \
--exclude=flagcounter.txt \
--exclude=ftp-flederwiesel.sh \
--exclude=METAR \
--exclude=sql \
--exclude=www.frankfurt-airport.com

ssh fra-flugplan.de "echo $rev > $root/target"

rm revision
rm history

echo -e "\033[36mRevision: $rev\033[m"
