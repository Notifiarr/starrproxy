<?php

/*
----------------------------------
 ------  Created: 110424   ------
 ------  Austin Best	   ------
----------------------------------
*/

function loadClassExtras($class)
{
    $extras = ['interfaces', 'traits'];

    foreach ($extras as $extraDir) {
        if (file_exists(ABSOLUTE_PATH . 'classes/' . $extraDir . '/' . $class . '.php')) {
            require ABSOLUTE_PATH . 'classes/' . $extraDir . '/' . $class . '.php';
        } else {
            $extraFolder = ABSOLUTE_PATH . 'classes/' . $extraDir . '/' . $class . '/';

            if (is_dir($extraFolder)) {
                $openExtraDir = opendir($extraFolder);
                while ($extraFile = readdir($openExtraDir)) {
                    if (str_contains($extraFile, '.php')) {
                        require $extraFolder . $extraFile;
                    }
                }
                closedir($openExtraDir);
            }
        }
    }
}

function automation()
{
    $shell = $shell ?? new Shell();

    //-- CREATE DIRECTORIES
    $createFolders = [LOGS_PATH, LOGS_PATH . 'system/', LOGS_PATH . 'notifications/', BACKUP_PATH, APP_USER_TEMPLATES_PATH, DATABASE_PATH, MIGRATIONS_PATH];
    foreach ($createFolders as $createFolder) {
        if (!is_dir($createFolder)) {
            createDirectoryTree($createFolder);
        }
    }
    foreach (StarrApps::LIST as $starrApp) {
        if (!is_dir(APP_USER_TEMPLATES_PATH . $starrApp)) {
            createDirectoryTree(APP_USER_TEMPLATES_PATH . $starrApp);
        }
        if (!is_dir(ABSOLUTE_PATH . 'templates/' . $starrApp)) {
            createDirectoryTree(ABSOLUTE_PATH . 'templates/' . $starrApp);
        }
    }
}

function createDirectoryTree($tree)
{
    $shell = $shell ?? new Shell();
    $shell->exec('mkdir -p ' . $tree);
}

function extractCookies($string)
{
    $lines = explode(PHP_EOL, $string);

    foreach ($lines as $line) {
        if (substr($line, 0, 10) == '#HttpOnly_') {
            $line               = substr($line, 10);
            $cookie['httponly'] = true;
        } else {
            $cookie['httponly'] = false;
        }

        // we only care for valid cookie def lines
        if (strlen($line) > 0 && $line[0] != '#' && substr_count($line, "\t") == 6) {
            $tokens = explode("\t", $line);
            $tokens = array_map('trim', $tokens);
            $cookie = [
                'domain'           => $tokens[0],
                'flag'             => $tokens[1],
                'path'             => $tokens[2],
                'secure'           => $tokens[3],
                'expiration-epoch' => $tokens[4],
                'name'             => urldecode($tokens[5]),
                'value'            => urldecode($tokens[6]),
                'expiration'       => date('Y-m-d h:i:s', $tokens[4])
            ];

            $cookies[] = $cookie;
        }
    }

    return $cookies ?: [];
}

function getThemes()
{
    $themes     = [];
    $themesPath = ABSOLUTE_PATH . 'themes/';

    if (!is_dir($themesPath)) {
        return $themes;
    }

    $dir = opendir($themesPath);
    while ($file = readdir($dir)) {
        if (str_contains($file, '.min.css')) {
            $themes[] = str_replace('.min.css', '', $file);
        }
    }
    closedir($dir);
    sort($themes);

    return $themes;
}
