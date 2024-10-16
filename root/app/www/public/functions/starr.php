<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

function starrApiVersion($app)
{
    switch ($app) {
        case 'lidarr':
        case 'readarr':
            return 'v1';
        case 'radarr':
        case 'sonarr':
        case 'whisparr':
            return 'v3';
    }
}

function testStarrConnection($app, $url, $apikey)
{
    $url        = $url . '/api/' . starrApiVersion($app) . '/config/host';
    $headers    = ['x-api-key:' . $apikey];
    $curl       = curl($url, $headers);

    return $curl;
}

function getStarrEndpoints($app)
{
    switch ($app) {
        case 'lidarr':
            $openapi = 'https://raw.githubusercontent.com/lidarr/Lidarr/develop/src/Lidarr.Api.V1/openapi.json';
            break;
        case 'radarr':
            $openapi = 'https://raw.githubusercontent.com/Radarr/Radarr/develop/src/Radarr.Api.V3/openapi.json';
            break;
        case 'readarr':
            $openapi = 'https://raw.githubusercontent.com/Readarr/Readarr/develop/src/Readarr.Api.V1/openapi.json';
            break;
        case 'sonarr':
            $openapi = 'https://raw.githubusercontent.com/Sonarr/Sonarr/develop/src/Sonarr.Api.V3/openapi.json';
            break;
        case 'whisparr':
            $openapi = 'https://raw.githubusercontent.com/Whisparr/Whisparr/develop/src/Whisparr.Api.V3/openapi.json';
            break;
    }

    $openapi = curl($openapi);

    foreach ($openapi['response']['paths'] as $endpoint => $endpointData) {
        if (str_equals_any($endpoint, ['/', '/{path}'])) {
            continue;
        }

        $endpointInfo = ['label' => '', 'methods' => []];
        foreach ($endpointData as $method => $methodParams) {
            if (str_equals_any($methodParams['tags'][0], ['StaticResource'])) {
                continue;
            }

            $endpointInfo['label'] = $methodParams['tags'][0];
            $endpointInfo['methods'][] = $method;
        }

        if ($endpointInfo) {
            $endpoints[$endpoint] = $endpointInfo;
        }
    }

    return $endpoints;
}

function getAppFromKey($apikey, $truncated = false)
{
    $settings = getFile(APP_SETTINGS_FILE);

    $access = [];
    foreach ($settings['access'] as $starr => $starrApps) {
        foreach ($starrApps as $appId => $appPermissions) {
            if (!$truncated && $apikey == $appPermissions['apikey'] || $truncated && $apikey == truncateMiddle($appPermissions['apikey'], 20)) {
                $access = $appPermissions['endpoints'];
                $app    = $settings[$starr][$appPermissions['instances']];
                break;
            }
        }

        if ($app) {
            break;
        }
    }

    return ['starr' => $starr, 'appId' => $appId, 'app' => $app, 'access' => $access];
}

function accessCounter($app, $id, $code = 200)
{
    if (!$app || !isset($id)) {
        return;
    }

    $settings = getFile(APP_SETTINGS_FILE);

    if (str_equals_any($code, [401, 405])) {
        $settings['access'][$app][$id]['usage']['error'] = intval($settings['access'][$app][$id]['usage']['error']) + 1;
    } else {
        $settings['access'][$app][$id]['usage']['success'] = intval($settings['access'][$app][$id]['usage']['success']) + 1;
    }

    setFile(APP_SETTINGS_FILE, $settings);
}
