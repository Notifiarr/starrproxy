<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('APP_NAME', 'Starr Proxy');
define('APP_API_ERROR', APP_NAME . ': %s');
define('APP_URL', ($_SERVER['REQUEST_SCHEME'] ?: 'http') . '://' . $_SERVER['HTTP_HOST']);
define('APP_X', 0);
define('APP_Y', 1);

define('STARR_BACKUP_AGE', 2); //-- DELETE AFTER THIS AMOUNT OF DAYS

//-- DATABASE
define('PROXY_DATABASE_NAME', 'starrproxy.db');
define('USAGE_DATABASE_NAME', 'usage.db');
define('SETTINGS_TABLE', 'settings');
define('STARRS_TABLE', 'starrs');
define('APPS_TABLE', 'apps');
define('NOTIFICATION_PLATFORM_TABLE', 'notification_platform');
define('NOTIFICATION_TRIGGER_TABLE', 'notification_trigger');
define('NOTIFICATION_LINK_TABLE', 'notification_link');
define('USAGE_TABLE', 'usage');

//-- FOLDERS
define('APP_DATA_PATH', '/config/');
define('LOGS_PATH', APP_DATA_PATH . 'logs/');
define('BACKUP_PATH', APP_DATA_PATH . 'backups/');
define('APP_USER_TEMPLATES_PATH', APP_DATA_PATH . 'templates/');
define('DATABASE_PATH', APP_DATA_PATH . 'database/');
define('MIGRATIONS_PATH', ABSOLUTE_PATH . 'migrations/');

//-- FILES
define('APP_APIKEY_FILE', APP_DATA_PATH . 'key');
define('MIGRATION_FILE', APP_DATA_PATH . 'migration-in-progress.txt');
define('IS_MIGRATION_RUNNING', (file_exists(MIGRATION_FILE) ? true : false));

//-- LOG FILES
define('SYSTEM_LOG', LOGS_PATH . 'system/app.log');
define('MIGRATION_LOG', LOGS_PATH . 'system/migrations.log');
define('CRON_HOUSEKEEPER_LOG', LOGS_PATH . 'system/cron-housekeeper.log');
define('LOG_LINES_PER_PAGE', 1000);

//-- CACHE
define('REQUEST_COUNTER_KEY', 'requests');
define('REQUEST_COUNTER_TIME', 86400); //-- 1 DAY
define('STARRS_TABLE_CACHE_KEY', 'starr_table');
define('STARRS_TABLE_CACHE_TIME', 86400); //-- 1 DAY
define('APPS_TABLE_CACHE_KEY', 'apps_table');
define('APPS_TABLE_CACHE_TIME', 86400); //-- 1 DAY
define('STARR_ENDPOINT_LIST_KEY', 'endpoints_%s'); //-- _starrApp
define('STARR_ENDPOINT_LIST_TIME', 604800); //-- 1 WEEK

//-- MISC
define('REDACTION_VALUE', '{PROXY-REDACTED}');
