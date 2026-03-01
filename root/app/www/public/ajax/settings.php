<?php

/*
----------------------------------
 ------  Created: 110924   ------
 ------  Austin Best	   ------
----------------------------------
*/

error_reporting(E_ERROR | E_PARSE);

if (!$_SESSION) {
    session_start();
}

if (!$_SESSION['IN_UI']) {
    exit('Invalid session, refresh the page');
}

require '../loader.php';

if ($_POST['m'] == 'saveSettings') {
    $newSettings = [];
    foreach ($_POST as $key => $val) {
        if (str_equals_any($key, ['m', 'apikey'])) {
            continue;
        }

        if ($key == 'redactionFields') {
            $val = str_replace("\n", ',', $val);
        }

        $newSettings[$key] = $val;
    }

    $proxyDb->setSettings($newSettings, $settingsTable);

    if ($_POST['apikey'] && $_POST['apikey'] != APP_APIKEY) {
        file_put_contents(APP_APIKEY_FILE, $_POST['apikey']);
    }
}

if ($_POST['m'] == 'bustCache') {
    $cache->bust($_POST['key']);
}

if ($_POST['m'] == 'updateSetting') {
    $proxyDb->setSetting($_POST['setting'], $_POST['value']);
}
