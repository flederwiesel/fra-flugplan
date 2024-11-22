USE `fra-flugplan`;

SET profiling = 1;

ALTER TABLE `flights` CHANGE `expected` `estimated` DATETIME;
ALTER TABLE `history` CHANGE `expected` `estimated` DATETIME;

ALTER TABLE `flights` ADD COLUMN `expected` DATETIME AS (IFNULL(`estimated`, `scheduled`)) VIRTUAL AFTER `estimated`;
ALTER TABLE `history` ADD COLUMN `expected` DATETIME AS (IFNULL(`estimated`, `scheduled`)) VIRTUAL AFTER `estimated`;

ALTER TABLE `flights` ADD INDEX `i:flights(direction,expected)`(`direction`, `expected`);
ALTER TABLE `history` ADD INDEX `i:history(direction,expected)`(`direction`, `expected`);

ALTER TABLE `flights` ADD INDEX `i:flights(expected)`(`expected`);
ALTER TABLE `history` ADD INDEX `i:history(expected)`(`expected`);

SHOW PROFILES;
