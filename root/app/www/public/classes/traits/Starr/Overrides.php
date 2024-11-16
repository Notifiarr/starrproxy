<?php

/*
----------------------------------
 ------  Created: 111524   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait Overrides
{
    /*
        This is needed because some applications do not follow the api spec and some endpoints work for methods that are not documented
    */
    public function endpointOverrides($starr)
    {
        $version = $this->apiVersion($starr);

        $overrides['lidarr']    = [];
        $overrides['prowlarr']  = [];
        $overrides['radarr']    = [
                                    '/api/' . $version . '/movie' => ['put']
                                ];
        $overrides['readarr']   = [];
        $overrides['sonarr']    = [
                                    '/api/' . $version . '/series' => ['put']
                                ];
        $overrides['whisparr']  = [];

        return $overrides[$starr];
    }
}
