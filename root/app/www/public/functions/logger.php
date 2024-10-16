<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

function logger($logfile, $apikey = '', $endpoint = '', $proxyCode = 200, $starrCode = 0, $starrRequest = [])
{
    if (!$logfile) {
        return;
    }

    //-- ROTATE IT DAILY
    if (file_exists($logfile) && date('Ymd') != date('Ymd', filemtime($logfile))) {
        rename($logfile, str_replace('.log', '_' . date('Ymd') . '.log', $logfile));
    }

    $log = date('c') . ' ua:' . $_SERVER['HTTP_USER_AGENT'];
    if ($apikey) {
        $log .= '; key:' . truncateMiddle($apikey, 20);
    }
    if ($endpoint) {
        $log .= '; endpoint:' . $endpoint;
    }

    $log .= '; method:' . strtolower($_SERVER['REQUEST_METHOD']);
    $log .= '; proxyCode:' . $proxyCode;

    if ($starrCode) {
        $log .= '; starrCode:' . $starrCode;

        if ($starrCode != 200) {
            $log .= '; starrResponse:' . json_encode($starrRequest);
        }
    }

    file_put_contents($logfile, $log . "\n", FILE_APPEND);
}
