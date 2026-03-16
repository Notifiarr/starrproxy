<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

if (!$_SESSION) {
    session_start();
}

if (!$_SESSION['IN_UI']) {
    exit('Invalid session, refresh the page');
}

$getTotalAppStats       = getTotalAppStats($starrsTable);
$getTotalEndpointStats  = getTotalEndpointStats($starrsTable, $appsTable);
$getTotalUsageStats     = getTotalUsageStats($starrsTable, $appsTable, $usageTable);
?>

<div class="card mb-3">
    <div class="card-header">Purpose</div>
    <div class="card-body">
        <p class="card-text">
            The list of 3<sup>rd</sup> party apps that utilize the starr app API's is ever growing but their is no limitation to what they can do and access! Most apps need very 
            little access to function so why expose every method available and full access to your database needlessly?<br><br>

            Permission scopes for apikeys is something that would be better served native in the apps but as it stands that request was denied years ago so it was time to simply make it 
            possible with another solution.
        </p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Protection</div>
    <div class="card-body">
        <div class="row">
            <div class="col-sm-12 col-lg-3">
                <div class="card mb-3">
                    <div class="card-header">Starr instances</div>
                    <div class="card-body">
                        <table class="table table-no-squish table-bordered table-hover">
                            <tr>
                                <td></td>
                                <td>Starr</td>
                                <td>Instances</td>
                            </tr>
                            <?php
                            if ($getTotalAppStats) {
                                foreach ($getTotalAppStats as $starrApp => $instances) {
                                    ?>
                                    <tr>
                                        <td><img src="images/logos/<?= $starrApp ?>.png" style="height:20px;"></td>
                                        <td><?= ucfirst($starrApp) ?></td>
                                        <td><?= $instances ?></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?><td colspan="3">Nothing protected! What are you waiting for?</td><?php
                            }
                            ?>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-lg-3">
                <div class="card mb-3">
                    <div class="card-header">Starr app endpoints</div>
                    <div class="card-body">
                        <table class="table table-no-squish table-bordered table-hover">
                            <tr>
                                <td></td>
                                <td>Starr</td>
                                <td>Apps</td>
                                <td>Enabled</td>
                                <td>Disabled</td>
                            </tr>
                            <?php
                            if ($getTotalEndpointStats) {
                                foreach ($getTotalEndpointStats as $starrApp => $endpointStats) {
                                    ?>
                                    <tr>
                                        <td><img src="images/logos/<?= $starrApp ?>.png" style="height:20px;"></td>
                                        <td><?= ucfirst($starrApp) ?></td>
                                        <td><?= number_format($endpointStats['apps']) ?></td>
                                        <td><?= number_format($endpointStats['allowed']) ?></td>
                                        <td><?= number_format($endpointStats['total'] - $endpointStats['allowed']) ?></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?><td colspan="5">Nothing protected! What are you waiting for?</td><?php
                            }
                            ?>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-lg-3">
                <div class="card mb-3">
                    <div class="card-header">Starr api enforcement</div>
                    <div class="card-body">
                        <table class="table table-no-squish table-bordered table-hover">
                            <tr>
                                <td></td>
                                <td>Starr</td>
                                <td>Allowed</td>
                                <td>Rejected</td>
                            </tr>
                            <?php
                            if ($getTotalUsageStats) {
                                foreach ($getTotalUsageStats as $starrApp => $usageStats) {
                                    ?>
                                    <tr>
                                        <td><img src="images/logos/<?= $starrApp ?>.png" style="height:20px;"></td>
                                        <td><?= ucfirst($starrApp) ?></td>
                                        <td><?= number_format($usageStats['allowed']) ?></td>
                                        <td><?= number_format($usageStats['rejected']) ?></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?><td colspan="4">Nothing protected! What are you waiting for?</td><?php
                            }
                            ?>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-lg-4">
                <div class="card mb-3">
                    <div class="card-header">Template problems</div>
                    <div class="card-body">
                        <?php
                        $noTemplate = '';
                        $notMatching = [];

                        foreach ($appsTable as $app) {
                            if (!$app['template']) {
                                $noTemplate .= ($noTemplate ? ', ' : '') . $app['name'];
                            }

                            $templateFile   = file_exists($app['template']) ? $app['template'] : str_replace('../', './', $app['template']);
                            $appAccess      = json_decode($app['endpoints'], true);

                            if (file_exists($templateFile)) {
                                $appTemplate = getFile($templateFile);

                                if (count($appAccess, COUNT_RECURSIVE) != count($appTemplate, COUNT_RECURSIVE)) {
                                    foreach ($starrsTable as $starrApp) {
                                        if ($starrApp['id'] == $app['starr_id']) {
                                            $notMatching[$starrApp['name']][] = ['id' => $app['id'], 'app' => $app['name'], 'template' => count($appTemplate, COUNT_RECURSIVE), 'endpoints' => count($appAccess, COUNT_RECURSIVE)];
                                            break;
                                        }
                                    }
                                }
                            }
                        }

                        if ($notMatching) {
                            ?>
                            <table class="table table-no-squish table-bordered table-hover">
                                <tr>
                                    <td>Starr</td>
                                    <td>App</td>
                                    <td>App access</td>
                                    <td>Template access</td>
                                    <td></td>
                                </tr>
                                <?php
                                foreach ($notMatching as $starrAppName => $starrAppApps) {
                                    foreach ($starrAppApps as $starrAppApp) {
                                        ?>
                                        <tr>
                                            <td><?= $starrAppName ?></td>
                                            <td><?= $starrAppApp['app'] ?></td>
                                            <td><?= $starrAppApp['endpoints'] ?></td>
                                            <td><?= $starrAppApp['template'] ?></td>
                                            <td><i class="far fa-check-circle text-success" title="Match endpoints/methods to template" style="cursor:pointer;" onclick="viewAppEndpointDiff(<?= $starrAppApp['id'] ?>)"></i></td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                            </table>
                            <?php
                        } else {
                            ?>All apps with a template assigned match their template<?php
                        }

                        if ($noTemplate) {
                            ?><hr>Apps with no template assigned: <?= $noTemplate ?><?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
