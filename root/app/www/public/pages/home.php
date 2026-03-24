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

$getTotalAppStats      = getTotalAppStats($starrsTable);
$getTotalEndpointStats = getTotalEndpointStats($starrsTable, $appsTable);
$getTotalUsageStats    = getTotalUsageStats($starrsTable, $appsTable, $usageTable);
?>

<div class="card mb-3">
    <div class="card-header">Purpose</div>
    <div class="card-body">
        <p class="card-text">
            The list of third-party apps using the Starr app APIs continues to grow, but there are currently no restrictions on what they can access or perform.<br>
            In reality, most of these apps only require minimal permissions to function properly, so granting full access to every endpoint and your entire database is unnecessary and potentially risky.<br><br>
            Ideally, API key permission scopes would be implemented natively within the apps themselves. However, since that request was declined years ago, it became necessary to develop an alternative solution to address this limitation.
        </p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Protection</div>
    <div class="card-body">
        <div class="row">
            <div class="col-sm-12 col-lg-3">
                <div class="card mb-3">
                    <div class="card-header">Instances</div>
                    <div class="card-body">
                        <table class="table table-no-squish table-bordered table-hover">
                            <thead>
                                <tr>
                                    <td></td>
                                    <td>Starr</td>
                                    <td>Instances</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($getTotalAppStats) {
                                    foreach ($getTotalAppStats as $starrApp => $instances) {
                                        ?>
                                        <tr>
                                            <td class="table-icon"><img src="images/logos/<?= $starrApp ?>.png" style="height:20px;"></td>
                                            <td>
                                                <?= ucfirst($starrApp) ?>
                                            </td>
                                            <td>
                                                <?= $instances ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <td colspan="3">Nothing protected! What are you waiting for?</td>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-lg-3">
                <div class="card mb-3">
                    <div class="card-header">Endpoints</div>
                    <div class="card-body">
                        <table class="table table-no-squish table-bordered table-hover">
                            <thead>
                                <tr>
                                    <td></td>
                                    <td>Starr</td>
                                    <td>Apps</td>
                                    <td>Enabled</td>
                                    <td>Disabled</td>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                if ($getTotalEndpointStats) {
                                    foreach ($getTotalEndpointStats as $starrApp => $endpointStats) {
                                        ?>
                                        <tr>
                                            <td><img src="images/logos/<?= $starrApp ?>.png" style="height:20px;"></td>
                                            <td>
                                                <?= ucfirst($starrApp) ?>
                                            </td>
                                            <td>
                                                <?= number_format($endpointStats['apps']) ?>
                                            </td>
                                            <td>
                                                <?= number_format($endpointStats['allowed']) ?>
                                            </td>
                                            <td>
                                                <?= number_format($endpointStats['total'] - $endpointStats['allowed']) ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <td colspan="5">Nothing protected! What are you waiting for?</td>
                                    <?php
                                }
                                ?>
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-lg-3">
                <div class="card mb-3">
                    <div class="card-header">Enforcement</div>
                    <div class="card-body">
                        <table class="table table-no-squish table-bordered table-hover">
                            <thead>
                                <tr>
                                    <td></td>
                                    <td>Starr</td>
                                    <td>Allowed</td>
                                    <td>Rejected</td>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                if ($getTotalUsageStats) {
                                    foreach ($getTotalUsageStats as $starrApp => $usageStats) {
                                        ?>
                                        <tr>
                                            <td><img src="images/logos/<?= $starrApp ?>.png" style="height:20px;"></td>
                                            <td>
                                                <?= ucfirst($starrApp) ?>
                                            </td>
                                            <td>
                                                <?= number_format($usageStats['allowed']) ?>
                                            </td>
                                            <td>
                                                <?= number_format($usageStats['rejected']) ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <td colspan="4">Nothing protected! What are you waiting for?</td>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-lg-4">
                <div class="card mb-3">
                    <div class="card-header">Issues</div>
                    <div class="card-body">
                        <?php
                        $noTemplate  = '';
                        $notMatching = [];

                        foreach ($appsTable as $app) {
                            if (!$app['template']) {
                                $noTemplate .= ($noTemplate ? ', ' : '') . $app['name'];
                            }

                            $templateFile = file_exists($app['template']) ? $app['template'] : str_replace('../', './', $app['template']);
                            $appAccess    = json_decode($app['endpoints'], true);

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
                                <thead>
                                    <tr>
                                        <td>Starr</td>
                                        <td>App</td>
                                        <td>Allowed Endpoints</td>
                                        <td>Template Endpoints</td>
                                        <td></td>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php
                                    foreach ($notMatching as $starrAppName => $starrAppApps) {
                                        foreach ($starrAppApps as $starrAppApp) {
                                            ?>
                                            <tr>
                                                <td>
                                                    <?= $starrAppName ?>
                                                </td>
                                                <td>
                                                    <?= $starrAppApp['app'] ?>
                                                </td>
                                                <td>
                                                    <?= $starrAppApp['endpoints'] ?>
                                                </td>
                                                <td>
                                                    <?= $starrAppApp['template'] ?>
                                                </td>
                                                <td><i class="far fa-check-circle text-success" title="Match endpoints/methods to template" style="cursor:pointer;" onclick="viewAppEndpointDiff(<?= $starrAppApp['id'] ?>)"></i></td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                    ?>
                                </tbody>

                            </table>
                            <?php
                        } else {
                            ?>All apps with a template assigned match their template<?php
                        }

                        if ($noTemplate) {
                            ?>
                            <hr>Apps with no template assigned: <?= $noTemplate ?><?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
