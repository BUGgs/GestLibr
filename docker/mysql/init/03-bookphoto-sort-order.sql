-- bookphoto sort order support for modern gallery

SET @db_name := DATABASE();

-- Add sortOrder column if missing
SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'bookphoto'
      AND COLUMN_NAME = 'sortOrder'
);
SET @sql := IF(
    @col_exists = 0,
    'ALTER TABLE `bookphoto` ADD COLUMN `sortOrder` INT NOT NULL DEFAULT 0 AFTER `idGroup`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for ordered retrieval if missing
SET @idx_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'bookphoto'
      AND INDEX_NAME = 'idx_bookphoto_order'
);
SET @sql := IF(
    @idx_exists = 0,
    'ALTER TABLE `bookphoto` ADD INDEX `idx_bookphoto_order` (`idGroup`, `idBook`, `sortOrder`, `idBookPhoto`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Backfill sortOrder for existing rows using stable historical order
SET @grp := '';
SET @rn := 0;
UPDATE `bookphoto` bp
INNER JOIN (
    SELECT
        p.`idBookPhoto`,
        (@rn := IF(@grp = CONCAT(p.`idGroup`, '-', p.`idBook`), @rn + 1, 1)) AS newSortOrder,
        (@grp := CONCAT(p.`idGroup`, '-', p.`idBook`)) AS grpMarker
    FROM `bookphoto` p
    ORDER BY p.`idGroup` ASC, p.`idBook` ASC, p.`date` ASC, p.`idBookPhoto` ASC
) ordered ON ordered.`idBookPhoto` = bp.`idBookPhoto`
SET bp.`sortOrder` = ordered.newSortOrder
WHERE bp.`sortOrder` IS NULL OR bp.`sortOrder` = 0;
