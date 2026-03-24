<?php

/*
----------------------------------
 ------  Created: 102324   ------
 ------  Austin Best	   ------
----------------------------------
*/

function getTotalAppStats($starrsTable)
{
    global $starr;

    $stats = [];

    if ($starrsTable) {
        foreach ($starrsTable as $starrApp) {
            $app = $starr->getStarrInterfaceNameFromId($starrApp['starr']);

            $stats[$app]++;
        }

        ksort($stats);
    }

    return $stats;
}

function getTotalEndpointStats($starrsTable, $appsTable)
{
    global $starr;

    $starr = $starr ?: new Starr();
    $stats = $endpointList = [];

    if ($starrsTable) {
        foreach ($starrsTable as $starrApp) {
            $app     = $starr->getStarrInterfaceNameFromId($starrApp['starr']);
            $allowed = $total = $apps = 0;

            if (!$endpointList[$app]) {
                $endpointList[$app] = $starr->getEndpoints($app);
            }

            foreach ($appsTable as $proxiedApp) {
                if ($proxiedApp['starr_id'] == $starrApp['id']) {
                    $endpoints  = json_decode($proxiedApp['endpoints'], true);
                    $allowed   += count($endpoints);
                    $total     += count($endpointList[$app]);
                    $apps++;
                }
            }

            $stats[$app] = [
                'apps'    => ($stats[$app]['apps'] + $apps),
                'total'   => ($stats[$app]['total'] + $total),
                'allowed' => ($stats[$app]['allowed'] + $allowed)
            ];
        }

        if ($stats) {
            ksort($stats);
        }
    }

    return $stats;
}

function getTotalUsageStats($starrsTable, $appsTable, $usageTable)
{
    global $starr;

    $stats = [];
    if ($starrsTable && $appsTable && $usageTable) {
        foreach ($starrsTable as $starrApp) {
            $app      = $starr->getStarrInterfaceNameFromId($starrApp['starr']);
            $allowed  = 0;
            $rejected = 0;

            foreach ($appsTable as $proxiedApp) {
                if ($proxiedApp['starr_id'] == $starrApp['id']) {
                    foreach ($usageTable as $usage) {
                        if ($usage['app_id'] == $proxiedApp['id']) {
                            $allowed  += $usage['allowed'];
                            $rejected += $usage['rejected'];
                            break;
                        }
                    }
                }
            }

            $stats[$app] = [
                'allowed'  => ($stats[$app]['allowed'] + $allowed),
                'rejected' => ($stats[$app]['rejected'] + $rejected)
            ];
        }

        if ($stats) {
            ksort($stats);
        }
    }

    return $stats;
}
