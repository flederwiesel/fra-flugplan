START TRANSACTION;

ALTER TABLE `flights` CHANGE `type` `type` enum('P', 'F', 'C') NOT NULL DEFAULT 'P';
ALTER TABLE `history` CHANGE `type` `type` enum('P', 'F', 'C') NOT NULL DEFAULT 'P';

COMMIT;
