#!/bin/sh

schema="fra-schedule"
file="usr_web416_3-2013-09-08.sql.gz"

alias "mysql=mysql --host=localhost --user=root --password= --default-character-set=utf8"
mysql --execute="DROP DATABASE IF EXISTS \`$schema\`; CREATE DATABASE \`$schema\`;"
(echo "USE \`$schema\`;"; gunzip -c "../data/$file") | mysql
