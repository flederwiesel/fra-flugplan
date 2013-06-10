/* Entries in usr_web416_1 only */

INSERT INTO `fra-schedule`.`airlines`(`uid`, `code`, `name`)
SELECT
 (SELECT `id` FROM `fra-schedule`.`users` WHERE `name`='root') AS `uid`,
 `usr_web416_1`.`airlines`.`abbrev` AS `code`,
 `usr_web416_1`.`airlines`.`name` AS `name`
FROM
 `usr_web416_1`.`airlines`
LEFT JOIN
 `fra-schedule`.`airlines`
ON
 `usr_web416_1`.`airlines`.`abbrev` =
 `fra-schedule`.`airlines`.`code`
WHERE `fra-schedule`.`airlines`.`id` IS NULL;

/* -> 3 row(s) affected, Records: 3  Duplicates: 0  Warnings: 0*/

/* Create mapping */
SELECT
 `usr_web416_1`.`airlines`.`id` AS `src`,
 `fra-schedule`.`airlines`.`id` AS `dst`
FROM
 `usr_web416_1`.`airlines`
LEFT JOIN
 `fra-schedule`.`airlines`
ON
 `usr_web416_1`.`airlines`.`abbrev` =
 `fra-schedule`.`airlines`.`code`
ORDER BY src ASC;

/* Entries in usr_web416_1 only */
INSERT INTO `fra-schedule`.`models`(`uid`, `icao`, `name`)
SELECT
 (SELECT `id` FROM `fra-schedule`.`users` WHERE `name`='root') AS `uid`,
 `usr_web416_1`.`aircraft-types`.`icao` AS `icao`,
 `usr_web416_1`.`aircraft-types`.`name` AS `name`
FROM
 `usr_web416_1`.`aircraft-types`
LEFT JOIN
 `fra-schedule`.`models`
ON
 `usr_web416_1`.`aircraft-types`.`icao` =
 `fra-schedule`.`models`.`icao`
WHERE `fra-schedule`.`models`.`id` IS NULL
 AND LENGTH(`usr_web416_1`.`aircraft-types`.`icao`) > 0;

/* -> 2 row(s) affected, Records: 2  Duplicates: 0  Warnings: 0 */

/* Create mapping */
SELECT
 `usr_web416_1`.`aircraft-types`.`id` AS src,
 `fra-schedule`.`models`.`id` AS dst
FROM
 `usr_web416_1`.`aircraft-types`
LEFT JOIN
 `fra-schedule`.`models`
ON
 `usr_web416_1`.`aircraft-types`.`icao` =
 `fra-schedule`.`models`.`icao`
ORDER BY src ASC;

/* Entries in usr_web416_1 only */

INSERT INTO `fra-schedule`.`airports`(`uid`, `iata`, `icao`, `name`)
SELECT
 (SELECT `id` FROM `fra-schedule`.`users` WHERE `name`='root') AS `uid`,
 `usr_web416_1`.`airports`.`iata` AS `iata`,
 `usr_web416_1`.`airports`.`icao` AS `icao`,
 `usr_web416_1`.`airports`.`name` AS `name`
FROM
 `usr_web416_1`.`airports`
LEFT JOIN
 `fra-schedule`.`airports`
ON
 `usr_web416_1`.`airports`.`icao` =
 `fra-schedule`.`airports`.`icao`
WHERE `fra-schedule`.`airports`.`id` IS NULL;

/* -> 55 row(s) affected, Records: 55  Duplicates: 0  Warnings: 0 */

/* Create mapping */

SELECT
 `usr_web416_1`.`airports`.`id` AS `src`,
 `fra-schedule`.`airports`.`id` AS `dst`
FROM
 `usr_web416_1`.`airports`
LEFT JOIN
 `fra-schedule`.`airports`
ON
(`usr_web416_1`.`airports`.`iata` = `fra-schedule`.`airports`.`iata` AND
 `usr_web416_1`.`airports`.`icao` = `fra-schedule`.`airports`.`icao`)
ORDER BY src ASC;

INSERT INTO `fra-schedule`.`aircrafts`(`uid`, `reg`, `model`)
SELECT
 (SELECT `id` FROM `fra-schedule`.`users` WHERE `name`='root') AS `uid`,
 `usr_web416_1`.`aircrafts`.`reg` AS `reg`,
 `usr_web416_1`.`aircrafts`.`type` AS `type`
FROM
 `usr_web416_1`.`aircrafts`
LEFT JOIN
 `fra-schedule`.`aircrafts`
ON
 `usr_web416_1`.`aircrafts`.`reg` =
 `fra-schedule`.`aircrafts`.`reg`
WHERE `fra-schedule`.`aircrafts`.`id` IS NULL;

/* -> 3475 row(s) affected, Records: 3475  Duplicates: 0  Warnings: 0 */

/* Create mapping */

SELECT
 `usr_web416_1`.`aircrafts`.`id` AS `src`,
 `fra-schedule`.`aircrafts`.`id` AS `dst`
FROM
 `usr_web416_1`.`aircrafts`
LEFT JOIN
 `fra-schedule`.`aircrafts`
ON
 `usr_web416_1`.`aircrafts`.`reg` =
 `fra-schedule`.`aircrafts`.`reg`
ORDER BY src ASC;
