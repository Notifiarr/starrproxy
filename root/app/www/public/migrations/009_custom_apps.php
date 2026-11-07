<?php

/*
----------------------------------
 ------  Created: 022426   ------
 ------  Custom Apps        ------
----------------------------------
*/

//-- RESET THE LIST
$q = [];

$q[] = "CREATE TABLE IF NOT EXISTS custom_apps (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL UNIQUE,
        label TEXT NOT NULL,
        api_version TEXT NOT NULL DEFAULT 'v1',
        openapi_url TEXT NULL,
        icon TEXT NULL
        )";

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET value = '009'
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