#!/bin/sh

if [ $# -lt 1 ]; then
	echo "Usage: $(basename $0) <file>"
else
	schema="usr_web416_3"

	alias "mysql=mysql --host=localhost --user=root --password= --default-character-set=utf8"
	mysql --execute="DROP DATABASE IF EXISTS \`$schema\`; CREATE DATABASE \`$schema\`;"
	(echo "USE \`$schema\`;"; gunzip -c "$1") | mysql
fi
