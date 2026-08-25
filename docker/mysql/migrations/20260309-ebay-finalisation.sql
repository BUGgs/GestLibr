-- Production migration: eBay finalization
-- Keep in sync with docker/mysql/init/04-ebay-finalisation.sql

SET @db_name := DATABASE();

-- ------------------------------------------------------------
-- Legacy compatibility bootstrap (old schema -> modern schema)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `config` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `description` MEDIUMTEXT NOT NULL,
    `value` MEDIUMTEXT NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_config_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `groups` (
    `idGroup` INT NOT NULL,
    `groupName` VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (`idGroup`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `language` (
    `idLanguage` INT NOT NULL AUTO_INCREMENT,
    `languageName` VARCHAR(100) NOT NULL DEFAULT '',
    `idGroup` INT NOT NULL DEFAULT 1,
    PRIMARY KEY (`idLanguage`),
    KEY `idx_language_group` (`idGroup`, `languageName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `bookupdate` (
    `idBookupdate` INT NOT NULL AUTO_INCREMENT,
    `idBook` INT NOT NULL DEFAULT 0,
    `date` INT NOT NULL DEFAULT 0,
    `type` VARCHAR(50) NOT NULL DEFAULT '',
    `old_value` MEDIUMTEXT,
    `new_value` MEDIUMTEXT,
    `comment` MEDIUMTEXT,
    `idUser` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`idBookupdate`),
    KEY `idx_bookupdate_book` (`idBook`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `groups` (`idGroup`, `groupName`)
SELECT 1, 'Default'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `groups` WHERE `idGroup`=1);

INSERT INTO `language` (`languageName`, `idGroup`)
SELECT 'Français', 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `language` WHERE `idGroup`=1 AND LOWER(TRIM(`languageName`))='français'
);

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'idGroup'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `idGroup` INT NOT NULL DEFAULT 1", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'editor'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `editor` VARCHAR(250) NOT NULL DEFAULT ''", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'publishDate'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `publishDate` VARCHAR(30) NOT NULL DEFAULT ''", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'publishLocation'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `publishLocation` VARCHAR(250) NOT NULL DEFAULT ''", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'format'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `format` INT NOT NULL DEFAULT -1", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'pageNumber'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `pageNumber` VARCHAR(50) NOT NULL DEFAULT ''", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'binding'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `binding` INT NOT NULL DEFAULT -1", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'language'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `language` INT NOT NULL DEFAULT -1", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'collection'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `collection` VARCHAR(255) NOT NULL DEFAULT ''", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'condition'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `condition` INT NOT NULL DEFAULT -1", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'price_ebay'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `price_ebay` VARCHAR(20) NOT NULL DEFAULT ''", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'price_amazon'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `price_amazon` VARCHAR(20) NOT NULL DEFAULT ''", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'internet'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `internet` CHAR(1) NOT NULL DEFAULT '1'", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'location'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `location` VARCHAR(10) NOT NULL DEFAULT ''", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'isbn'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `isbn` VARCHAR(30) NOT NULL DEFAULT ''", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'onEbay'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `onEbay` INT(1) NOT NULL DEFAULT 0", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'ebayItemId'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `ebayItemId` VARCHAR(30) NOT NULL DEFAULT ''", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'ebayLastSync'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `ebayLastSync` INT NOT NULL DEFAULT 0", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'ebayCategoryId'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `ebayCategoryId` INT NOT NULL DEFAULT -1", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'ebayCategoryIdSecondary'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `ebayCategoryIdSecondary` INT NOT NULL DEFAULT -1", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'ebayShippingCost'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `ebayShippingCost` DECIMAL(10,2) NOT NULL DEFAULT 0.00", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'book' AND COLUMN_NAME = 'ebayShippingCostInternational'
);
SET @sql := IF(@col_exists = 0, "ALTER TABLE `book` ADD COLUMN `ebayShippingCostInternational` DECIMAL(10,2) NOT NULL DEFAULT 0.00", "SELECT 1");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- book: per-book policy override fields
-- ------------------------------------------------------------
SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'book'
      AND COLUMN_NAME = 'ebayPolicyMode'
);
SET @sql := IF(
    @col_exists = 0,
    "ALTER TABLE `book` ADD COLUMN `ebayPolicyMode` VARCHAR(16) NOT NULL DEFAULT 'default' AFTER `onEbay`",
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'book'
      AND COLUMN_NAME = 'ebayPaymentPolicyIdOverride'
);
SET @sql := IF(
    @col_exists = 0,
    "ALTER TABLE `book` ADD COLUMN `ebayPaymentPolicyIdOverride` VARCHAR(128) NOT NULL DEFAULT '' AFTER `ebayPolicyMode`",
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'book'
      AND COLUMN_NAME = 'ebayFulfillmentPolicyIdOverride'
);
SET @sql := IF(
    @col_exists = 0,
    "ALTER TABLE `book` ADD COLUMN `ebayFulfillmentPolicyIdOverride` VARCHAR(128) NOT NULL DEFAULT '' AFTER `ebayPaymentPolicyIdOverride`",
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'book'
      AND COLUMN_NAME = 'ebayReturnPolicyIdOverride'
);
SET @sql := IF(
    @col_exists = 0,
    "ALTER TABLE `book` ADD COLUMN `ebayReturnPolicyIdOverride` VARCHAR(128) NOT NULL DEFAULT '' AFTER `ebayFulfillmentPolicyIdOverride`",
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `book`
SET `ebayPolicyMode`='default'
WHERE `ebayPolicyMode` IS NULL OR `ebayPolicyMode`='';

SET @idx_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'book'
      AND INDEX_NAME = 'idx_book_ebay_sync'
);
SET @sql := IF(
    @idx_exists = 0,
    "ALTER TABLE `book` ADD INDEX `idx_book_ebay_sync` (`idGroup`, `onEbay`, `quantity`, `ebayItemId`, `modifyDate`, `ebayLastSync`)",
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- ebay_group_settings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ebay_group_settings` (
    `idGroup` INT NOT NULL,
    `marketplaceId` VARCHAR(32) NOT NULL DEFAULT 'EBAY_FR',
    `merchantLocationKey` VARCHAR(128) NOT NULL DEFAULT '',
    `defaultPaymentPolicyId` VARCHAR(128) NOT NULL DEFAULT '',
    `defaultFulfillmentPolicyId` VARCHAR(128) NOT NULL DEFAULT '',
    `defaultReturnPolicyId` VARCHAR(128) NOT NULL DEFAULT '',
    `updatedAtTs` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`idGroup`),
    KEY `idx_ebay_group_settings_updated` (`updatedAtTs`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- ebay_policy_catalog
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ebay_policy_catalog` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `idGroup` INT NOT NULL,
    `policyType` VARCHAR(24) NOT NULL,
    `policyId` VARCHAR(128) NOT NULL,
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `description` TEXT,
    `marketplaceId` VARCHAR(32) NOT NULL DEFAULT '',
    `rawJson` MEDIUMTEXT,
    `syncedAtTs` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_group_type_policy` (`idGroup`, `policyType`, `policyId`),
    KEY `idx_group_type` (`idGroup`, `policyType`),
    KEY `idx_synced` (`syncedAtTs`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- ebay_store_category
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ebay_store_category` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `idGroup` INT NOT NULL,
    `storeCategoryId` VARCHAR(64) NOT NULL,
    `parentStoreCategoryId` VARCHAR(64) NOT NULL DEFAULT '',
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `displayOrder` INT NOT NULL DEFAULT 0,
    `level` INT NOT NULL DEFAULT 0,
    `leaf` TINYINT(1) NOT NULL DEFAULT 0,
    `rawJson` MEDIUMTEXT,
    `syncedAtTs` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_group_store_cat` (`idGroup`, `storeCategoryId`),
    KEY `idx_group_parent` (`idGroup`, `parentStoreCategoryId`),
    KEY `idx_group_name` (`idGroup`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- ebay_category_store_map
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ebay_category_store_map` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `idGroup` INT NOT NULL,
    `idCategory` INT NOT NULL,
    `storeCategoryId` VARCHAR(64) NOT NULL,
    `updatedAtTs` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_group_category` (`idGroup`, `idCategory`),
    KEY `idx_group_store_cat` (`idGroup`, `storeCategoryId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- ebay_sync_run
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ebay_sync_run` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `idGroup` INT NOT NULL,
    `startedAtTs` INT NOT NULL DEFAULT 0,
    `endedAtTs` INT NOT NULL DEFAULT 0,
    `triggerSource` VARCHAR(32) NOT NULL DEFAULT 'manual',
    `batchLimit` INT NOT NULL DEFAULT 0,
    `nbAdded` INT NOT NULL DEFAULT 0,
    `nbUpdated` INT NOT NULL DEFAULT 0,
    `nbDeleted` INT NOT NULL DEFAULT 0,
    `nbErrors` INT NOT NULL DEFAULT 0,
    `status` VARCHAR(24) NOT NULL DEFAULT 'running',
    `message` VARCHAR(255) NOT NULL DEFAULT '',
    `idUser` INT NOT NULL DEFAULT 0,
    `detailsJson` MEDIUMTEXT,
    PRIMARY KEY (`id`),
    KEY `idx_group_started` (`idGroup`, `startedAtTs`),
    KEY `idx_group_status` (`idGroup`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- ebay_location_catalog
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ebay_location_catalog` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `idGroup` INT NOT NULL,
    `merchantLocationKey` VARCHAR(128) NOT NULL,
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `city` VARCHAR(128) NOT NULL DEFAULT '',
    `postalCode` VARCHAR(32) NOT NULL DEFAULT '',
    `country` VARCHAR(8) NOT NULL DEFAULT '',
    `status` VARCHAR(32) NOT NULL DEFAULT '',
    `rawJson` MEDIUMTEXT,
    `syncedAtTs` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_group_location_key` (`idGroup`, `merchantLocationKey`),
    KEY `idx_group_name` (`idGroup`, `name`),
    KEY `idx_synced` (`syncedAtTs`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- ebay_reconcile_run
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ebay_reconcile_run` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `idGroup` INT NOT NULL,
    `startedAtTs` INT NOT NULL DEFAULT 0,
    `endedAtTs` INT NOT NULL DEFAULT 0,
    `triggerSource` VARCHAR(32) NOT NULL DEFAULT 'manual',
    `status` VARCHAR(24) NOT NULL DEFAULT 'running',
    `message` VARCHAR(255) NOT NULL DEFAULT '',
    `idUser` INT NOT NULL DEFAULT 0,
    `nbItems` INT NOT NULL DEFAULT 0,
    `nbPending` INT NOT NULL DEFAULT 0,
    `nbApplied` INT NOT NULL DEFAULT 0,
    `nbIgnored` INT NOT NULL DEFAULT 0,
    `nbErrors` INT NOT NULL DEFAULT 0,
    `statsJson` MEDIUMTEXT,
    PRIMARY KEY (`id`),
    KEY `idx_group_started` (`idGroup`, `startedAtTs`),
    KEY `idx_group_status` (`idGroup`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------
-- ebay_reconcile_item
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ebay_reconcile_item` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `idRun` INT NOT NULL,
    `idGroup` INT NOT NULL,
    `discrepancyType` VARCHAR(32) NOT NULL DEFAULT '',
    `actionSuggested` VARCHAR(24) NOT NULL DEFAULT '',
    `actionSelected` VARCHAR(24) NOT NULL DEFAULT '',
    `status` VARCHAR(24) NOT NULL DEFAULT 'pending',
    `message` VARCHAR(255) NOT NULL DEFAULT '',
    `localBookId` INT NOT NULL DEFAULT 0,
    `localEbayItemId` VARCHAR(64) NOT NULL DEFAULT '',
    `remoteSku` VARCHAR(64) NOT NULL DEFAULT '',
    `remoteOfferId` VARCHAR(64) NOT NULL DEFAULT '',
    `remoteListingId` VARCHAR(64) NOT NULL DEFAULT '',
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `author` VARCHAR(255) NOT NULL DEFAULT '',
    `priceLocal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `priceRemote` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `quantityLocal` INT NOT NULL DEFAULT 0,
    `quantityRemote` INT NOT NULL DEFAULT 0,
    `detailsJson` MEDIUMTEXT,
    `createdAtTs` INT NOT NULL DEFAULT 0,
    `updatedAtTs` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_run_status` (`idRun`, `status`),
    KEY `idx_group_status` (`idGroup`, `status`),
    KEY `idx_local_book` (`idGroup`, `localBookId`),
    KEY `idx_remote_listing` (`idGroup`, `remoteListingId`),
    KEY `idx_remote_sku` (`idGroup`, `remoteSku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
