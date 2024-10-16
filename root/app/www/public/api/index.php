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

$internalEndpoint = false;
$_GET['endpoint'] = strtolower($_GET['endpoint']);

list($endpoint, $parameters) = explode('?', $_GET['endpoint']);
$originalEndpoint   = $endpoint;
$method             = strtolower($_SERVER['REQUEST_METHOD']);
$json               = file_get_contents('php://input');
$internalEndpoint   = str_equals_any($endpoint, ['/api/addstarr']) ? true : false;
$apikey             = $_GET['apikey'] ?: $_SERVER['HTTP_X_API_KEY'];

if (!$apikey) {
    logger($logfile, $apikey, null, 401);
    apiResponse(401, ['error' => sprintf(APP_API_ERROR, 'no apikey provided')]);
}

if ($internalEndpoint) {
    if (APP_APIKEY != $apikey) {
        apiResponse(401, ['error' => sprintf(APP_API_ERROR, 'provided apikey is not valid for internal api access')]);
    }

    switch ($endpoint) {
        case '/api/addstarr':
            if (!$json) {
                $code       = 400;
                $response   = ['error' => sprintf(APP_API_ERROR, 'missing required fields for addstarr endpoint. Optional: template | Required: name, starr, url, apikey')];
            } else {
                $request        = json_decode($json, true);
                $requestError   = '';

                if (!$request['name']) {
                    $requestError = 'name field is required, should be the name of the 3rd party app/script';
                } elseif (!$request['starr']) {
                    $requestError = 'starr field is required, should be one of: ' . implode(', ', $starrApps);
                } elseif (!in_array($request['starr'], $starrApps)) {
                    $requestError = 'starr field is not valid, should be one of: ' . implode(', ', $starrApps);
                } elseif (!$request['url']) {
                    $requestError = 'url field is required, should be the local url to the starr app';
                } elseif (!$request['apikey']) {
                    $requestError = 'apikey field is required, should be the apikey to the starr app';
                } elseif ($request['template']) {
                    if (!file_exists('../templates/' . $request['starr'] . '/' . $request['template'] . '.json')) {
                        $requestError = 'requested template (' . $request['template'] . ') does not exist for ' . $request['starr'] . ', provide a valid template or leave it blank';
                    }
                }

                if ($requestError) {
                    $code       = 400;
                    $response   = ['error' => sprintf(APP_API_ERROR, $requestError)];
                } else {
                    //-- SOME BASIC SANITY CHECKING
                    if (!str_contains($request['url'], 'http')) {
                        $request['url'] = 'http://' . $request['url'];
                    }

                    $request['url'] = rtrim($request['url'], '/');

                    $test = testStarrConnection($request['starr'], $request['url'], $request['apikey']);

                    $error = $result = '';
                    if ($test['code'] != 200) {
                        $code       = $test['code'];
                        $response   = ['error' => sprintf(APP_API_ERROR, 'could not connect to the starr app (' . $request['starr'] . ')')];
                    } else {
                        //-- ADD THE STARR APP
                        $settingsFile[$request['starr']][] = ['name' => $test['response']['instanceName'], 'url' => $request['url'], 'apikey' => $request['apikey']];
                        setFile(APP_SETTINGS_FILE, $settingsFile);
                        $settingsFile = getFile(APP_SETTINGS_FILE); //-- RELOAD IT AFTER ADDING THE STARR APP

                        //-- ADD THE APP ACCESS
                        $starrApp = getAppFromStarrKey($request['apikey']);

                        if ($starrApp['id']) {
                            $scopeKey       = generateApikey();
                            $scopeAccess    = $request['template'] ? json_decode(file_get_contents('../templates/' . $request['starr'] . '/' . $request['template'] . '.json'), true) : [];

                            $settingsFile['access'][$request['starr']][] = ['name' => $request['name'], 'apikey' => $scopeKey, 'instances' => $starrApp['id'], 'endpoints' => $scopeAccess];
                            setFile(APP_SETTINGS_FILE, $settingsFile);

                            $code                       = 200;
                            $response['proxied-scope']  = $request['template'] ? $request['template'] . '\'s template access (' . count($scopeAccess) . ' endpoint' . (count($scopeAccess) != 1 ? 's' : '') . ')' : 'no access';
                            $response['proxied-url']    = APP_URL;
                            $response['proxied-key']    = $scopeKey;
                        } else {
                            $code       = 400;
                            $response   = ['error' => sprintf(APP_API_ERROR, 'failed to add starr app')];
                        }
                    }
                }
            }
            break;
        default:
            $code       = 404;
            $response   = ['error' => sprintf(APP_API_ERROR, 'invalid internal api route')];
            break;
    }

    apiResponse($code, $response);
} else {
    $proxiedApp = getAppFromProxiedKey($apikey);
    if (!$proxiedApp) {
        logger($logfile, $apikey, $endpoint, 401);
        apiResponse(401, ['error' => sprintf(APP_API_ERROR, 'provided apikey is not valid or has no access')]);
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
            logger(str_replace('access.log', 'access_' . $settingsFile['access'][$app][$appId]['name'] . '.log', $logfile), $apikey, $endpoint, 401);
            accessCounter($app, $appId, 401);
            apiResponse(401, ['error' => sprintf(APP_API_ERROR, 'provided apikey is missing access to ' . $endpoint)]);
        }
    }
    
    if (!in_array($method, $proxiedApp['access'][$endpoint])) {
        logger($logfile, $apikey, $endpoint, 405);
        logger(str_replace('access.log', 'access_' . $settingsFile['access'][$app][$appId]['name'] . '.log', $logfile), $apikey, $endpoint, 405);
        accessCounter($app, $appId, 405);
        apiResponse(405, ['error' => sprintf(APP_API_ERROR, 'provided apikey is missing access to ' . $endpoint . ' using the ' . $method . ' method')]);
    }
    
    $request = curl($proxiedApp['app']['url'] . $originalEndpoint, ['X-Api-Key:' . $proxiedApp['app']['apikey']], $method, $json);
    
    logger($logfile, $apikey, $endpoint, 200, $request['code']);
    logger(str_replace('access.log', 'access_' . $settingsFile['access'][$app][$appId]['name'] . '.log', $logfile), $apikey, $endpoint, 200, $request['code'], $request);
    accessCounter($app, $appId, $request['code']);
    
    apiResponse($request['code'], $request['response']);
}
