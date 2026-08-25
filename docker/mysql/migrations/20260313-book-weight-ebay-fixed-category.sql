-- Production migration: add book.weight_g + fixed eBay category config (idempotent)

SET @db_name := DATABASE();

CREATE TABLE IF NOT EXISTS `config` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `description` MEDIUMTEXT NOT NULL,
    `value` MEDIUMTEXT NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_config_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'book'
      AND COLUMN_NAME = 'pageNumber'
);
SET @sql := IF(
    @col_exists = 0,
    "ALTER TABLE `book` ADD COLUMN `pageNumber` VARCHAR(50) NOT NULL DEFAULT ''",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'book'
      AND COLUMN_NAME = 'weight_g'
);
SET @sql := IF(
    @col_exists = 0,
    "ALTER TABLE `book` ADD COLUMN `weight_g` INT NOT NULL DEFAULT 0 AFTER `pageNumber`",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `book`
SET `weight_g` = 0
WHERE `weight_g` IS NULL OR `weight_g` < 0;

INSERT INTO `config` (`name`, `description`, `value`)
SELECT 'ebay_fixed_category_id', 'ID catégorie eBay fixe utilisée pour les offres (ex: Livres anciens et de collection = 29223)', '29223'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `config` WHERE `name`='ebay_fixed_category_id'
);
