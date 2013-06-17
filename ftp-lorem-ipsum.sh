#!/bin/sh

rev=$(svn info . | awk '/^Revision:/ { print $2; }')

[ -e .config ] || cp -f .config.lorem-ipsum .config

lftp <<EOF
open web416:L2ppkt1fl2@www.flederwiesel.com

!svn info . | awk '/^(URL|Revision):/ { print $2; }' > revision

rm -rf /html/vault/lorem-ipsum/$rev
mkdir -p /html/vault/lorem-ipsum/$rev
cd /html/vault/lorem-ipsum/$rev

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

#cd /html/vault/lorem-ipsum
#!echo $rev > recent

bye
EOF

rm revision
