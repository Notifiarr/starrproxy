<?php

/*
----------------------------------
 ------  Created: 073025   ------
 ------  Austin Best	   ------
----------------------------------
*/

trait EndpointMaps
{
    /*
        This is needed because some applications use dead endpoints instead of updating their code
    */
    public function getEndpointMaps($starr)
    {
        $version = $this->apiVersion($starr);

        $maps['lidarr']     = [];
        $maps['prowlarr']   = [];
        $maps['radarr']     = [
                                ['get' => ['/api/system/status' => '/api/' . $version . '/system/status']]
                            ];
        $maps['readarr']    = [];
        $maps['sonarr']     = [
                                ['get' => ['/api/system/status' => '/api/' . $version . '/system/status']]
                            ];
        $maps['whisparr']   = [];

        return $maps[$starr];
    }
}
