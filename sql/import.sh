#!/bin/sh

if [ $# -lt 1 ]; then
	echo "Usage: $(basename $0) <file>"
else
	schema="usr_web416_3"

	alias "mysql=mysql --silent --host=localhost --user=root --password= --default-character-set=utf8"

	eval mysql <<<$(echo "DROP DATABASE IF EXISTS \`$schema\`")
	eval mysql <<<$(echo "CREATE DATABASE \`$schema\`")
	unzip="gunzip -c '$1'"
	eval mysql <<<"USE \`$schema\`; $(eval $unzip)"

fi
