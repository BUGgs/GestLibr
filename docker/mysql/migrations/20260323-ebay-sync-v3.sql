-- ------------------------------------------------------------
-- eBay sync v3: dry-run + stock application tracking + action log
-- ------------------------------------------------------------

SET @schema_name := DATABASE();

-- ebay_sync_run.isDryRun
SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'ebay_sync_run'
      AND COLUMN_NAME = 'isDryRun'
);
SET @sql := IF(
    @col_exists = 0,
    "ALTER TABLE `ebay_sync_run` ADD COLUMN `isDryRun` TINYINT(1) NOT NULL DEFAULT 0 AFTER `batchLimit`",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ebay_order_item.stockAppliedQty
SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'ebay_order_item'
      AND COLUMN_NAME = 'stockAppliedQty'
);
SET @sql := IF(
    @col_exists = 0,
    "ALTER TABLE `ebay_order_item` ADD COLUMN `stockAppliedQty` INT NOT NULL DEFAULT 0 AFTER `quantity`",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ebay_order_item.stockAppliedAtTs
SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'ebay_order_item'
      AND COLUMN_NAME = 'stockAppliedAtTs'
);
SET @sql := IF(
    @col_exists = 0,
    "ALTER TABLE `ebay_order_item` ADD COLUMN `stockAppliedAtTs` INT NOT NULL DEFAULT 0 AFTER `stockAppliedQty`",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ebay_order_item.stockAppliedRunId
SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'ebay_order_item'
      AND COLUMN_NAME = 'stockAppliedRunId'
);
SET @sql := IF(
    @col_exists = 0,
    "ALTER TABLE `ebay_order_item` ADD COLUMN `stockAppliedRunId` INT NOT NULL DEFAULT 0 AFTER `stockAppliedAtTs`",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'ebay_order_item'
      AND INDEX_NAME = 'idx_group_stock_applied'
);
SET @sql := IF(
    @idx_exists = 0,
    "ALTER TABLE `ebay_order_item` ADD KEY `idx_group_stock_applied` (`idGroup`,`stockAppliedQty`,`quantity`)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Detailed action log for each sync run.
CREATE TABLE IF NOT EXISTS `ebay_sync_action` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `idRun` INT NOT NULL,
    `idGroup` INT NOT NULL,
    `actionType` VARCHAR(32) NOT NULL DEFAULT '',
    `idBook` INT NOT NULL DEFAULT 0,
    `ebayOrderId` VARCHAR(80) NOT NULL DEFAULT '',
    `lineItemId` VARCHAR(120) NOT NULL DEFAULT '',
    `ebayItemIdBefore` VARCHAR(64) NOT NULL DEFAULT '',
    `ebayItemIdAfter` VARCHAR(64) NOT NULL DEFAULT '',
    `qtyBefore` INT NOT NULL DEFAULT 0,
    `qtyDelta` INT NOT NULL DEFAULT 0,
    `qtyAfter` INT NOT NULL DEFAULT 0,
    `isDryRun` TINYINT(1) NOT NULL DEFAULT 0,
    `status` VARCHAR(24) NOT NULL DEFAULT '',
    `message` VARCHAR(255) NOT NULL DEFAULT '',
    `detailsJson` MEDIUMTEXT,
    `createdAtTs` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_run` (`idRun`),
    KEY `idx_group_action` (`idGroup`,`actionType`),
    KEY `idx_group_book` (`idGroup`,`idBook`),
    KEY `idx_group_order_line` (`idGroup`,`ebayOrderId`,`lineItemId`),
    KEY `idx_group_dry_status` (`idGroup`,`isDryRun`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Cutover: prevent decrement on historical validated lines already present.
UPDATE `ebay_order_item` i
INNER JOIN `ebay_order` o
    ON o.`idGroup` = i.`idGroup`
   AND o.`ebayOrderId` = i.`ebayOrderId`
SET i.`stockAppliedQty` = i.`quantity`,
    i.`stockAppliedAtTs` = UNIX_TIMESTAMP(),
    i.`stockAppliedRunId` = 0
WHERE o.`isValidated` = 1
  AND i.`quantity` > i.`stockAppliedQty`;
