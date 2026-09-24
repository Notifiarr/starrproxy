<?php

//-- RESET THE LIST
$q = [];

//-- ALWAYS NEED TO BUMP THE MIGRATION ID
$q[] = "UPDATE " . SETTINGS_TABLE . "
        SET value = '009'
        WHERE name = 'migration'";

$q[] = "ALTER TABLE " . APPS_TABLE . " ADD signalr INTEGER NOT NULL DEFAULT 0";

foreach ($q as $query) {
    logger(MIGRATION_LOG, ['text' => '<span class="text-success">[Q]</span> ' . preg_replace('!\s+!', ' ', $query)]);

    $proxyDb->query($query);

    if ($proxyDb->error() != 'not an error') {
        logger(MIGRATION_LOG, ['text' => '<span class="text-info">[R]</span> ' . $proxyDb->error()]);
    } else {
        logger(MIGRATION_LOG, ['text' => '<span class="text-info">[R]</span> query applied!']);
    }
}
