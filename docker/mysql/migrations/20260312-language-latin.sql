-- Add "Latin" language option for all existing groups (idempotent)
CREATE TABLE IF NOT EXISTS `groups` (
    `idGroup` INT NOT NULL,
    `groupName` VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (`idGroup`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `language` (
    `idLanguage` INT NOT NULL AUTO_INCREMENT,
    `languageName` VARCHAR(100) NOT NULL,
    `idGroup` INT NOT NULL DEFAULT 1,
    PRIMARY KEY (`idLanguage`),
    KEY `idx_language_group` (`idGroup`, `languageName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `groups` (`idGroup`, `groupName`)
SELECT 1, 'Default'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `groups` WHERE `idGroup`=1);

INSERT INTO `language` (`languageName`, `idGroup`)
SELECT 'Latin', src.`idGroup`
FROM (
    SELECT DISTINCT `idGroup` FROM `language` WHERE `idGroup` > 0
    UNION
    SELECT DISTINCT `idGroup` FROM `groups` WHERE `idGroup` > 0
) AS src
LEFT JOIN `language` l
    ON l.`idGroup` = src.`idGroup`
   AND LOWER(TRIM(l.`languageName`)) = 'latin'
WHERE l.`idLanguage` IS NULL;
