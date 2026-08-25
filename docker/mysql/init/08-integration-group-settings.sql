-- Init migration: multi-group integration settings + eBay global keyset config (idempotent)

SET @db_name := DATABASE();

CREATE TABLE IF NOT EXISTS `integration_group_settings` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `idGroup` INT NOT NULL,
    `integration` VARCHAR(64) NOT NULL,
    `settingKey` VARCHAR(128) NOT NULL,
    `settingValue` MEDIUMTEXT NOT NULL,
    `isSecret` TINYINT(1) NOT NULL DEFAULT 0,
    `updatedAtTs` INT NOT NULL DEFAULT 0,
    `updatedByUser` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_group_integration_setting` (`idGroup`, `integration`, `settingKey`),
    KEY `idx_integration_group` (`integration`, `idGroup`),
    KEY `idx_group_updated` (`idGroup`, `updatedAtTs`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `config` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `description` MEDIUMTEXT NOT NULL,
    `value` MEDIUMTEXT NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_config_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `config` (`name`, `description`, `value`)
SELECT 'ebay_client_id_sandbox', 'eBay OAuth sandbox client id (keyset global)', ''
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `config` WHERE `name`='ebay_client_id_sandbox');

INSERT INTO `config` (`name`, `description`, `value`)
SELECT 'ebay_client_secret_sandbox', 'eBay OAuth sandbox client secret (keyset global)', ''
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `config` WHERE `name`='ebay_client_secret_sandbox');

INSERT INTO `config` (`name`, `description`, `value`)
SELECT 'ebay_redirect_uri_sandbox', 'eBay OAuth sandbox RuName/redirect URI (keyset global)', ''
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `config` WHERE `name`='ebay_redirect_uri_sandbox');

INSERT INTO `config` (`name`, `description`, `value`)
SELECT 'ebay_client_id_production', 'eBay OAuth production client id (keyset global)', ''
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `config` WHERE `name`='ebay_client_id_production');

INSERT INTO `config` (`name`, `description`, `value`)
SELECT 'ebay_client_secret_production', 'eBay OAuth production client secret (keyset global)', ''
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `config` WHERE `name`='ebay_client_secret_production');

INSERT INTO `config` (`name`, `description`, `value`)
SELECT 'ebay_redirect_uri_production', 'eBay OAuth production RuName/redirect URI (keyset global)', ''
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `config` WHERE `name`='ebay_redirect_uri_production');

SET @has_ebay_group_settings := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ebay_group_settings'
);

SET @sql := IF(
    @has_ebay_group_settings > 0,
    "INSERT INTO `integration_group_settings` (`idGroup`,`integration`,`settingKey`,`settingValue`,`isSecret`,`updatedAtTs`,`updatedByUser`) "
    "SELECT e.`idGroup`, 'ebay', 'marketplace_id', e.`marketplaceId`, 0, IFNULL(e.`updatedAtTs`, UNIX_TIMESTAMP()), 0 "
    "FROM `ebay_group_settings` e WHERE TRIM(IFNULL(e.`marketplaceId`,''))<>'' "
    "ON DUPLICATE KEY UPDATE `settingValue`=VALUES(`settingValue`),`isSecret`=VALUES(`isSecret`),`updatedAtTs`=VALUES(`updatedAtTs`),`updatedByUser`=VALUES(`updatedByUser`)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_ebay_group_settings > 0,
    "INSERT INTO `integration_group_settings` (`idGroup`,`integration`,`settingKey`,`settingValue`,`isSecret`,`updatedAtTs`,`updatedByUser`) "
    "SELECT e.`idGroup`, 'ebay', 'merchant_location_key', e.`merchantLocationKey`, 0, IFNULL(e.`updatedAtTs`, UNIX_TIMESTAMP()), 0 "
    "FROM `ebay_group_settings` e WHERE TRIM(IFNULL(e.`merchantLocationKey`,''))<>'' "
    "ON DUPLICATE KEY UPDATE `settingValue`=VALUES(`settingValue`),`isSecret`=VALUES(`isSecret`),`updatedAtTs`=VALUES(`updatedAtTs`),`updatedByUser`=VALUES(`updatedByUser`)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_ebay_group_settings > 0,
    "INSERT INTO `integration_group_settings` (`idGroup`,`integration`,`settingKey`,`settingValue`,`isSecret`,`updatedAtTs`,`updatedByUser`) "
    "SELECT e.`idGroup`, 'ebay', 'default_payment_policy_id', e.`defaultPaymentPolicyId`, 0, IFNULL(e.`updatedAtTs`, UNIX_TIMESTAMP()), 0 "
    "FROM `ebay_group_settings` e WHERE TRIM(IFNULL(e.`defaultPaymentPolicyId`,''))<>'' "
    "ON DUPLICATE KEY UPDATE `settingValue`=VALUES(`settingValue`),`isSecret`=VALUES(`isSecret`),`updatedAtTs`=VALUES(`updatedAtTs`),`updatedByUser`=VALUES(`updatedByUser`)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_ebay_group_settings > 0,
    "INSERT INTO `integration_group_settings` (`idGroup`,`integration`,`settingKey`,`settingValue`,`isSecret`,`updatedAtTs`,`updatedByUser`) "
    "SELECT e.`idGroup`, 'ebay', 'default_fulfillment_policy_id', e.`defaultFulfillmentPolicyId`, 0, IFNULL(e.`updatedAtTs`, UNIX_TIMESTAMP()), 0 "
    "FROM `ebay_group_settings` e WHERE TRIM(IFNULL(e.`defaultFulfillmentPolicyId`,''))<>'' "
    "ON DUPLICATE KEY UPDATE `settingValue`=VALUES(`settingValue`),`isSecret`=VALUES(`isSecret`),`updatedAtTs`=VALUES(`updatedAtTs`),`updatedByUser`=VALUES(`updatedByUser`)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_ebay_group_settings > 0,
    "INSERT INTO `integration_group_settings` (`idGroup`,`integration`,`settingKey`,`settingValue`,`isSecret`,`updatedAtTs`,`updatedByUser`) "
    "SELECT e.`idGroup`, 'ebay', 'default_return_policy_id', e.`defaultReturnPolicyId`, 0, IFNULL(e.`updatedAtTs`, UNIX_TIMESTAMP()), 0 "
    "FROM `ebay_group_settings` e WHERE TRIM(IFNULL(e.`defaultReturnPolicyId`,''))<>'' "
    "ON DUPLICATE KEY UPDATE `settingValue`=VALUES(`settingValue`),`isSecret`=VALUES(`isSecret`),`updatedAtTs`=VALUES(`updatedAtTs`),`updatedByUser`=VALUES(`updatedByUser`)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `integration_group_settings` (`idGroup`,`integration`,`settingKey`,`settingValue`,`isSecret`,`updatedAtTs`,`updatedByUser`)
SELECT src.`idGroup`, 'ebay', 'fixed_category_id', cfg.`value`, 0, UNIX_TIMESTAMP(), 0
FROM (
    SELECT DISTINCT `idGroup` FROM `groups` WHERE `idGroup` > 0
    UNION
    SELECT DISTINCT `idGroup` FROM `book` WHERE `idGroup` > 0
) src
INNER JOIN `config` cfg ON cfg.`name`='ebay_fixed_category_id' AND TRIM(IFNULL(cfg.`value`,''))<>''
ON DUPLICATE KEY UPDATE
    `settingValue`=VALUES(`settingValue`),
    `isSecret`=VALUES(`isSecret`),
    `updatedAtTs`=VALUES(`updatedAtTs`),
    `updatedByUser`=VALUES(`updatedByUser`);

INSERT INTO `integration_group_settings` (`idGroup`,`integration`,`settingKey`,`settingValue`,`isSecret`,`updatedAtTs`,`updatedByUser`)
SELECT src.`idGroup`, 'amazon_creators', map.`settingKey`, cfg.`value`, map.`isSecret`, UNIX_TIMESTAMP(), 0
FROM (
    SELECT DISTINCT `idGroup` FROM `groups` WHERE `idGroup` > 0
    UNION
    SELECT DISTINCT `idGroup` FROM `book` WHERE `idGroup` > 0
) src
INNER JOIN (
    SELECT 'amazon_creators_credential_id' AS `configName`, 'credential_id' AS `settingKey`, 0 AS `isSecret`
    UNION ALL SELECT 'amazon_creators_credential_secret', 'credential_secret', 1
    UNION ALL SELECT 'amazon_creators_version', 'version', 0
    UNION ALL SELECT 'amazon_creators_partner_tag', 'partner_tag', 0
    UNION ALL SELECT 'amazon_creators_marketplace', 'marketplace', 0
    UNION ALL SELECT 'amazon_creators_language', 'language', 0
    UNION ALL SELECT 'amazon_creators_base_url', 'base_url', 0
    UNION ALL SELECT 'amazon_creators_token_endpoint', 'token_endpoint', 0
    UNION ALL SELECT 'amazon_creators_scope', 'scope', 0
) map ON 1=1
INNER JOIN `config` cfg ON cfg.`name` = map.`configName` AND TRIM(IFNULL(cfg.`value`,''))<>''
ON DUPLICATE KEY UPDATE
    `settingValue`=VALUES(`settingValue`),
    `isSecret`=VALUES(`isSecret`),
    `updatedAtTs`=VALUES(`updatedAtTs`),
    `updatedByUser`=VALUES(`updatedByUser`);

-- NOTE: do not backfill ebay_refresh_token globally to avoid cross-group leakage.
