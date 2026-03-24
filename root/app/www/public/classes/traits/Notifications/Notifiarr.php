<?php

/*
----------------------------------
 ------  Created: 111623   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Notifiarr
{
    public function notifiarr($logfile, $apikey, $payload, $test = false)
    {
        $headers = ['x-api-key:' . $apikey];
        $url     = 'https://notifiarr.com/api/v1/notification/starrproxy';
        $curl    = curl($url, $headers, 'POST', json_encode($payload));

        logger($logfile, ['text' => 'notification response:' . json_encode($curl)]);

        $return = ['code' => 200];

        if ($curl['code'] != 200) {
            $error  = is_array($curl['response']) && $curl['response']['details'] && $curl['response']['details']['response'] ? $curl['response']['details']['response'] : 'Unknown error';
            $return = ['code' => $curl['code'], 'error' => $error];
        }

        if (!str_equals_any($curl['code'], [200, 201, 400, 401])) {
            logger($logfile, ['text' => 'sending a retry in 5s...']);
            sleep(5);

            $curl = curl($url, $headers, 'POST', json_encode($payload));
            logger($logfile, ['text' => 'notification response:' . json_encode($curl)]);

            if ($curl['code'] != 200) {
                $error  = is_array($curl['response']) && $curl['response']['details'] && $curl['response']['details']['response'] ? $curl['response']['details']['response'] : 'Unknown error';
                $return = ['code' => $curl['code'], 'error' => $error];
            }
        }

        return $return;
    }
}
