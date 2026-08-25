-- book subtitle support (idempotent)

SET @db_name := DATABASE();

SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'book'
      AND COLUMN_NAME = 'subtitle'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `book` ADD COLUMN `subtitle` TEXT NOT NULL AFTER `title`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `book`
SET `subtitle`=''
WHERE `subtitle` IS NULL;
