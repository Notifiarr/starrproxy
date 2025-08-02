<?php

/*
----------------------------------
 ------  Created: 111524   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait EndpointOverrides
{
    /*
        This is needed because some applications do not follow the api spec and some endpoints work for methods that are not documented
    */
    public function getEndpointOverrides($starr)
    {
        $version = $this->apiVersion($starr);

        $overrides['lidarr']    = [];
        $overrides['prowlarr']  = [];
        $overrides['radarr']    = [
                                    '/api/system/status'                    => ['get'],
                                    '/api/' . $version . '/movie'           => ['put'],
                                    '/api/' . $version . '/config/naming'   => ['put']
                                ];
        $overrides['readarr']   = [];
        $overrides['sonarr']    = [
                                    '/api/system/status'                    => ['get'],
                                    '/api/' . $version . '/series'          => ['put'],
                                    '/api/' . $version . '/config/naming'   => ['put']
                                ];
        $overrides['whisparr']  = [];

        return $overrides[$starr];
    }
}
