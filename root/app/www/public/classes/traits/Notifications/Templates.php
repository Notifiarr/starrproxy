<?php

/*
----------------------------------
 ------  Created: 110924   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait NotificationTemplates
{
    function getTemplate($trigger)
    {
        switch ($trigger) {
            case 'blocked':
                return [
                    'event'    => '',
                    'proxyApp' => '',
                    'starrApp' => '',
                    'endpoint' => '',
                    'method'   => ''
                ];
            case 'test':
                return [
                    'event'   => '',
                    'title'   => '',
                    'message' => ''
                ];
            default:
                return [];
        }
    }
}
