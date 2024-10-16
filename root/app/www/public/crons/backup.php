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
mkdir($backupFolder);
copy(APP_SETTINGS_FILE, $backupFolder . basename(APP_SETTINGS_FILE));
copy(APP_USAGE_FILE, $backupFolder . basename(APP_USAGE_FILE));
