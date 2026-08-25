<?php /********************************/
/**   GestLibr: config.php     **/
/**       Config File          **/
/** Romain DUCHENE, August 2012  **/
/********************************/

//unset($config);

function getEnvValue($name, $defaultValue)
{
    $value = getenv($name);
    if ($value === false || $value === '') {
        return $defaultValue;
    }
    return $value;
}

/* Language */
$config["lang"]="french";
$config["template"]="style2012";

/* SQL Settings */
$config["dbServer"]=getEnvValue("DB_HOST", getEnvValue("MYSQL_HOST", "localhost"));
$config["dbUser"]=getEnvValue("DB_USER", getEnvValue("MYSQL_USER", "gestlibr"));
$config["dbPassword"]=getEnvValue("DB_PASSWORD", getEnvValue("MYSQL_PASSWORD", ""));
$config["dbBase"]=getEnvValue("DB_NAME", getEnvValue("MYSQL_DATABASE", "gestlibr"));

/* Default settings */
$config["maxBookRow"]=20;
$config["maxCatalogDataRow"]=20;

?>
