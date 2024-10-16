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
        case 'prowlarr':
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
        case 'prowlarr':
            $openapi = 'https://raw.githubusercontent.com/Prowlarr/Prowlarr/develop/src/Prowlarr.Api.V1/openapi.json';
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

function getAppFromProxiedKey($apikey, $truncated = false)
{
    $settingsFile = getFile(APP_SETTINGS_FILE);

    $access = [];
    foreach ($settingsFile['access'] as $starr => $starrApps) {
        foreach ($starrApps as $appId => $appPermissions) {
            if (!$truncated && $apikey == $appPermissions['apikey'] || $truncated && $apikey == truncateMiddle($appPermissions['apikey'], 20)) {
                $access = $appPermissions['endpoints'];
                $app    = $settingsFile[$starr][$appPermissions['instances']];
                break;
            }
        }

        if ($app) {
            break;
        }
    }

    return ['starr' => $starr, 'appId' => $appId, 'app' => $app, 'access' => $access];
}

function getAppFromStarrKey($apikey)
{
    global $starrApps;

    $settingsFile = getFile(APP_SETTINGS_FILE);

    foreach ($settingsFile as $key => $settings) {
        if (!in_array($key, $starrApps)) {
            continue;
        }

        foreach ($settings as $starrIndex => $starrApp) {
            if ($starrApp['apikey'] == $apikey) {
                $starrApp['id'] = $starrIndex;
                return $starrApp;
            }
        }
    }

    return [];
}

function accessCounter($app, $id, $code = 200)
{
    if (!$app || !isset($id)) {
        return;
    }

    $usageFile = getFile(APP_USAGE_FILE);

    if (str_equals_any($code, [401, 405])) {
        $usageFile[$app][$id]['error'] = intval($usageFile[$app][$id]['error']) + 1;
    } else {
        $usageFile[$app][$id]['success'] = intval($usageFile[$app][$id]['success']) + 1;
    }

    setFile(APP_USAGE_FILE, $usageFile);
}
