<?php

/*
----------------------------------
 ------  Created: 102126   ------
 ------  bakerboy448	   ------
----------------------------------
*/

/*
 * nginx auth_request target for /<base>/signalr/*. Lives outside the web root so it can only be
 * reached through the internal /signalr-auth location, never directly by a client.
 *
 * Applies the same scoped-key lookup, endpoint/method allow-list, logging, and usage accounting
 * as api/index.php. The only response is status + headers consumed by nginx, which never forwards
 * them to the client. The upstream path comes from the matched allow-list entry, not the request.
 */

if (!defined('ABSOLUTE_PATH')) {
    define('ABSOLUTE_PATH', __DIR__ . '/public/');
}

require ABSOLUTE_PATH . 'loader.php';

if (($_SERVER['SIGNALR_SUBREQUEST'] ?? '') !== '1') {
    http_response_code(404);
    exit();
}

$requestCounter = $cache->get(REQUEST_COUNTER_KEY) + 1;
$cache->set(REQUEST_COUNTER_KEY, $requestCounter, REQUEST_COUNTER_TIME);

$logfile = LOGS_PATH . 'access.log';
$method  = strtolower($_SERVER['STARR_CLIENT_METHOD'] ?? '');
$uri     = (string) ($_SERVER['ORIG_URI'] ?? '');
$path    = (string) parse_url($uri, PHP_URL_PATH);
$rawArgs = (string) parse_url($uri, PHP_URL_QUERY);

//-- SPLIT THE QUERY, KEEPING IT RAW. THE SCOPED KEY NEVER GOES TO THE ARR
$apikey   = '';
$keepArgs = [];
foreach (array_filter(explode('&', $rawArgs), 'strlen') as $arg) {
    $name = strtolower(urldecode(explode('=', $arg, 2)[0]));
    if (in_array($name, ['access_token', 'apikey'])) {
        $apikey = $apikey ?: urldecode(explode('=', $arg, 2)[1] ?? '');
        continue;
    }
    $keepArgs[] = $arg;
}
$apikey = $apikey ?: ($_SERVER['HTTP_X_API_KEY'] ?? '');

//-- /<base>/signalr/<rest> -> /signalr/<rest>
$endpoint = '';
if (preg_match('#/signalr/(.*)$#i', $path, $match)) {
    $endpoint = rtrim('/signalr/' . strtolower($match[1]), '/');
}

if (!$apikey) {
    logger($logfile, ['req' => $requestCounter, 'proxyCode' => 401]);
    http_response_code(401);
    exit();
}

$proxiedApp = $starr->getAppFromProxiedKey($apikey);

if (!$proxiedApp || !$proxiedApp['starrApp']) {
    logger($logfile, ['req' => $requestCounter, 'apikey' => $apikey, 'endpoint' => $endpoint, 'proxyCode' => 401]);
    http_response_code(401);
    exit();
}

$proxiedAppLogfile = str_replace('access.log', 'access_' . $proxiedApp['proxiedAppDetails']['name'] . '.log', $logfile);
$app               = $proxiedApp['starrApp'];

$block = function ($code, $extra = []) use ($logfile, $proxiedAppLogfile, $requestCounter, $apikey, $endpoint, $proxiedApp, $usageDb, $proxyDb, $notifications) {
    logger($logfile, ['req' => $requestCounter, 'apikey' => $apikey, 'endpoint' => $endpoint, 'proxyCode' => $code]);
    logger($proxiedAppLogfile, ['req' => $requestCounter, 'apikey' => $apikey, 'endpoint' => $endpoint, 'proxyCode' => $code]);
    $usageDb->adjustAppUsage($proxiedApp['proxiedAppDetails']['id'], $code);

    if ($proxyDb->isNotificationTriggerEnabled('blocked')) {
        $notifications->notify(0, 'blocked', [
            'event'    => 'blocked',
            'proxyApp' => $proxiedApp['proxiedAppDetails']['name'],
            'starrApp' => $proxiedApp['starrAppDetails']['name'],
            'endpoint' => $endpoint
        ] + $extra);
    }

    //-- auth_request only understands 401/403
    http_response_code(401);
    exit();
};

//-- THE SIGNALR STREAM CANNOT BE FIELD-REDACTED, SO AN APP WITH REDACTIONS NEVER GETS IT
if (trim((string) $proxiedApp['proxiedAppDetails']['redactions'])) {
    $block(401);
}

$isAllowedEndpoint = $starr->isAllowedEndpoint($app, $proxiedApp['access'], $endpoint);
if (!$isAllowedEndpoint['allowed']) {
    $block(401);
}

$endpoint = $isAllowedEndpoint['starrEndpoint'];
if (!$starr->isAllowedEndpointMethod($proxiedApp['access'], $endpoint, $method)) {
    $block(405, ['method' => $method]);
}

$backendUrl   = rtrim($proxiedApp['starrAppDetails']['url'], '/');
$backendParts = parse_url($backendUrl);
if (!filter_var($backendUrl, FILTER_VALIDATE_URL) || !in_array(strtolower($backendParts['scheme']), ['http', 'https'])) {
    http_response_code(502);
    exit();
}

//-- AUTH ONLY: THE ARR HAS NOT BEEN CONTACTED YET, SO THIS IS NOT A PROXY RESULT AND IS NOT LOGGED AS 200
logger($logfile, ['req' => $requestCounter, 'apikey' => $apikey, 'endpoint' => $endpoint, 'proxyCode' => 'auth-granted']);
logger($proxiedAppLogfile, ['req' => $requestCounter, 'apikey' => $apikey, 'endpoint' => $endpoint, 'proxyCode' => 'auth-granted']);
$usageDb->adjustAppUsage($proxiedApp['proxiedAppDetails']['id'], 200);

//-- CONSUMED BY NGINX ONLY. THE REAL KEY IS INJECTED AS A REQUEST HEADER TO THE ARR
header('X-Starr-Backend-Url: ' . $backendUrl);
header('X-Starr-Endpoint: ' . $endpoint);
header('X-Starr-Proxy-Args: ' . ($keepArgs ? '?' . implode('&', $keepArgs) : ''));
header('X-Starr-Api-Key: ' . $proxiedApp['starrAppDetails']['apikey']);
http_response_code(204);
