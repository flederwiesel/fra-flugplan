#!/bin/sh

root=/html
target=fra-schedule

cp -f .config.flederwiesel .config

rev=$(svn info . | awk '/^Revision:/ { print $2; }')
echo -e "\033[36mRevision: $rev\033[m"

lftp <<EOF
open web416:L2ppkt1fl2@www.flederwiesel.com

!echo $rev > revision
!svn info . | awk '/^URL:/ { print $2; }' > history
!echo '------------------------------------------------------------------------' >> history
!svn log | sed -nr 'H; :a /---/ { x; s/\([a-z]+,[^)]+\) //g; s/[0-9]+ lines?//g; s/-*-$//g; s/\n//g; p; n; h; ba }' >> history

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
	--exclude .svn/ \
	--exclude check/ \
	--exclude etc/ \
	--exclude sql/ \
	--exclude METAR/ \
	--exclude ToDo.txt \
	--exclude adminmessage.php \
	--exclude flagcounter.txt
#	--dry-run \

!echo $rev > target
cd ${root}/vault/${target}
put target
!rm target

bye
EOF

rm revision
rm history
