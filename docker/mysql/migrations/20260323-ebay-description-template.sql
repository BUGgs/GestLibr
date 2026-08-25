-- Production migration: eBay description template toggle on book + group header/footer settings

SET @db_name := DATABASE();

SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'book'
      AND COLUMN_NAME = 'ebayUseDescriptionTemplate'
);
SET @sql := IF(
    @col_exists = 0,
    "ALTER TABLE `book` ADD COLUMN `ebayUseDescriptionTemplate` TINYINT(1) NOT NULL DEFAULT 0 AFTER `onEbay`",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `book`
SET `ebayUseDescriptionTemplate` = 0
WHERE `ebayUseDescriptionTemplate` IS NULL;

-- Existing rows stay OFF (0), default for new rows is ON (1).
ALTER TABLE `book` MODIFY `ebayUseDescriptionTemplate` TINYINT(1) NOT NULL DEFAULT 1;
