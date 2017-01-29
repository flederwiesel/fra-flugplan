/******************************************************************************
 *
 * Copyright © Tobias Kühne
 *
 * You may use and distribute this software free of charge for non-commercial
 * purposes. The software must be distributed in its entirety, i.e. containing
 * ALL binary and source files without modification.
 * Publication of modified versions of the source code provided herein,
 * is permitted only with the author's written consent. In this case the
 * copyright notice must not be removed or altered, all modifications to the
 * source code must be clearly marked as such.
 *
 ******************************************************************************/

DROP DATABASE IF EXISTS `flederwiesel_fra-schedule`;

CREATE DATABASE `flederwiesel_fra-schedule` CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci';
USE `flederwiesel_fra-schedule`;

/******************************************************************************
 * Tables
 ******************************************************************************/

CREATE TABLE `users`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`email` varchar(255) NOT NULL,
	`name` varchar(64) NOT NULL,
	`salt` varchar(64) NOT NULL,
	`passwd` varchar(64) NOT NULL,
	`token` varchar(64) DEFAULT NULL,
	`token_type` enum('none', 'activation', 'password') NOT NULL,
	`token_expires` timestamp NULL DEFAULT NULL,
	`last login` timestamp NULL DEFAULT NULL,
	`timezone` smallint DEFAULT 3600,
	`language` varchar(2) NOT NULL DEFAULT 'en',
	`ip` varchar(16) NOT NULL,	/* at the time of registration */
	`tm-` integer NOT NULL DEFAULT 0,
	`tm+` integer NOT NULL DEFAULT 3600,
	`tt-` integer NOT NULL DEFAULT 0,
	`tt+` integer NOT NULL DEFAULT 86400,
	`notification-from` time NOT NULL DEFAULT '00:00:00',
	`notification-until` time NOT NULL DEFAULT '00:00:00',
	`send mail` BOOL DEFAULT TRUE,
	`notification-timefmt` varchar(24) DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`),
	UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `groups`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`name` varchar(64) NOT NULL,
	`comment` varchar(64) DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `membership`
(
	`user` integer NOT NULL,
	`group` integer NOT NULL,
	PRIMARY KEY (`user`, `group`),
	FOREIGN KEY (`user`) REFERENCES `users`(`id`),
	FOREIGN KEY (`group`) REFERENCES `groups`(`id`),
	INDEX `membership:user`(`user` ASC),
	INDEX `membership:group`(`group` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `airlines`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`code` varchar(3) NOT NULL,
	`name` varchar(128) DEFAULT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `unique: code` UNIQUE (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `models`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`icao` varchar(4) NOT NULL,
	`name` varchar(96) NOT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `unique:icao` UNIQUE (`icao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='http://www.airlinecodes.co.uk';

CREATE TABLE `aircrafts`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`reg` varchar(8) NOT NULL,
	`model` integer NOT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`model`) REFERENCES `models`(`id`),
	CONSTRAINT `unique:reg` UNIQUE (`reg`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `airports`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`iata` varchar(3) DEFAULT NULL,
	`icao` varchar(4) NOT NULL,
	`name` varchar(255) NOT NULL,
	PRIMARY KEY (`id`),
	CONSTRAINT `unique:icao` UNIQUE(`icao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `flights`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`type` enum('P', 'F', 'C') NOT NULL DEFAULT 'P',
	`direction` enum('arrival', 'departure') NOT NULL,
	`airline` integer NOT NULL,
	`code` varchar(6) NOT NULL,
	`scheduled` datetime NOT NULL,
	`expected` timestamp NULL DEFAULT NULL,
	`airport` integer DEFAULT NULL,
	`model` integer DEFAULT NULL,
	`aircraft` integer DEFAULT NULL,
	`last update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`airline`) REFERENCES `airlines`(`id`),
	FOREIGN KEY (`airport`) REFERENCES `airports`(`id`),
	FOREIGN KEY (`model`) REFERENCES `models`(`id`),
	FOREIGN KEY (`aircraft`) REFERENCES `aircrafts`(`id`),
	CONSTRAINT `unique:direction, airline, code, scheduled` UNIQUE (`direction`, `airline`, `code`, `scheduled`),
	INDEX `flights:scheduled`(`scheduled` ASC),
	INDEX `flights:code`(`code` ASC),
	INDEX `flights:direction`(`direction` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* Copy `flights` table structure (including indices!) */
CREATE TABLE `history` LIKE `flights`;

/* Remove AUTO_INCREMENT */
ALTER TABLE `history` AUTO_INCREMENT = 0;

/* Count visits to FRA */
CREATE TABLE `visits`
(
	`aircraft` integer NOT NULL,
	`num` integer NOT NULL,
	`current` datetime NOT NULL,
	`previous` datetime DEFAULT NULL,
	PRIMARY KEY (`aircraft`),
	FOREIGN KEY (`aircraft`) REFERENCES `aircrafts`(`id`),
	INDEX `visits:aircraft`(`aircraft` ASC),
	INDEX `visits:current`(`current` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `watchlist`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`user` integer NOT NULL,
	`notify` bool DEFAULT FALSE,
	`reg` varchar(31) NOT NULL,
	`comment` varchar(255) DEFAULT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`user`) REFERENCES `users`(`id`),
	UNIQUE KEY `user, reg` (`user`, `reg`),
	INDEX `watchlist:user`(`user` ASC),
	INDEX `watchlist:reg`(`reg` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `watchlist-notifications`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`watch` integer NOT NULL,
	`flight` integer NOT NULL,
	`notified` datetime DEFAULT NULL,
	PRIMARY KEY (`id`),
	FOREIGN KEY (`watch`) REFERENCES `watchlist`(`id`),
	FOREIGN KEY (`flight`) REFERENCES `flights`(`id`),
	UNIQUE KEY (`watch`, `flight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/******************************************************************************
 * Indices
 ******************************************************************************/

INSERT INTO `groups`(`name`, `comment`)
VALUES
('admin',      NULL),
('users',      NULL),
('addflights', NULL),
('specials',   NULL);
