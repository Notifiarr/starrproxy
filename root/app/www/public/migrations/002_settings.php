<?php

/*
----------------------------------
 ------  Created: 110924   ------
 ------  Austin Best	   ------
----------------------------------
*/

//-- RESET THE LIST
$q = [];

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET value = '002'
        WHERE name = 'migration'";

$settings   = [
                'backupRetentionLength' => $BACKUP_AGE,
                'logRotationSize'       => $LOG_ROTATE_SIZE,
                'logRetentionLength'    => $LOG_AGE,
            ];

$settingRows = [];
foreach ($settings as $key => $val) {
    $settingRows[] = "('" . $key . "', '" . $val . "')";
}

$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`) 
        VALUES " . implode(', ', $settingRows);

foreach ($q as $query) {
	logger(MIGRATION_LOG, '<span class="text-success">[Q]</span> ' . preg_replace('!\s+!', ' ', $query));

    $proxyDb->query($query);

	if ($proxyDb->error() != 'not an error') {
		logger(MIGRATION_LOG, '<span class="text-info">[R]</span> ' . $proxyDb->error(), 'error');
	} else {
		logger(MIGRATION_LOG, '<span class="text-info">[R]</span> query applied!');
	}
}
