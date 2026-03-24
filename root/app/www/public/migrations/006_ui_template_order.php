<?php

/*
----------------------------------
 ------  Created: 111424   ------
 ------  Austin Best	   ------
----------------------------------
*/

//-- RESET THE LIST
$q = [];

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET value = '006'
        WHERE name = 'migration'";

$settings = [
    'templateOrder' => 1
];

$settingRows = [];
foreach ($settings as $key => $val) {
    $settingRows[] = "('" . $key . "', '" . $val . "')";
}

$q[] = "INSERT INTO " . SETTINGS_TABLE . "
        (`name`, `value`)
        VALUES " . implode(', ', $settingRows);

foreach ($q as $query) {
    logger(MIGRATION_LOG, ['text' => '<span class="text-success">[Q]</span> ' . preg_replace('!\s+!', ' ', $query)]);

    $proxyDb->query($query);

    if ($proxyDb->error() != 'not an error') {
        logger(MIGRATION_LOG, ['text' => '<span class="text-info">[R]</span> ' . $proxyDb->error()]);
    } else {
        logger(MIGRATION_LOG, ['text' => '<span class="text-info">[R]</span> query applied!']);
    }
}
