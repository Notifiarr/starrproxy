<?php

/*
----------------------------------
 ------  Created: 081425   ------
 ------  Austin Best	   ------
----------------------------------
*/

//-- RESET THE LIST
$q = [];

$q[] = "ALTER TABLE " . APPS_TABLE . "
        ADD redactions TEXT NULL";

$settings = [
        'redactionFields' => 'nzbInfoUrl,downloadUrl,guid,downloadClient,indexer,downloadClientName,torrentInfoHash,apikey,protocol'
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
        SET value = '007'
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

//-- NEEDED SINCE WE ADD A NEW FIELD TO THIS TABLE
$proxyDb->cache->bust(APPS_TABLE_CACHE_KEY);
