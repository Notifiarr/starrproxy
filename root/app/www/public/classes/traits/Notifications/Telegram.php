<?php

/*
----------------------------------
 ------  Created: 092024   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Telegram
{
    public function telegram($logfile, $botToken, $chatId, $payload, $test = false)
    {
        if (!$botToken) {
            return ['code' => 400, 'error' => 'Missing bot token'];
        }
        if (!$chatId) {
            return ['code' => 400, 'error' => 'Missing chat id'];
        }

        $message = $this->buildTelegramMessage($payload, $test);
        $url     = 'https://api.telegram.org/bot%s/sendMessage';
        $payload = ['chat_id' => $chatId, 'text' => $message, 'parse_mode' => 'MarkdownV2', 'disable_web_page_preview' => true];
        $url     = sprintf($url, $botToken);
        $curl    = curl($url, [], 'POST', json_encode($payload));

        logger($logfile, ['text' => 'notification response:' . json_encode($curl), 'notificationCode' => $curl['code']]);

        $return = ['code' => 200];
        if (!str_equals_any($curl['code'], [200, 201, 400, 401])) {
            logger($logfile, ['text' => 'sending a retry in 5s...']);
            sleep(5);

            $curl = curl($url, [], 'POST', json_encode($payload));
            logger($logfile, ['text' => 'notification response:' . json_encode($curl), 'notificationCode' => $curl['code']]);

            if ($curl['code'] != 200) {
                $return = ['code' => $curl['code'], 'error' => $curl['response']['description']];
            }
        }

        return $return;
    }

    public function buildTelegramMessage($payload, $test = false)
    {
        $message = '';

        switch ($payload['event']) {
            case 'test':
                $message .= APP_NAME . ': Test' . "\n\n";
                $message .= $payload['message'];
                $message .= "\n\n";
                break;
            case 'blocked':
                $message .= APP_NAME . ': Blocked API request' . "\n\n";
                $message .= 'Proxied app: ' . $payload['proxyApp'] . "\n";
                $message .= 'Starr app: ' . $payload['starrApp'] . "\n";
                $message .= 'Endpoint: ' . ($payload['method'] ? '[' . strtoupper($payload['method']) . '] ' : '') . $payload['endpoint'] . "\n";
                $message .= "\n\n";
                break;
        }

        $message = $test ? $message .= '`[TEST NOTIFICATION]`' : $message;

        return $this->escapeTelegramNotification($message);
    }

    public function escapeTelegramNotification($message)
    {
        $chars = ['-', '.', '(', ')', '<', '>', '=', '[', ']'];
        foreach ($chars as $char) {
            $message = str_replace($char, '\\' . $char, $message);
        }

        return $message;
    }
}
