<?php

/*
----------------------------------
 ------  Created: 110924   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait NotificationTests
{
    public function getTestPayloads()
    {
        return [
            'blocked' => ['event' => 'blocked', 'proxyApp' => 'Notifiarr', 'starrApp' => 'Radarr', 'endpoint' => '/api/v3/system', 'method' => 'GET'],
            'test'    => ['event' => 'test', 'title' => APP_NAME . ' test', 'message' => 'This is a test message sent from ' . APP_NAME]
        ];
    }
}
