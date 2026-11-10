<?php

/*
----------------------------------
 ------  Created: 111824   ------
 ------  Austin Best	   ------
----------------------------------
*/

echo date('c') . ' startup.php ->' . "\n";

error_reporting(E_ERROR | E_PARSE);

if (!defined('ABSOLUTE_PATH')) {
	define('ABSOLUTE_PATH', __DIR__ . '/');
}

//-- Migrations only run here on container start, not on every UI request
define('IS_STARTUP', true);

echo 'require_once ' . ABSOLUTE_PATH . 'loader.php' . "\n";
require_once ABSOLUTE_PATH . 'loader.php';

$command = 'memcached -u abc > /dev/null 2>&1 &';

echo date('c') . ' starting memcached \'' . $command . '\'' . "\n";
$shell->exec($command);

echo date('c') . ' startup.php <-' . "\n";
