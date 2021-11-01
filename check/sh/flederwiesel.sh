#!/bin/bash

###############################################################################
#
#  Copyright © Tobias Kühne
#
#  You may use and distribute this software free of charge for non-commercial
#  purposes. The software must be distributed in its entirety, i.e. containing
#  ALL binary and source files without modification.
#  Publication of modified versions of the source code provided herein,
#  is permitted only with the author's written consent. In this case the
#  copyright notice must not be removed or altered, all modifications to the
#  source code must be clearly marked as such.
#
###############################################################################

# drop/re-create database
initdb && rm -f .COOKIES

query <<-"EOF"
	USE fra-flugplan;

	INSERT INTO `users`
	(
		`email`,
		`name`,
		`salt`,
		`passwd`,
		`timezone`,
		`language`
	)
	VALUES
	(
		'hausmeister@flederwiesel.com',
		'flederwiesel',
		'ad879fa6950455c6bbe11b96d2038b6bd2e91a3c95f9624500d16c2bf3759e2c',
		'3209cdc842a87229023e3f1a01f0051f87710dafa417960e8436469a41343e30',
		3600,
		'en'
	);

	INSERT INTO `membership`(`user`, `group`)
	(
		SELECT LAST_INSERT_ID() AS `user`, `id` AS `group`
		FROM `groups`
		WHERE `name` IN ('admin', 'addflights', 'specials', 'users')
	);
EOF
