<?php

/*
----------------------------------
 ------  Created: 022826   ------
 ------  Austin Best       ------
----------------------------------
*/

//-- RESET THE LIST
$q = [];

$settings = [
                'defaultTheme' => 'nzblack'
            ];

$settingRows = [];
foreach ($settings as $key => $val) {
    $settingRows[] = "('" . $key . "', '" . $val . "')";
}

$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`) 
        VALUES " . implode(', ', $settingRows);

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET value = '008'
        WHERE name = 'migration'";

foreach ($q as $query) {
    logger(MIGRATION_LOG, ['text' => '<span class="text-success">[Q]</span> ' . preg_replace('!\s+!', ' ', $query)]);

    $proxyDb->query($query);

    if ($proxyDb->error() != 'not an error') {
        logger(MIGRATION_LOG, ['text' => '<span class="text-info">[R]</span> ' . $proxyDb->error()]);
    } else {
        logger(MIGRATION_LOG, ['text' => '<span class="text-info">[R]</span> query applied!']);
    }
}
