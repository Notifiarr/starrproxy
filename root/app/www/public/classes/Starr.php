<?php

/*
----------------------------------
 ------  Created: 110424   ------
 ------  Austin Best	   ------
----------------------------------
*/

//-- BRING IN THE EXTRAS
loadClassExtras('Starr');

class Starr
{
    use Overrides;

    public function __construct()
    {

    }

    public function __tostring()
    {
        return 'Class loaded: Starr';
    }

    public function getStarrInterfaceIdFromName($name)
    {
        switch ($name) {
            case 'lidarr':
                return StarrApps::LIDARR_ID;
            case 'prowlarr':
                return StarrApps::PROWLARR_ID;
            case 'radarr':
                return StarrApps::RADARR_ID;
            case 'readarr':
                return StarrApps::READARR_ID;
            case 'sonarr':
                return StarrApps::SONARR_ID;
            case 'whisparr':
                return StarrApps::WHISPARR_ID;
        }
    }

    public function getStarrInterfaceNameFromId($id)
    {
        switch ($id) {
            case StarrApps::LIDARR_ID:
                return strtolower(StarrApps::LIDARR_LABEL);
            case StarrApps::PROWLARR_ID:
                return strtolower(StarrApps::PROWLARR_LABEL);
            case StarrApps::RADARR_ID:
                return strtolower(StarrApps::RADARR_LABEL);
            case StarrApps::READARR_ID:
                return strtolower(StarrApps::READARR_LABEL);
            case StarrApps::SONARR_ID:
                return strtolower(StarrApps::SONARR_LABEL);
            case StarrApps::WHISPARR_ID:
                return strtolower(StarrApps::WHISPARR_LABEL);
        }
    }

    public function apiVersion($app)
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

    public function testConnection($app, $url, $apikey)
    {    
        $url        = $url . '/api/' . $this->apiVersion($app) . '/config/host';
        $headers    = ['x-api-key:' . $apikey];
        $curl       = curl($url, $headers);
    
        return $curl;
    }

    public function getEndpoints($app)
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
    
        $openapi    = curl($openapi);
        $overrides  = $this->endpointOverrides($app);

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

