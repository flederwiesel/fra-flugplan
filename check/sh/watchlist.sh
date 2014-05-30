#!/bin/sh

###############################################################################
#
#       project: FRA-schedule
#                Auomatic test script
#
#       $Author: flederwiesel $
#         $Date: 2013-07-04 18:16:27 +0200 (Do, 04 Jul 2013) $
#          $Rev: 341 $
#
###############################################################################

# drop/re-create database
initdb && rm -f .COOKIES

# preparation #################################################################

sed "s/%{date}/$(date +'%Y-%m-%d' --date='+1 day 00:00')/g" <<-"SQL" | mysql
	USE fra-schedule;

	SELECT `id` INTO @uid FROM `users` WHERE `name`='root';

	INSERT INTO `aircrafts`
	(
		`uid`, `model`, `reg`
	)
	VALUES
	(@uid, (SELECT `id` FROM `models` WHERE `icao` = 'B77W'), 'B-KPE'),
	(@uid, (SELECT `id` FROM `models` WHERE `icao` = 'A333'), 'C-GFAH'),
	(@uid, (SELECT `id` FROM `models` WHERE `icao` = 'A346'), 'ZS-SNC');

	INSERT INTO `flights`
	(
		`uid`, `type`, `direction`, `airline`, `code`,
		`scheduled`, `expected`, `airport`, `model`, `aircraft`
	)
	VALUES
	(
		@uid, 'pax-regular', 'arrival',
		(SELECT `id` FROM `airlines` WHERE `code`='SA'), '260',
		'%{date} 06:15', NULL,
		(SELECT `id` FROM `airports` WHERE `icao`='FAOR'),
		(SELECT `id` FROM `models` WHERE `icao`='A346'),
		(SELECT `id` FROM `aircrafts` WHERE `reg`='ZS-SNC')
	),
	(
		@uid, 'pax-regular', 'arrival',
		(SELECT `id` FROM `airlines` WHERE `code`='CX'), '289',
		'%{date} 06:20', NULL,
		(SELECT `id` FROM `airports` WHERE `icao`='VHHH'),
		(SELECT `id` FROM `models` WHERE `icao`='B77W'),
		(SELECT `id` FROM `aircrafts` WHERE `reg`='B-KPE')
	),
	(
		@uid, 'pax-regular', 'arrival',
		(SELECT `id` FROM `airlines` WHERE `code`='AC'), '874',
		'%{date} 07:00', NULL,
		(SELECT `id` FROM `airports` WHERE `icao`='CYUL'),
		(SELECT `id` FROM `models` WHERE `icao`='A333'),
		(SELECT `id` FROM `aircrafts` WHERE `reg`='C-GFAH')
	);

	INSERT INTO `users`
	(
		`id`, `name`, `email`, `salt`, `passwd`,
		`language`, `permissions`
	)
	VALUES
	(
		2,
		'flederwiesel',
		'hausmeister@flederwiesel.com',
		'cf78aafd5c5410b7b12c2794a52cda1bccd01316f30df57aa29c5609ba979c15',
		'c4ae99aa0209ce5bea9687cf0548d8ebc942ba14e166c45957a876bcec194fed', # elvizzz
		'en',
		'1'
	);
SQL

# /preparation ################################################################

now=$(rawurlencode $(date +'%Y-%m-%d %H:%M:%S' --date="0 days 23:59"))
today=$(date +'%Y-%m-%d' --date="23:55")

check "1" curl "$url/?req=login\&now=$now" \
		--data-urlencode "user=flederwiesel" \
		--data-urlencode "passwd=elvizzz" \
	"| sed -r '
		s/now=$today/now=0000-00-00/g
	'"

check "2" curl "$url/?arrival\&now=$now" \
	"| sed -r '
		s/now=$today/now=0000-00-00/g
	'"

check "3" curl "$url/?arrival\&now=$now" \
		--data-urlencode "'reg[ZS-SNC]=South African Airways - Star Alliance'" \
		--data-urlencode "'reg[C-????]=Air Canada ?'" \
	"| sed -r '
		s/now=$today/now=0000-00-00/g
	'"

check "4" curl "$url/?arrival\&now=$now" \
		--data-urlencode "'reg[C-*]=Air Canada *'" \
		--data-urlencode "'del[C-????]='" \
	"| sed -r '
		s/now=$today/now=0000-00-00/g
	'"
