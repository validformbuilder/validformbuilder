
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- dbform
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `dbform`;

CREATE TABLE `dbform`
(
	`name` VARCHAR(255) NOT NULL,
	`serialized` TEXT NOT NULL,
	`is_complete` TINYINT DEFAULT 0 NOT NULL,
	`created_at` DATETIME,
	`updated_at` DATETIME,
	`id` BIGINT NOT NULL AUTO_INCREMENT,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB CHARACTER SET='utf8' COLLATE='utf8_general_ci';

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
