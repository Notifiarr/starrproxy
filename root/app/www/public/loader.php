<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

error_reporting(E_ERROR | E_PARSE);

if (!defined('ABSOLUTE_PATH')) {
    if (file_exists('loader.php')) {
        define('ABSOLUTE_PATH', './');
    }
    if (file_exists('../loader.php')) {
        define('ABSOLUTE_PATH', '../');
    }
    if (file_exists('../../loader.php')) {
        define('ABSOLUTE_PATH', '../../');
    }
}

//-- USED FOR MIGRATIONS, DEFINES SET BELOW
$LOG_ROTATE_SIZE    = 2; //-- MB UNTIL ROTATE
$LOG_AGE            = 2; //-- DELETE AFTER THIS AMOUNT OF DAYS
$BACKUP_AGE         = 7; //-- DELETE AFTER THIS AMOUNT OF DAYS

//-- SETUP SOME SHARED VARIABLES
$page       = $_GET['page'] ?: $_POST['page'];
$app        = $_GET['app'] ?: $_POST['app'];
$appLabel   = ucfirst($app);

//-- DIRECTORIES TO LOAD FILES FROM, ORDER IS IMPORTANT
$autoloads          = ['includes', 'functions', 'functions/helpers', 'classes'];
$ignoreAutoloads    = ['header.php', 'footer.php'];

foreach ($autoloads as $autoload) {
    $dir = ABSOLUTE_PATH . $autoload;

    if (is_dir($dir)) {
        $handle = opendir($dir);
        while ($file = readdir($handle)) {
            if ($file[0] != '.' && !is_dir($dir . '/' . $file) && !in_array($file, $ignoreAutoloads)) {
                require $dir . '/' . $file;
            }
        }
        closedir($handle);
    }
}

//-- INITIALIZE THE CACHE CLASS
$cache = new Cache();

//-- INITIALIZE THE SHELL CLASS
$shell = new Shell();

//-- INITIALIZE THE STARR CLASS
$starr = new Starr();

//-- CREATE NEEDED FOLDERS
automation();

//-- INITIALIZE THE DATABASE CLASS
$proxyDb = new Database(PROXY_DATABASE_NAME);
if ($_SESSION['IN_UI']) {
    $proxyDb->migrations();
}
$usageDb = new Database(USAGE_DATABASE_NAME);

//-- INITIALIZE THE NOTIFICATION CLASS
$notifications = new Notifications();

//-- CREATE APIKEY IF MISSING
if (!file_exists(APP_APIKEY_FILE)) {
    $key = generateApikey();
    file_put_contents(APP_APIKEY_FILE, $key);
}
define('APP_APIKEY', file_get_contents(APP_APIKEY_FILE));

//-- LOAD THE TABLES
$starrsTable    = $proxyDb->getStarrsTable();
$appsTable      = $proxyDb->getAppsTable();
$settingsTable  = $proxyDb->getSettings();
$usageTable     = $usageDb->getUsageTable();

//-- SOMETIMES THE TABLE IS BUSY, RETRY
if (!$usageTable && ($app || !$page || $page == 'home')) {
    sleep(1);
    $usageTable = $usageDb->getUsageTable();
}

//-- CONSTANTS BASED ON DATABASE SETTINGS
define('LOG_ROTATE_SIZE', $settingsTable['logRotationSize'] ?: $LOG_ROTATE_SIZE);
define('LOG_AGE', $settingsTable['logRetentionLength'] ?: $LOG_AGE);
define('BACKUP_AGE', $settingsTable['backupRetentionLength'] ?: $BACKUP_AGE);
define('USER_THEME', $settingsTable['defaultTheme'] && file_exists('themes/' . $settingsTable['defaultTheme'] . '.min.css') ? $settingsTable['defaultTheme'] : 'nzblack');
define('USER_THEME_MODE', $settingsTable['defaultThemeMode'] ?: 'dark');

define('TEMPLATE_ORDER', $settingsTable['templateOrder'] ?: 1);
$themes = getThemes();
