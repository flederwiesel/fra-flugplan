#!/bin/sh

root=
target=fra-schedule

cp -f .config.flederwiesel .config

rev=$(LC_MESSAGES=en_US svn info . | awk '/^Revision:/ { print $2; }')
echo -e "\033[36mRevision: $rev\033[m"

lftp <<EOF
open "web416f3:Fox@- 2>&1"@www.flederwiesel.com

!LC_MESSAGES=en_US svn info . | awk '/^Last Changed (Rev|Date):/ { print \$0; }' > revision
!LC_MESSAGES=en_US svn info . | awk '/^URL:/ { print \$2; }' > history
!echo '------------------------------------------------------------------------' >> history
!svn log | sed -nr 'H; :a /---/ { x; s/\([a-z]+,[^)]+\) //g; s/[0-9]+ lines?//g; s/-*-\$//g; s/\n//g; p; n; h; ba }' >> history

rm -rf ${root}/$rev
mkdir -p ${root}/$rev
cd ${root}/$rev

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
cd ${root}
put target
!rm target

bye
EOF

rm revision
rm history
