DROP PROCEDURE IF EXISTS `userdel`;

DELIMITER $$

CREATE PROCEDURE `userdel`(IN `id` INT UNSIGNED)
	NOT DETERMINISTIC
	MODIFIES SQL DATA
BEGIN

	DELETE notif, list
	 FROM `watchlist-notifications` notif
	  INNER JOIN `watchlist` list ON list.`id` = notif.`watch`
	 WHERE list.`user` = `id`;

	DELETE FROM `watchlist` WHERE `user`=`id`;
	DELETE FROM `membership` WHERE `user`=`id`;
	DELETE FROM `users` WHERE `users`.`id`=`id`;

END $$

DELIMITER ;
