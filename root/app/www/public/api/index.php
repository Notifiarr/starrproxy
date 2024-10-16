<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

if (!defined('ABSOLUTE_PATH')) {
    if (file_exists('loader.php')) {
        define('ABSOLUTE_PATH', './');
    }
    if (file_exists('../loader.php')) {
        define('ABSOLUTE_PATH', '../');
    }
    if (file_exists('../../loader.php')) {
        define('ABSOLUTE_PATH', '../../');
    }
}

require ABSOLUTE_PATH . 'loader.php';

$logfile = APP_LOG_PATH . 'access.log';

$apikey = $_GET['apikey'] ?: $_SERVER['HTTP_X_API_KEY'];
if (!$apikey) {
    logger($logfile, $apikey, null, 401);
    apiResponse(401, ['error' => sprintf(APP_API_ERROR, 'No apikey provided')]);
}

$_GET['endpoint'] = strtolower($_GET['endpoint']);

list($endpoint, $parameters) = explode('?', $_GET['endpoint']);
$originalEndpoint   = $endpoint;
$method             = strtolower($_SERVER['REQUEST_METHOD']);
$json               = file_get_contents('php://input');

$proxiedApp = getAppFromKey($apikey);
if (!$proxiedApp) {
    logger($logfile, $apikey, $endpoint, 401);
    apiResponse(401, ['error' => sprintf(APP_API_ERROR, 'Provided apikey is not valid or has no access')]);
}

$app    = $proxiedApp['starr'];
$appId  = $proxiedApp['appId'];

if (!$proxiedApp['access'][$endpoint]) {
    $parameter = false;
    preg_match('/^(.*)\/(.*)$/', $endpoint, $matches);
    $cleanEndpoint = $matches[1] . '/{';

    // CHECK IF THE ENDPOINT HAS /{...}
    foreach ($proxiedApp['access'] as $accessEndpoint => $accessMethods) {
        if (str_contains($accessEndpoint, $cleanEndpoint)) {
            $parameter  = true;
            $endpoint   = $accessEndpoint; //-- ALLOW LATER CHECKS TO PASS
            break;
        }
    }

    if (!$parameter) {
        logger($logfile, $apikey, $endpoint, 401);
        logger(str_replace('access.log', 'access_' . $settings['access'][$app][$appId]['name'] . '.log', $logfile), $apikey, $endpoint, 401);
        accessCounter($app, $appId, 401);
        apiResponse(401, ['error' => sprintf(APP_API_ERROR, 'Provided apikey is missing access to ' . $endpoint)]);
    }
}

if (!in_array($method, $proxiedApp['access'][$endpoint])) {
    logger($logfile, $apikey, $endpoint, 405);
    logger(str_replace('access.log', 'access_' . $settings['access'][$app][$appId]['name'] . '.log', $logfile), $apikey, $endpoint, 405);
    accessCounter($app, $appId, 405);
    apiResponse(405, ['error' => sprintf(APP_API_ERROR, 'Provided apikey is missing access to ' . $endpoint . ' using the ' . $method . ' method')]);
}

$request = curl($proxiedApp['app']['url'] . $originalEndpoint, ['X-Api-Key:' . $proxiedApp['app']['apikey']], $method, $json);

logger($logfile, $apikey, $endpoint, 200, $request['code']);
logger(str_replace('access.log', 'access_' . $settings['access'][$app][$appId]['name'] . '.log', $logfile), $apikey, $endpoint, 200, $request['code'], $request);
accessCounter($app, $appId, $request['code']);

apiResponse($request['code'], $request['response']);
