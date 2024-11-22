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

DROP DATABASE IF EXISTS `fra-flugplan`;

CREATE DATABASE `fra-flugplan` CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci';
USE `fra-flugplan`;

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
	`timezone` smallint DEFAULT 3600,
	`language` varchar(2) NOT NULL DEFAULT 'en',
	`ip` varchar(16) NOT NULL,	/* at the time of registration */
	`tm-` integer NOT NULL DEFAULT 0,
	`tm+` integer NOT NULL DEFAULT 3600,
	`tt-` integer NOT NULL DEFAULT 0,
	`tt+` integer NOT NULL DEFAULT 86400,
	`notification-from` time NOT NULL DEFAULT '00:00:00',
	`notification-until` time NOT NULL DEFAULT '00:00:00',
	`last login` timestamp NULL DEFAULT NULL,
	`send mail` BOOL DEFAULT TRUE,
	`notification-timefmt` varchar(24) DEFAULT NULL,
	`photodb` varchar(24) DEFAULT 'airliners.net',
	CONSTRAINT `pk:users(id)` PRIMARY KEY (`id`),
	UNIQUE KEY `u:users(email)`(`email`),
	UNIQUE KEY `u:users(name)`(`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `groups`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`name` varchar(64) NOT NULL,
	`comment` varchar(64) DEFAULT NULL,
	CONSTRAINT `pk:groups(id)` PRIMARY KEY (`id`),
	UNIQUE KEY `u:groups(name)`(`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `membership`
(
	`user` integer NOT NULL,
	`group` integer NOT NULL,
	CONSTRAINT `pk:membership(user,group)` PRIMARY KEY (`user`, `group`),
	CONSTRAINT `fk:membership(user)=users(id)` FOREIGN KEY (`user`) REFERENCES `users`(`id`),
	CONSTRAINT `fk:membership(group)=groups(id)` FOREIGN KEY (`group`) REFERENCES `groups`(`id`),
	INDEX `i:membership(user)`(`user`),
	INDEX `i:membership(group)`(`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `countries` # ISO 3166-1
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`en` varchar(64) NOT NULL,
	`de` varchar(64) NOT NULL,
	`fr` varchar(64) NOT NULL,
	`alpha-2` varchar(2) NOT NULL,
	`alpha-3` varchar(3) NOT NULL,
	`num` smallint NOT NULL,
	CONSTRAINT `pk:countries(id)` PRIMARY KEY(`id`),
	CONSTRAINT `u:countries(alpha-2)` UNIQUE(`alpha-2`),
	CONSTRAINT `u:countries(alpha-3)` UNIQUE(`alpha-3`),
	CONSTRAINT `u:countries(num)` UNIQUE(`num`),
	INDEX `i:countries(en)`(`en`),
	INDEX `i:countries(de)`(`de`),
	INDEX `i:countries(fr)`(`fr`),
	INDEX `i:countries(alpha-2)`(`alpha-2`),
	INDEX `i:countries(alpha-3)`(`alpha-3`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT=	'#https://www.iso.org/obp/ui/#search';

CREATE TABLE `airlines`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`code` varchar(3) NOT NULL,
	`iata` varchar(2) DEFAULT NULL,
	`icao` varchar(3) DEFAULT NULL,
	`name` varchar(128) DEFAULT NULL,
	CONSTRAINT `pk:airlines(id)` PRIMARY KEY (`id`),
	UNIQUE KEY `u:airlines(code)`(`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `models`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`icao` varchar(4) NOT NULL,
	`name` varchar(96) NOT NULL,
	CONSTRAINT `pk:models(id)` PRIMARY KEY (`id`),
	UNIQUE KEY `u:models(icao)`(`icao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='http://www.airlinecodes.co.uk';

CREATE TABLE `aircrafts`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`reg` varchar(8) NOT NULL,
	`model` integer NOT NULL,
	CONSTRAINT `pk:aircrafts(id)` PRIMARY KEY (`id`),
	CONSTRAINT `fk:aircrafts(model)=models(id)` FOREIGN KEY (`model`) REFERENCES `models`(`id`),
	UNIQUE KEY `u:aircrafts(reg)`(`reg`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `airports`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`iata` varchar(3) DEFAULT NULL,
	`icao` varchar(4) NOT NULL,
	`name` varchar(255) NOT NULL,
	`country` integer DEFAULT NULL,
	`lat` double DEFAULT NULL,
	`lon` double DEFAULT NULL,
	CONSTRAINT `pk:airports(id)` PRIMARY KEY (`id`),
	CONSTRAINT `fk:airports(country)=countries(id)` FOREIGN KEY (`country`) REFERENCES `countries`(`id`),
	UNIQUE KEY `u:airports(icao)`(`icao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `flights`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`type` enum('P', 'F', 'C') NOT NULL DEFAULT 'P',
	`direction` enum('arrival', 'departure') NOT NULL,
	`airline` integer NOT NULL,
	`code` varchar(6) NOT NULL,
	`scheduled` datetime NOT NULL,
	`estimated` datetime NULL DEFAULT NULL,
	`expected` datetime AS (IFNULL(`estimated`, `scheduled`)) VIRTUAL,
	`airport` integer DEFAULT NULL,
	`model` integer DEFAULT NULL,
	`aircraft` integer DEFAULT NULL,
	`last update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT `pk:flights(id)` PRIMARY KEY (`id`),
	CONSTRAINT `fk:flights(airline)=airlines(id)` FOREIGN KEY (`airline`) REFERENCES `airlines`(`id`),
	CONSTRAINT `fk:flights(airport)=airports(id)` FOREIGN KEY (`airport`) REFERENCES `airports`(`id`),
	CONSTRAINT `fk:flights(model)=models(id)` FOREIGN KEY (`model`) REFERENCES `models`(`id`),
	CONSTRAINT `fk:flights(aircraft)=aircrafts(id)` FOREIGN KEY (`aircraft`) REFERENCES `aircrafts`(`id`),
	UNIQUE KEY `u:flights(direction,airline,code,scheduled)` (`direction`, `airline`, `code`, `scheduled`),
	INDEX `i:flights(direction)`(`direction`),
	INDEX `i:flights(direction,expected)`(`direction`, `expected`),
	INDEX `i:flights(expected)`(`expected`),
	INDEX `i:flights(scheduled)`(`scheduled`),
	INDEX `i:flights(code)`(`code`),
	INDEX `i:flights(aircraft)`(`aircraft`)
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
	CONSTRAINT `pk:visits(aircraft)` PRIMARY KEY (`aircraft`),
	CONSTRAINT `fk:visits(aircraft)=aircrafts(id)` FOREIGN KEY (`aircraft`) REFERENCES `aircrafts`(`id`),
	INDEX `i:visits(aircraft)`(`aircraft`),
	INDEX `i:visits(current)`(`current`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `watchlist`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`user` integer NOT NULL,
	`notify` bool DEFAULT FALSE,
	`reg` varchar(31) NOT NULL,
	`comment` varchar(255) DEFAULT NULL,
	CONSTRAINT `pk:watchlist(id)` PRIMARY KEY (`id`),
	CONSTRAINT `fk:watchlist(user)=users(id)` FOREIGN KEY (`user`) REFERENCES `users`(`id`),
	UNIQUE KEY `u:watchlist(user,reg)`(`user`, `reg`),
	INDEX `i:watchlist(reg)`(`reg`),
	INDEX `i:watchlist(user)`(`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `watchlist-notifications`
(
	`id` integer NOT NULL AUTO_INCREMENT,
	`watch` integer NOT NULL,
	`flight` integer NOT NULL,
	`notified` datetime DEFAULT NULL,
	CONSTRAINT `pk:watchlist-notifications(id)` PRIMARY KEY (`id`),
	CONSTRAINT `fk:watchlist-notifications(watch)=watchlist(id)` FOREIGN KEY (`watch`) REFERENCES `watchlist`(`id`),
	CONSTRAINT `fk:watchlist-notifications(flight)=flights(id)` FOREIGN KEY (`flight`) REFERENCES `flights`(`id`),
	UNIQUE KEY `u:watchlist-notifications(watch, flight)`(`watch`, `flight`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/******************************************************************************
 * Administrative data
 ******************************************************************************/

INSERT INTO `groups`(`name`, `comment`)
VALUES
('admin',      NULL),
('users',      NULL),
('addflights', NULL),
('specials',   NULL);
