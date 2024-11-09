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

$logfile = LOGS_PATH . 'access.log';

$internalEndpoint = false;
$_GET['endpoint'] = strtolower($_GET['endpoint']);

//-- BUILD THE PARAMETER LIST TO SEND THROUGH TO THE STARR APP
$variables = $_GET ?: [];
unset($variables['endpoint']); //-- THIS DOES NOT GO TO STARR
unset($variables['apikey']); //-- THIS IS THE PROXIED KEY WHEN USED, STARR FAVORS GET OVER HEADER SO IT THROWS A 401 IF SENT

list($endpoint, $parameters) = explode('?', $_GET['endpoint']);
$originalEndpoint   = $endpoint;
$method             = strtolower($_SERVER['REQUEST_METHOD']);
$json               = file_get_contents('php://input');
$internalEndpoint   = str_equals_any($endpoint, ['/api/addstarr', '/api/proxystats']) ? true : false;
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
        case '/api/proxystats':
            $stats = [
                        'instances' => getTotalAppStats($starrsTable), 
                        'endpoints' => getTotalEndpointStats($starrsTable, $appsTable), 
                        'usage'     => getTotalUsageStats($starrsTable, $appsTable, $usageTable)
                    ];

            $code       = 200;
            $response   = $stats;
            break;
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
                    $requestError = 'starr field is required, should be one of: ' . implode(', ', StarrApps::LIST);
                } elseif (!in_array($request['starr'], StarrApps::LIST)) {
                    $requestError = 'starr field is not valid, should be one of: ' . implode(', ', StarrApps::LIST);
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

                    $test = $starr->testConnection($request['starr'], $request['url'], $request['apikey']);

                    $error = $result = '';
                    if ($test['code'] != 200) {
                        $code       = $test['code'];
                        $response   = ['error' => sprintf(APP_API_ERROR, 'could not connect to the starr app (' . $request['starr'] . ')')];
                    } else {
                        //-- ADD THE STARR APP
                        $fields = [
                                    'name'      => $test['response']['instanceName'], 
                                    'url'       => $request['url'], 
                                    'apikey'    => $request['apikey'], 
                                    'username'  => rawurldecode($request['username']), 
                                    'password'  => rawurldecode($request['password'])
                                ];
            
                        $error = $proxyDb->addStarrApp($request['starr'], $fields);

                        //-- ADD THE APP ACCESS
                        $starrApp = $starr->getAppFromStarrKey($request['apikey'], $starrsTable);

                        if (!$error) {
                            $scopeKey       = generateApikey();
                            $scopeAccess    = $request['template'] ? json_decode(file_get_contents('../templates/' . $request['starr'] . '/' . $request['template'] . '.json'), true) : [];

                            $fields = [
                                        'name'      => $request['name'], 
                                        'apikey'    => $scopeKey, 
                                        'starr_id'  => $starrApp['id'],
                                        'endpoints' => $scopeAccess
                                    ];
                            $error = $proxyDb->addApp($fields);

                            if (!$error) {
                                $code                       = 200;
                                $response['proxied-scope']  = $request['template'] ? $request['template'] . '\'s template access (' . count($scopeAccess) . ' endpoint' . (count($scopeAccess) != 1 ? 's' : '') . ')' : 'no access';
                                $response['proxied-url']    = APP_URL;
                                $response['proxied-key']    = $scopeKey;
                            } else {
                                $code       = 400;
                                $response   = ['error' => sprintf(APP_API_ERROR, 'failed to add proxied app')];                                
                            }
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
    $proxiedApp = $starr->getAppFromProxiedKey($apikey);

    if (!$proxiedApp) {
        logger($logfile, $apikey, $endpoint, 401);
        apiResponse(401, ['error' => sprintf(APP_API_ERROR, 'provided apikey is not valid or has no access')]);
    }

    if (!$endpoint && $_GET['backup']) { //--- Notifiarr corruption checking
        $proxyBackup = $starr->downloadBackup($_GET['backup'], $proxiedApp['starrAppDetails']);

        if ($proxyBackup) {
            header('Content-Type: application/octet-stream');
            header('Content-Transfer-Encoding: Binary'); 
            header('Content-disposition: attachment; filename="' . $proxyBackup . '"'); 
            readfile($proxyBackup);
        }
    } else {
        // CHECK IF THE ENDPOINT HAS WILDCARDS: /{...}/{...} OR /{...}
        if (!$proxiedApp['access'][$endpoint]) {
            $endpointRegexes    = ['/(.*)\/(.*)\/(.*)/', '/(.*)\/(.*)/'];
            $wildcardRegexes    = ['/(.*)({.*})\/({.*})/', '/(.*)({.*})/'];
            $wildcard           = false;

            foreach ($wildcardRegexes as $index => $wildcardRegex) {
                preg_match($endpointRegexes[$index], $endpoint, $requestMatches);

                if (!$requestMatches) {
                    continue;
                }

                foreach ($proxiedApp['access'] as $accessEndpoint => $accessMethods) {
                    preg_match($wildcardRegex, $accessEndpoint, $accessMatches);
    
                    if (!$accessMatches) {
                        continue;
                    }

                    if ($accessMatches[1] == $requestMatches[1] . '/') {
                        if (count($accessMatches) == count($requestMatches)) {
                            $wildcard   = true;
                            $endpoint   = $accessEndpoint; //-- ALLOW LATER CHECKS TO PASS
                            break;
                        }
                    }
                }

                if ($wildcard) {
                    break;
                }
            }

            if (!$wildcard) {
                logger($logfile, $apikey, $endpoint, 401);
                logger(str_replace('access.log', 'access_' . $proxiedApp['proxiedAppDetails']['name'] . '.log', $logfile), $apikey, $endpoint, 401);
                $usageDb->adjustAppUsage($proxiedApp['proxiedAppDetails']['id'], 401);

                if ($proxyDb->isNotificationTriggerEnabled('blocked')) {
                    $payload    = [
                                    'event'     => 'blocked', 
                                    'proxyApp'  => $proxiedApp['proxiedAppDetails']['name'], 
                                    'starrApp'  => $proxiedApp['starrAppDetails']['name'], 
                                    'endpoint'  => $endpoint
                                ];
                    $notifications->notify(0, 'blocked', $payload);
                }

                apiResponse(401, ['error' => sprintf(APP_API_ERROR, 'provided apikey is missing access to ' . $endpoint)]);
            }
        }

        if (!in_array($method, $proxiedApp['access'][$endpoint])) {
            logger($logfile, $apikey, $endpoint, 405);
            logger(str_replace('access.log', 'access_' . $proxiedApp['proxiedAppDetails']['name'] . '.log', $logfile), $apikey, $endpoint, 405);
            $usageDb->adjustAppUsage($proxiedApp['proxiedAppDetails']['id'], 405);

            if ($proxyDb->isNotificationTriggerEnabled('blocked')) {
                $payload    = [
                                'event'     => 'blocked', 
                                'proxyApp'  => $proxiedApp['proxiedAppDetails']['name'], 
                                'starrApp'  => $proxiedApp['starrAppDetails']['name'], 
                                'endpoint'  => $endpoint,
                                'method'    => $method
                            ];
                $notifications->notify(0, 'blocked', $payload);
            }

            apiResponse(405, ['error' => sprintf(APP_API_ERROR, 'provided apikey is missing access to ' . $endpoint . ' using the ' . $method . ' method')]);
        }

        $starrUrl   = $proxiedApp['starrAppDetails']['url'] . $originalEndpoint . ($variables ? '?' . http_build_query($variables) : '');
        $request    = curl($starrUrl, ['X-Api-Key:' . $proxiedApp['starrAppDetails']['apikey']], $method, $json);

        logger($logfile, $apikey, $endpoint, 200, $request['code']);
        logger(str_replace('access.log', 'access_' . $proxiedApp['proxiedAppDetails']['name'] . '.log', $logfile), $apikey, $originalEndpoint, 200, $request['code'], $request);
        $usageDb->adjustAppUsage($proxiedApp['proxiedAppDetails']['id'], $request['code']);

        if ($request['code'] <= 299 && str_contains($endpoint, 'mediacover')) { //-- OUTPUT THE REQUESTED IMAGE
            foreach ($request['responseHeaders'] as $rhKey => $rhVal) {
                header($rhKey . ': ' . $rhVal[0]);
            }

            echo $request['response'];
            exit();
        } else { //-- RETURN THE JSON API RESPONSE
            apiResponse($request['code'], $request['response'], $request['responseHeaders']);
        }
    }
}