                if ($overrides) {
                    $endpointOverrides = $overrides[$endpoint];

                    if ($endpointOverrides) {
                        foreach ($endpointOverrides as $endpointMethod) {
                            if (!in_array($endpointMethod, $endpointInfo['methods'])) {
                                $endpointInfo['methods'][] = $endpointMethod;
                            }
                        }
                    }
                }
            }
    
            if ($endpointInfo) {
                $endpoints[$endpoint] = $endpointInfo;
                sort($endpoints[$endpoint]['methods']);
            }
        }
    
        return $endpoints;
    }

    public function downloadBackup($starrBackup, $starrApp)
    {
        global $shell;
    
        $cookie = '';
        if ($starrApp['username'] && $starrApp['password']) {
            $shell->exec('curl -c ' . APP_DATA_PATH . 'cookie.txt -X POST -d "username=' . $starrApp['username'] . '" -d "password=' . $starrApp['password'] . '" "' . $starrApp['url'] . '/login"');
            $cookies = extractCookies(file_get_contents(APP_DATA_PATH . 'cookie.txt'));
    
            if ($cookies[0]['name']) {
                $cookie = $cookies[0]['name'] . '=' . $cookies[0]['value'];
            }
        }
    
        $starrBackup = $starrApp['url'] . $starrBackup;
        $proxyBackup = basename($starrBackup);
    
        $localbackup = fopen(BACKUP_PATH . $proxyBackup, 'wb');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $starrBackup);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cookie: ' . $cookie]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FILE, $localbackup);
        curl_exec($ch);
        curl_close($ch);
        fclose($localbackup);
    
        if (file_exists(APP_DATA_PATH . 'cookie.txt')) {
            unlink(APP_DATA_PATH . 'cookie.txt');
        }
    
        return file_exists(BACKUP_PATH . $proxyBackup) ? BACKUP_PATH . $proxyBackup : '';
    }

    public function getAppFromProxiedKey($apikey, $truncated = false)
    {
        global $proxyDb, $appsTable, $starrsTable;

        $proxyDb ??= new Database(PROXY_DATABASE_NAME);
        $starrsTable ??= $proxyDb->getStarrsTable();
        $appsTable ??= $proxyDb->getAppsTable();

        $access = $starrAppDetails = $proxiedAppDetails = [];

        foreach ($appsTable as $proxiedApp) {
            if ((!$truncated && $apikey == $proxiedApp['apikey']) || ($truncated && $apikey == truncateMiddle($proxiedApp['apikey'], 20))) {
                $proxiedAppDetails  = $proxiedApp;
                $access             = json_decode($proxiedApp['endpoints'], true);

                foreach ($starrsTable as $starrApp) {
                    if ($proxiedApp['starr_id'] == $starrApp['id']) {
                        $starrAppDetails = $starrApp;
                        break;
                    }
                }
                break;
            }
        }
    
        return [
                'starrApp'          => $this->getStarrInterfaceNameFromId($starrApp['starr']), 
                'starrAppDetails'   => $starrAppDetails, 
                'access'            => $access, 
                'proxiedAppDetails' => $proxiedAppDetails
            ];
    }

    public function getAppFromStarrKey($apikey, $starrsTable)
    {    
        global $proxyDb;

        $proxyDb ??= new Database(PROXY_DATABASE_NAME);
        $starrsTable ??= $proxyDb->getStarrsTable();

        foreach ($starrsTable as $starrApp) {    
            if ($starrApp['apikey'] == $apikey) {
                return $starrApp;
            }
        }
    
        return [];
    }

    public function findWildcardEndpoint($starrApp, $endpoint)
    {
        foreach (StarrApps::LIST as $listStarr) {
            if (strtolower($listStarr) != strtolower($starrApp)) {
                continue;
            }

            $endpoints = $this->getEndpoints(strtolower($starrApp));

            $endpointRegexes    = ['/(.*)\/(.*)\/(.*)/', '/(.*)\/(.*)/'];
            $wildcardRegexes    = ['/(.*)({.*})\/({.*})/', '/(.*)({.*})/'];

            foreach ($wildcardRegexes as $index => $wildcardRegex) {
                preg_match($endpointRegexes[$index], $endpoint, $requestMatches);

                if (!$requestMatches) {
                    continue;
                }

                foreach ($endpoints as $accessEndpoint => $accessMethods) {
                    preg_match($wildcardRegex, $accessEndpoint, $accessMatches);
    
                    if (!$accessMatches) {
                        continue;
                    }

                    if ($accessMatches[1] == $requestMatches[1] . '/') {
                        $invalidType = false;
                        foreach ($accessMatches as $accessIndex => $accessMatch) {
                            if (str_equals_any($accessMatch, ['{id}', '{seriesId}', '{movieId}']) && !is_numeric($requestMatches[$accessIndex])) {
                                $invalidType = true;
                                break;
                            }
                        }

                        if ($invalidType) {
                            continue;
                        }

                        $requestEndpointParts   = explode('/', $endpoint);
                        $starrEndpointParts     = explode('/', $accessEndpoint);
    
                        if (count($accessMatches) == count($requestMatches) && count($starrEndpointParts) == count($requestEndpointParts)) {
                            return $accessEndpoint;
                        }
                    }
                }
            }
        }

        return;
    }

    public function isAllowedEndpoint($starrApp, $endpoints, $endpoint)
    {
        if (!$starrApp || !$endpoint) {
            return ['allowed' => false];
        }

        $endpoints = $endpoints ?: [];

        if ($endpoints[$endpoint]) {
            return ['allowed' => true, 'starrEndpoint' => $endpoint];
        }

        // CHECK IF THE ENDPOINT HAS WILDCARDS: /{...}/{...} OR /{...}
        if (!$endpoints[$endpoint]) {
            $wildcard = $this->findWildcardEndpoint($starrApp, $endpoint);
            return ['allowed' => $endpoints[$wildcard], 'starrEndpoint' => $wildcard];
        }

        return ['allowed' => false];
    }

    public function isAllowedEndpointMethod($endpoints, $endpoint, $method)
    {
        if (!$endpoints || !$endpoint || !$method) {
            return false;
        }

        if (in_array($method, $endpoints[$endpoint]) || in_array(strtolower($method), $endpoints[$endpoint])) {
            return true;
        }

        return false;
    }
}
