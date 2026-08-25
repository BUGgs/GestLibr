-- Init migration: login reCAPTCHA settings stored in config table (idempotent)

CREATE TABLE IF NOT EXISTS `config` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL DEFAULT '',
    `description` MEDIUMTEXT NOT NULL,
    `value` MEDIUMTEXT NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_config_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `config` (`name`, `description`, `value`)
SELECT 'login_recaptcha_enabled', 'Active la protection reCAPTCHA v3 (score) sur le formulaire de connexion (0/1)', '0'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `config` WHERE `name`='login_recaptcha_enabled'
);

INSERT INTO `config` (`name`, `description`, `value`)
SELECT 'login_recaptcha_site_key', 'Clé publique reCAPTCHA v3 utilisée sur le formulaire de connexion', ''
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `config` WHERE `name`='login_recaptcha_site_key'
);

INSERT INTO `config` (`name`, `description`, `value`)
SELECT 'login_recaptcha_secret_key', 'Clé secrète reCAPTCHA v3 utilisée pour valider les connexions', ''
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `config` WHERE `name`='login_recaptcha_secret_key'
);

UPDATE `config` SET `description`='Active la protection reCAPTCHA v3 (score) sur le formulaire de connexion (0/1)' WHERE `name`='login_recaptcha_enabled';
UPDATE `config` SET `description`='Clé publique reCAPTCHA v3 utilisée sur le formulaire de connexion' WHERE `name`='login_recaptcha_site_key';
UPDATE `config` SET `description`='Clé secrète reCAPTCHA v3 utilisée pour valider les connexions' WHERE `name`='login_recaptcha_secret_key';
