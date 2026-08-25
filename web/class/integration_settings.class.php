<?php

class IntegrationGroupSettings
{
    protected static $tableExistsCache = null;

    protected static function db()
    {
        global $babase;
        if (isset($babase) && $babase instanceof DataBase) {
            return $babase;
        }

        if (!class_exists('DataBase')) {
            return null;
        }

        $db = new DataBase();
        $db->DbConnect();
        return $db;
    }

    protected static function escape($value)
    {
        $db = self::db();
        if ($db === null || !isset($db->DB_CONN)) {
            return addslashes((string) $value);
        }
        return mysqli_real_escape_string($db->DB_CONN, (string) $value);
    }

    protected static function select($sql)
    {
        $db = self::db();
        if ($db === null) {
            return array();
        }
        $rows = $db->DbSelect($sql, $count);
        if ($count <= 0 || !is_array($rows)) {
            return array();
        }
        return $rows;
    }

    protected static function query($sql)
    {
        $db = self::db();
        if ($db === null) {
            return false;
        }
        return $db->DbQuery($sql);
    }

    public static function tableExists()
    {
        if (self::$tableExistsCache !== null) {
            return (bool) self::$tableExistsCache;
        }

        $rows = self::select(
            "SELECT COUNT(*) AS nb FROM INFORMATION_SCHEMA.TABLES "
            . "WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='integration_group_settings'"
        );
        $exists = (!empty($rows[0]['nb']) && (int) $rows[0]['nb'] > 0);
        self::$tableExistsCache = $exists;
        return $exists;
    }

    public static function getGroupSetting($idGroup, $integration, $settingKey, $defaultValue = '')
    {
        $idGroup = (int) $idGroup;
        $integration = trim((string) $integration);
        $settingKey = trim((string) $settingKey);

        if ($idGroup <= 0 || $integration === '' || $settingKey === '' || !self::tableExists()) {
            return $defaultValue;
        }

        $rows = self::select(
            "SELECT `settingValue` FROM `integration_group_settings` "
            . "WHERE `idGroup`='" . $idGroup . "' "
            . "AND `integration`='" . self::escape($integration) . "' "
            . "AND `settingKey`='" . self::escape($settingKey) . "' "
            . "LIMIT 1"
        );
        if (empty($rows[0]) || !array_key_exists('settingValue', $rows[0])) {
            return $defaultValue;
        }

        return (string) $rows[0]['settingValue'];
    }

    public static function getGroupSettings($idGroup, $integration)
    {
        $idGroup = (int) $idGroup;
        $integration = trim((string) $integration);
        if ($idGroup <= 0 || $integration === '' || !self::tableExists()) {
            return array();
        }

        $rows = self::select(
            "SELECT `settingKey`, `settingValue`, `isSecret`, `updatedAtTs`, `updatedByUser` "
            . "FROM `integration_group_settings` "
            . "WHERE `idGroup`='" . $idGroup . "' "
            . "AND `integration`='" . self::escape($integration) . "' "
            . "ORDER BY `settingKey` ASC"
        );

        $result = array();
        $i = 0;
        while ($i < sizeof($rows)) {
            $row = $rows[$i];
            if (!empty($row['settingKey'])) {
                $result[(string) $row['settingKey']] = (string) $row['settingValue'];
            }
            $i++;
        }
        return $result;
    }

    public static function setGroupSetting($idGroup, $integration, $settingKey, $settingValue, $isSecret = 0, $updatedByUser = 0)
    {
        $idGroup = (int) $idGroup;
        $integration = trim((string) $integration);
        $settingKey = trim((string) $settingKey);
        $isSecret = ((int) $isSecret > 0) ? 1 : 0;
        $updatedByUser = (int) $updatedByUser;

        if ($idGroup <= 0 || $integration === '' || $settingKey === '' || !self::tableExists()) {
            return false;
        }

        $now = time();
        return self::query(
            "INSERT INTO `integration_group_settings` (`idGroup`,`integration`,`settingKey`,`settingValue`,`isSecret`,`updatedAtTs`,`updatedByUser`) VALUES ("
            . "'" . $idGroup . "',"
            . "'" . self::escape($integration) . "',"
            . "'" . self::escape($settingKey) . "',"
            . "'" . self::escape((string) $settingValue) . "',"
            . "'" . $isSecret . "',"
            . "'" . $now . "',"
            . "'" . $updatedByUser . "'"
            . ") ON DUPLICATE KEY UPDATE "
            . "`settingValue`=VALUES(`settingValue`),"
            . "`isSecret`=VALUES(`isSecret`),"
            . "`updatedAtTs`=VALUES(`updatedAtTs`),"
            . "`updatedByUser`=VALUES(`updatedByUser`)"
        );
    }

    public static function setGroupSettings($idGroup, $integration, array $values, array $secretSettingKeys = array(), $updatedByUser = 0)
    {
        $count = 0;
        foreach ($values as $settingKey => $settingValue) {
            $isSecret = in_array((string) $settingKey, $secretSettingKeys) ? 1 : 0;
            if (self::setGroupSetting($idGroup, $integration, (string) $settingKey, (string) $settingValue, $isSecret, $updatedByUser)) {
                $count++;
            }
        }
        return $count;
    }
}
