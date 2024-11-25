USE `fra-flugplan`;

SET profiling = 1;

ALTER TABLE `flights` CHANGE `expected` `estimated` DATETIME;
ALTER TABLE `history` CHANGE `expected` `estimated` DATETIME;

ALTER TABLE `flights` ADD COLUMN `expected` DATETIME AS (IFNULL(`estimated`, `scheduled`)) VIRTUAL AFTER `estimated`;
ALTER TABLE `history` ADD COLUMN `expected` DATETIME AS (IFNULL(`estimated`, `scheduled`)) VIRTUAL AFTER `estimated`;

ALTER TABLE `flights` ADD INDEX `i:flights(direction,expected)`(`direction`, `expected`);
ALTER TABLE `flights` ADD INDEX `i:flights(expected)`(`expected`);

INSERT INTO `flights`
SELECT * FROM `history`;

DROP TABLE `history`;

INSERT INTO `flights`
SELECT * FROM `history`;

DROP TABLE `history`;

SHOW PROFILES;
