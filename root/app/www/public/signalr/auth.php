<?php

if (!defined('ABSOLUTE_PATH')) {
    define('ABSOLUTE_PATH', dirname(__DIR__) . '/');
}

require ABSOLUTE_PATH . 'loader.php';

$apikey     = $_GET['access_token'] ?: $_GET['apikey'] ?: $_SERVER['HTTP_X_API_KEY'];
$proxiedApp = $starr->getAppFromProxiedKey($apikey);

if (!$apikey || !$proxiedApp['starrApp'] || !$proxiedApp['starrAppDetails'] || !$proxiedApp['proxiedAppDetails']) {
    http_response_code(401);
    exit();
}

if (!$proxiedApp['proxiedAppDetails']['signalr']) {
    http_response_code(403);
    exit();
}

$backendUrl   = rtrim($proxiedApp['starrAppDetails']['url'], '/');
$backendParts = parse_url($backendUrl);
if (!filter_var($backendUrl, FILTER_VALIDATE_URL) || !in_array(strtolower($backendParts['scheme']), ['http', 'https'])) {
    http_response_code(502);
    exit();
}

header('X-Starr-Backend-Url: ' . $backendUrl);
header('X-Starr-Api-Key: ' . $proxiedApp['starrAppDetails']['apikey']);
$proxyArgs = $_GET;
unset($proxyArgs['access_token'], $proxyArgs['apikey']);
$proxyArgs = ['access_token' => $proxiedApp['starrAppDetails']['apikey']] + $proxyArgs;
header('X-Starr-Proxy-Args: ' . http_build_query($proxyArgs));
http_response_code(204);
