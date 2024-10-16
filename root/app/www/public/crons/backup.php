<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

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

require ABSOLUTE_PATH . 'loader.php';

$backupFolder = APP_BACKUP_PATH . date('Y-m-d') . '/';
shell_exec('mkdir -p ' . $backupFolder);
copy(APP_SETTINGS_FILE, $backupFolder . basename(APP_SETTINGS_FILE));
copy(APP_USAGE_FILE, $backupFolder . basename(APP_USAGE_FILE));
copy(APP_APIKEY_FILE, $backupFolder . basename(APP_APIKEY_FILE));
