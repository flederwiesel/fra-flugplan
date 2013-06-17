#!/bin/sh

root=/html
target=lorem-ipsum

cp -f .config.flederwiesel .config

rev=$(svn info . | awk '/^Revision:/ { print $2; }')

lftp <<EOF
open web416:L2ppkt1fl2@www.flederwiesel.com

!svn info . | awk '/^URL:/ { print $2; }' > revision

rm -rf ${root}/vault/${target}/$rev
mkdir -p ${root}/vault/${target}/$rev
cd ${root}/vault/${target}/$rev

mirror --reverse \
	--verbose \
	--no-perms \
	--exclude-glob *.sql.gz \
	--exclude-glob .config.* \
	--exclude-glob data/*.* \
	--exclude-glob ftp-*.sh \
	--exclude .svn \
	--exclude check \
	--exclude etc \
	--exclude sql \
	--exclude METAR \
	--exclude ToDo.txt
#	--dry-run \

!echo $rev > target
cd ${root}/vault/${target}
put target
!rm target

bye
EOF

rm revision
