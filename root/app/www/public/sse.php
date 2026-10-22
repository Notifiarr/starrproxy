<?php

/*
----------------------------------
 ------  Created: 110826   ------
 ------  Joly0             ------
----------------------------------
*/

//-- Answers once and exits like dockwatch, the browser's EventSource reconnects after `retry` ms so no php worker stays busy
//-- ?events=a,b picks the events, an event is only sent when its html changed since the Last-Event-ID the browser sends back

error_reporting(E_ERROR | E_PARSE);

if (!$_SESSION) {
    session_start();
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

if (!$_SESSION['IN_UI']) {
    //-- EventSource stops reconnecting after a non 200 response
    http_response_code(401);
    exit;
}

require 'loader.php';

//-- Only logs the log list knows about, the path ends up in shell commands
$log = '';
if ($_GET['log']) {
    $logList = getLogs();
    array_walk_recursive($logList, function ($path) use (&$log) {
        if ($path === $_GET['log']) {
            $log = $path;
        }
    });
}

$events = ['logList' => fn() => renderLogList()];

if ($log) {
    $events['log']    = fn() => getLog($log, max(1, intval($_GET['page'])));
    $events['appLog'] = fn() => getLog($log, 1, true);
}

$lastIds = json_decode($_SERVER['HTTP_LAST_EVENT_ID'] ?? '', true) ?: [];
$ids     = [];
$changed = [];

foreach (array_intersect(explode(',', $_GET['events'] ?? ''), array_keys($events)) as $event) {
    ob_start();
    $events[$event]();
    $changed[$event] = ob_get_clean();
    $ids[$event]     = hash('crc32b', $changed[$event]);

    if ($ids[$event] === ($lastIds[$event] ?? '')) {
        unset($changed[$event]);
    }
}

echo "retry: 3000\n\n";

foreach ($changed as $event => $html) {
    echo 'id: ' . json_encode($ids) . "\nevent: " . $event . "\ndata: " . json_encode($html) . "\n\n";
}
