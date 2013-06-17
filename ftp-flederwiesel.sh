#!/bin/sh

rev=$(svn info . | awk '/^Revision:/ { print $2; }')

[ -e .config ] || cp -f .config.flederwiesel .config

lftp <<EOF
open web416:L2ppkt1fl2@www.flederwiesel.com

!svn info . | awk '/^(URL|Revision):/ { print $2; }' > revision

rm -rf /html/vault/fra-schedule/$rev
mkdir -p /html/vault/fra-schedule/$rev
cd /html/vault/fra-schedule/$rev

lpwd

mirror --reverse \
	--verbose \
	--no-perms \
	--exclude .config.*/ \
	--exclude .svn/ \
	--exclude check/ \
	--exclude data/betriebsrichtung.* \
	--exclude etc/ \
	--exclude ftp-flederwiesel.lftp \
	--exclude ftp-lorem-ipsum.lftp \
	--exclude sql/ \
	--exclude METAR
#	--dry-run \

#cd /html/vault/fra-schedule
#!echo $rev > recent

bye
EOF

rm revision
