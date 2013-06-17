#!/usr/bin/lftp -f

open web416:L2ppkt1fl2@www.flederwiesel.com

!svn info . | awk '/^(URL|Revision):/ { print $2; }' > revision
!cp -f .config.flederwiesel .config
!rm -f .config.*
!rm -f data/betriebsrichtung.*

cd /html/fra-schedule

#	--dry-run \
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

!rm revision
!echo fin.

bye
