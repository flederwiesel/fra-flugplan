START TRANSACTION;

ALTER TABLE `flights` CHANGE `type` `type` enum('pax-regular', 'cargo', 'P', 'F') NOT NULL DEFAULT 'P';
ALTER TABLE `history` CHANGE `type` `type` enum('pax-regular', 'cargo', 'P', 'F') NOT NULL DEFAULT 'P';

UPDATE `flights` SET `type` = 'F' WHERE `type` = 'cargo';
UPDATE `history` SET `type` = 'F' WHERE `type` = 'cargo';
UPDATE `flights` SET `type` = 'P' WHERE `type` = 'pax-regular';
UPDATE `history` SET `type` = 'P' WHERE `type` = 'pax-regular';

ALTER TABLE `flights` CHANGE `type` `type` enum('P', 'F') NOT NULL DEFAULT 'P';
ALTER TABLE `history` CHANGE `type` `type` enum('P', 'F') NOT NULL DEFAULT 'P';

ALTER TABLE `flights` ADD `last update` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `history` ADD `last update` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

UPDATE `flights` SET `last update` = NOW();
UPDATE `history` SET `last update` = NOW();

COMMIT;
