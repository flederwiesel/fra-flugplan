USE `fra-flugplan`;

SET profiling = 1;

ALTER TABLE `flights` CHANGE `expected` `estimated` DATETIME;
ALTER TABLE `history` CHANGE `expected` `estimated` DATETIME;

ALTER TABLE `flights` ADD COLUMN `expected` DATETIME AS (IFNULL(`estimated`, `scheduled`)) VIRTUAL AFTER `estimated`;
ALTER TABLE `history` ADD COLUMN `expected` DATETIME AS (IFNULL(`estimated`, `scheduled`)) VIRTUAL AFTER `estimated`;

ALTER TABLE `flights` ADD INDEX `i:flights(expected)`(`expected`);
ALTER TABLE `flights` ADD INDEX `i:flights(direction,expected)`(`direction`, `expected`);
ALTER TABLE `flights` ADD INDEX `i:flights(direction,aircraft)`(`direction`, `aircraft`);

INSERT INTO `flights`
SELECT * FROM `history`;

DROP TABLE `history`;

ALTER TABLE `users` ADD INDEX `i:users(name,email)`(`name`, `email`);
ALTER TABLE `users` ADD INDEX `i:users(from,until)`(`notification-from`, `notification-until`);

ALTER TABLE `airports` ADD INDEX `i:airports(iata)`(`iata`);

ALTER TABLE `watchlist` ADD INDEX `i:watchlist(notify)`(`notify`);

ALTER TABLE `watchlist-notifications` ADD INDEX `i:watchlist-notifications(flight,id)`(`flight`, `id`);
ALTER TABLE `watchlist-notifications` ADD INDEX `i:watchlist-notifications(flight,notified)`(`flight`, `notified`);
ALTER TABLE `watchlist-notifications` ADD INDEX `i:watchlist-notifications(notified)`(`notified`);

SHOW PROFILES;
