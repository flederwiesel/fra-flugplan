SET profiling=1;

INSERT INTO `flights`
SELECT * FROM `history`;

DROP TABLE `history`;

SHOW PROFILES;
