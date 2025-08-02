<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

if (!defined('ABSOLUTE_PATH')) {
    if (file_exists('/config/www/loader.php')) {
        define('ABSOLUTE_PATH', '/config/www/');
    } elseif (file_exists('/app/www/public/loader.php')) {
        define('ABSOLUTE_PATH', '/app/www/public/');
    } else {
        if (file_exists('loader.php')) {
            define('ABSOLUTE_PATH', './');
        } elseif (file_exists('../loader.php')) {
            define('ABSOLUTE_PATH', '../');
        } elseif (file_exists('../../loader.php')) {
            define('ABSOLUTE_PATH', '../../');
        }
    }
}

require ABSOLUTE_PATH . 'loader.php';

$backupFolder = BACKUP_PATH . date('Y-m-d') . '/';
$shell->exec('mkdir -p ' . $backupFolder);

$proxyBackupError = $proxyDb->backup();
if ($proxyBackupError) {
    echo date('c') . '[ERROR] Backup of main database failed: ' . $proxyBackupError . "\n";
}
$usageBackupError = $usageDb->backup();
if ($usageBackupError) {
    echo date('c') . '[ERROR] Backup of usage database failed: ' . $usageBackupError . "\n";
}

copy(APP_APIKEY_FILE, $backupFolder . basename(APP_APIKEY_FILE));
