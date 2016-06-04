CREATE TABLE IF NOT EXISTS `groups`
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
	PRIMARY KEY(`user`, `group`),
	FOREIGN KEY(`user`) REFERENCES `users`(`id`),
	FOREIGN KEY(`group`) REFERENCES `groups`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE INDEX `membership:user` ON `membership`(`user` ASC);
CREATE INDEX `membership:group` ON `membership`(`group` ASC);

INSERT INTO `groups`(`name`, `comment`)
VALUES
('admin',      NULL),
('users',      NULL),
('addflights', NULL),
('specials',   NULL);


INSERT INTO `membership`(`user`, `group`)
SELECT `users`.`id` AS `user`,
	(SELECT @admin:=`id` FROM `groups` WHERE `name`='admin') AS `group`
FROM `users`
WHERE `name` IN('root', 'flederwiesel');

INSERT INTO `membership`(`user`, `group`)
SELECT `users`.`id` AS `user`,
	(SELECT `id` FROM `groups` WHERE `name`='addflights') AS `group`
FROM `users`
WHERE `name` IN('root', 'flederwiesel');

INSERT INTO `membership`(`user`, `group`)
SELECT `users`.`id` AS `user`,
	(SELECT `id` FROM `groups` WHERE `name`='specials') AS `group`
FROM `users`
WHERE `name` IN('root', 'flederwiesel');

INSERT INTO `membership`(`user`, `group`)
SELECT `users`.`id` AS `user`,
	(SELECT `id` FROM `groups` WHERE `name`='users') AS `group`
FROM `users`;

ALTER TABLE `users` DROP COLUMN `permissions`;
