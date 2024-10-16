<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

define('APP_NAME', 'Starr Proxy');
define('APP_DATA_PATH', '/config/');
define('APP_LOG_PATH', APP_DATA_PATH . 'logs/');
define('APP_BACKUP_PATH', APP_DATA_PATH . 'backups/');
define('APP_SETTINGS_FILE', APP_DATA_PATH . 'settings.json');
define('APP_USAGE_FILE', APP_DATA_PATH . 'usage.json');
define('APP_API_ERROR', APP_NAME .': %s');

define('LOG_AGE', 2); //-- DELETE AFTER THIS AMOUNT OF DAYS
define('BACKUP_AGE', 7); //-- DELETE AFTER THIS AMOUNT OF DAYS

$starrApps = ['lidarr', 'radarr', 'readarr', 'sonarr', 'whisparr'];
