<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/
?>

<div class="col-sm-12">
    <h4><?= $appLabel ?> instances</h4>
    <div class="table-responsive">
        <table class="table table-bordered" style="min-width: 750px;" align="center">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>URL</th>
                    <th>Apikey</th>
                    <th class="w-25">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($settings[$app]) {
                    foreach ($settings[$app] as $instance => $instanceSettings) {
                        ?>
                        <tr>
                            <td><?= $instanceSettings['name'] ?></td>
                            <td><input type="text" class="form-control" id="instance-url-<?= $instance ?>" placeholder="http://localhost:1111" value="<?= $instanceSettings['url'] ?>"></td>
                            <td>
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" id="instance-apikey-<?= $instance ?>" data-apikey="<?= $instanceSettings['apikey'] ?>" placeholder="12345-67890-09876-54321" value="<?= truncateMiddle($instanceSettings['apikey'], 20) ?>" aria-describedby="apikey-<?= $instance ?>">
                                    <button class="btn btn-primary" type="button" id="apikey-<?= $instance ?>" onclick="$('#instance-apikey-<?= $instance ?>').val($('#instance-apikey-<?= $instance ?>').data('apikey'))">Show</button>
                                </div>
                            </td>
                            <td align="center">
                                <button class="btn btn-outline-info" type="button" onclick="testStarr('<?= $app ?>', '<?= $instance ?>')"><i class="fas fa-network-wired"></i> Test</button>
                                <button class="btn btn-outline-success" type="button" onclick="saveStarr('<?= $app ?>', '<?= $instance ?>')"><i class="fas fa-save"></i> Save instance</button>
                                <button class="btn btn-outline-danger" type="button" onclick="deleteStarr('<?= $app ?>', '<?= $instance ?>')"><i class="fas fa-trash-alt"></i> Delete instance</button>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
                <tr>
                    <td></td>
                    <td><input type="text" class="form-control" id="instance-url-99" placeholder="http://localhost:1111"></td>
                    <td><input type="text" class="form-control" id="instance-apikey-99" placeholder="12345-67890-09876-54321"></td>
                    <td align="center">
                        <button class="btn btn-outline-info" type="button" onclick="testStarr('<?= $app ?>', '99')"><i class="fas fa-network-wired"></i> Test</button>
                        <button class="btn btn-outline-success" type="button" onclick="saveStarr('<?= $app ?>', '99')"><i class="fas fa-plus-circle"></i> Add instance</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<div class="col-sm-12">
    <h4>3<sup>rd</sup> party app access</h4>
    <br>You will use <code id="proxyUrl"><?= $_SERVER['REQUEST_SCHEME'] ?: 'http' ?>://<?= $_SERVER['HTTP_HOST'] ?></code> <i class="far fa-copy text-info" style="cursor: pointer;" onclick="clipboard('proxyUrl', 'html')" title="Copy apikey to clipboard"></i> as the <?= ucfirst($app) ?> url in the 3<sup>rd</sup> party app and copy the apikey below<br><br>
    <div class="row">
        <?php
        if ($settings['access'] && $settings['access'][$app]) {
            foreach ($settings['access'][$app] as $accessIndex => $accessApp) {
                $usageSuccess = intval($accessApp['usage']['success']);
                $usageFailure = intval($accessApp['usage']['error']);
                ?>
                <div class="col-sm-12 col-lg-3">
                    <div class="card border-secondary mb-3">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-sm-6">
                                    App: <?= $accessApp['name'] ?>
                                </div>
                                <div class="col-sm-6 text-end">
                                    <ul style="list-style-type: none;">
                                        <li class="nav-item dropdown">
                                            <a class="nav-link" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false"><i class="fas fa-ellipsis-h text-info"></i></a>
                                            <div class="dropdown-menu">
                                                <div class="ms-2">
                                                <span style="cursor: pointer;" onclick="openAppStarrAccess('<?= $app ?>', <?= $accessIndex ?>)" title="Modify the <?= $accessApp['name'] ?> app's details"><i class="far fa-edit"></i> Modify</span><br>
                                                    <span style="cursor: pointer;" onclick="openAppAccessLog('<?= $app ?>', <?= $accessIndex ?>, '<?= $accessApp['name'] ?>', '<?= truncateMiddle($accessApp['apikey'], 20) ?>')" title="View <?= $accessApp['name'] ?> app logs"><i class="fas fa-newspaper"></i> Logs</span><br>
                                                    <span style="cursor: pointer;" onclick="openAppStarrAccess('<?= $app ?>', 99, <?= $accessIndex ?>)" title="Clone the <?= $accessApp['name'] ?> app"><i class="far fa-clone"></i> Clone</span><br>
                                                    <span style="cursor: pointer;" onclick="openTemplateStarrAccess('<?= $app ?>', <?= $accessIndex ?>)" title="Create a new template based on <?= $accessApp['name'] ?>'s settings"><i class="far fa-file-alt"></i> Create template</span><br>
                                                    <div class="dropdown-divider"></div>
                                                    <span style="cursor: pointer;" onclick="resetUsage('<?= $app ?>', <?= $accessIndex ?>)" title="Reset usage counter"><i class="fas fa-recycle text-danger"></i> Reset usage</span><br>
                                                    <span style="cursor: pointer;" onclick="deleteAppStarrAccess('<?= $app ?>', <?= $accessIndex ?>)" title="Remove the <?= $accessApp['name'] ?> app's access"><i class="far fa-trash-alt text-danger"></i> Delete</span>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="card-body" style="cursor: pointer;">
                            Instance: <?= $settings[$app][$accessApp['instances']]['name'] ?: '!! Orphaned !!' ?> <span class="text-small"><?= $settings[$app][$accessApp['instances']]['url'] ? '(' . $settings[$app][$accessApp['instances']]['url'] . ')' : '' ?></span><br>
                            Access: <?= count($accessApp['endpoints']) ?> endpoint<?= count($accessApp['endpoints']) == 1 ? '' : 's' ?><br>
                            Apikey: <?= truncateMiddle($accessApp['apikey'], 20) ?> <i class="far fa-copy text-info" style="cursor: pointer;" onclick="clipboard('app-<?= $accessIndex ?>-apikey', 'html')" title="Copy apikey to clipboard"></i><span id="app-<?= $accessIndex ?>-apikey" style="display: none;"><?= $accessApp['apikey'] ?></span><br>
                            Usage: <?= number_format($usageSuccess + $usageFailure) ?> request<?= $usageSuccess + $usageFailure == 1 ? '' : 's' ?> (Allowed: <?= number_format($usageSuccess) ?> Rejected: <?= number_format($usageFailure) ?>)
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        ?>
        <div class="col-sm-12 col-lg-3">
            <div class="card border-secondary mb-3">
                <div class="card-header">New app</div>
                <div class="card-body" style="cursor: pointer;" onclick="openAppStarrAccess('<?= $app ?>', 99)">
                    <h5 class="card-title">Give an external app access to a radarr instance</h5>
                    <center>
                        <i class="text-info far fa-plus-square fa-5x"></i>
                    </center>
                </div>
            </div>
        </div>
    </div>
</div>
