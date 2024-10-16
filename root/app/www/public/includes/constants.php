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
define('APP_SETTINGS_FILE', APP_DATA_PATH . 'settings.json');
define('APP_API_ERROR', APP_NAME .': %s');

$starrApps = ['lidarr', 'radarr', 'readarr', 'sonarr', 'whisparr'];
