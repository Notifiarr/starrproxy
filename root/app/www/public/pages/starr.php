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
?>

<?php $tabIdPrefix = 'starr-' . $app; ?>
<div class="col-sm-12">
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" data-bs-toggle="tab" href="#<?= $tabIdPrefix ?>-instances" aria-selected="true" role="tab"><?= $appLabel ?> instances</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="tab" href="#<?= $tabIdPrefix ?>-apps" aria-selected="false" role="tab">3<sup>rd</sup> party apps</a>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="<?= $tabIdPrefix ?>-instances" role="tabpanel">
            <div class="table-responsive mt-3">
                <table class="table table-no-squish table-bordered" style="min-width: 750px;" align="center">
                    <thead>
                        <tr>
                            <td style="width: 10%;">Name</td>
                            <td style="width: 20%;">URL</td>
                            <td style="width: 20%;">API Key</td>
                            <td style="width: 20%;"><i class="far fa-question-circle" title="This is only needed for corruption checks with Notifiarr" style="cursor:help;"></i> User</td>
                            <td style="width: 20%;"><i class="far fa-question-circle" title="This is only needed for corruption checks with Notifiarr" style="cursor:help;"></i> Pass</td>
                            <td class="w-25">&nbsp;</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($starrsTable) {
                            foreach ($starrsTable as $starrInstance) {
                                if ($starrInstance['starr'] != $starr->getStarrInterfaceIdFromName($app)) {
                                    continue;
                                }

                                $test    = $starr->testConnection($app, $starrInstance['url'], $starrInstance['apikey']);
                                $version = $test['responseHeaders']['X-Application-Version'][0];
                                $branch  = '';

                                if ($version && is_array($test) && is_array($test['response'])) {
                                    $branch = $test['response']['branch'];

                                    if ($test['response']['instanceName'] != $starrInstance['name']) {
                                        $proxyDb->updateStarrAppSetting($starrInstance['id'], 'name', $test['response']['instanceName']);
                                        $starrInstance['name'] = $test['response']['instanceName'];
                                    }
                                }

                                $instanceDown = !$version ? true : false;
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($instanceDown) { ?>
                                            <div class="text-center text-danger">Unreachable</div>
                                        <?php } else { ?>
                                            <?= $starrInstance['name'] ?><br>
                                            <span class="text-small"><?= $branch ?> → v<?= $version ?></span>
                                        <?php } ?>
                                    </td>
                                    <td><input type="text" class="form-control" id="instance-url-<?= $starrInstance['id'] ?>" placeholder="http://localhost:1111" value="<?= $starrInstance['url'] ?>"></td>
                                    <td>
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control" id="instance-apikey-<?= $starrInstance['id'] ?>" data-apikey="<?= $starrInstance['apikey'] ?>" placeholder="12345-67890-09876-54321" value="<?= truncateMiddle($starrInstance['apikey'], 20) ?>" aria-describedby="apikey-<?= $starrInstance['id'] ?>">
                                            <button class="btn btn-primary" type="button" id="apikey-<?= $starrInstance['id'] ?>" onclick="$('#instance-apikey-<?= $starrInstance['id'] ?>').val($('#instance-apikey-<?= $starrInstance['id'] ?>').data('apikey'))"><i class="fas fa-eye"></i></button>
                                        </div>
                                    </td>
                                    <td><input type="text" class="form-control" id="instance-username-<?= $starrInstance['id'] ?>" placeholder="username" value="<?= $starrInstance['username'] ?>"></td>
                                    <td><input type="password" class="form-control" id="instance-password-<?= $starrInstance['id'] ?>" placeholder="password" value="<?= $starrInstance['password'] ?>"></td>
                                    <td align="right">
                                        <button class="btn btn-outline-info" type="button" onclick="testStarr('<?= $starrInstance['id'] ?>', '<?= $app ?>')"><i class="fas fa-network-wired"></i></button>
                                        <button class="btn btn-outline-success" type="button" onclick="saveStarr('<?= $starrInstance['id'] ?>', '<?= $app ?>')"><i class="fas fa-save"></i></button>
                                        <button class="btn btn-outline-danger" type="button" onclick="deleteStarr('<?= $starrInstance['id'] ?>', '<?= $app ?>')"><i class="fas fa-trash-alt"></i></button>
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
                            <td><input type="text" class="form-control" id="instance-username-99" placeholder="username"></td>
                            <td><input type="text" class="form-control" id="instance-password-99" placeholder="password"></td>
                            <td align="right">
                                <button class="btn btn-outline-info" type="button" onclick="testStarr('99', '<?= $app ?>')"><i class="fas fa-network-wired"></i></button>
                                <button class="btn btn-outline-success" type="button" onclick="saveStarr('99', '<?= $app ?>')"><i class="fas fa-plus-circle"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="<?= $tabIdPrefix ?>-apps" role="tabpanel">
            <div class="mt-3">
                You will use <code id="proxyUrl"><?= APP_URL ?></code> <i class="far fa-copy text-info" style="cursor: pointer;" onclick="clipboard('proxyUrl', 'html')" title="Copy apikey to clipboard"></i> as the <?= ucfirst($app) ?> url in the 3<sup>rd</sup> party app and copy the apikey below
            </div>
            <div class="mt-3 mb-2">
                <?php if ($starrsTable) { ?>
                    <button class="btn btn-outline-success" type="button" onclick="openAppStarrAccess('<?= $app ?>', 99)"><i class="fas fa-plus-circle"></i> Add app</button>
                <?php } else { ?>
                    <span class="text-warning">Add some <?= $app ?> instances in the first tab so you can assign apps to them.</span>
                <?php } ?>
            </div>
            <div class="table-responsive" style="min-height: 100vh;">
                <table class="table table-no-squish table-bordered table-hover" style="min-width: 1000px;" align="center">
                    <thead>
                        <tr>
                            <td>App</td>
                            <td>Instance</td>
                            <td>Access</td>
                            <td>Redactions</td>
                            <td>Apikey</td>
                            <td>Usage</td>
                            <td>Actions</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $appRows = 0;
                        if ($appsTable) {
                            foreach ($appsTable as $accessApp) {
                                $template       = '';
                                $parentStarrApp = $proxyDb->getStarrAppFromId($accessApp['starr_id'], $starrsTable);

                                if ($app != $starr->getStarrInterfaceNameFromId($parentStarrApp['starr'])) {
                                    continue;
                                }

                                $appRows++;
                                $accessApp['endpoints'] = json_decode($accessApp['endpoints'], true) ?: [];
                                $usage                  = $usageDb->getStarrAppUsage($accessApp['id']);

                                $templateFile = file_exists($accessApp['template']) ? $accessApp['template'] : str_replace('../', './', $accessApp['template']);
                                if (file_exists($templateFile)) {
                                    $templateEndpoints = getFile($templateFile);
                                    $template          = '<span ' . (count($accessApp['endpoints'], COUNT_RECURSIVE) != count($templateEndpoints, COUNT_RECURSIVE) ? 'class="text-warning" title="Template does not match, click to fix that" style="cursor: pointer;" onclick="viewAppEndpointDiff(' . $accessApp['id'] . ')"' : '') . '>Template: ' . count($templateEndpoints, COUNT_RECURSIVE) . ' endpoint' . (count($templateEndpoints, COUNT_RECURSIVE) == 1 ? '' : 's') . '</span>';
                                }
                                ?>
                                <tr>
                                    <td><?= $accessApp['name'] ?></td>
                                    <td><?= $parentStarrApp['name'] ?> <span class="text-small"><?= $parentStarrApp['url'] ?></span></td>
                                    <td>
                                        <?= count($accessApp['endpoints'], COUNT_RECURSIVE) ?> endpoint<?= count($accessApp['endpoints'], COUNT_RECURSIVE) == 1 ? '' : 's' ?><br>
                                        <span class="text-small"><?= $template ?></span>
                                    </td>
                                    <td><?= count(array_filter(explode(',', $accessApp['redactions']))) ?> applied</td>
                                    <td>
                                        <?= truncateMiddle($accessApp['apikey'], 20) ?>
                                        <i class="far fa-copy text-info" style="cursor: pointer;" onclick="clipboard('app-<?= $accessApp['id'] ?>-apikey', 'html')" title="Copy apikey to clipboard"></i>
                                        <span id="app-<?= $accessApp['id'] ?>-apikey" style="display: none;"><?= $accessApp['apikey'] ?></span>
                                    </td>
                                    <td>
                                        <?= number_format($usage['allowed'] + $usage['rejected']) ?> request<?= $usage['allowed'] + $usage['rejected'] == 1 ? '' : 's' ?><br>
                                        <span class="text-small">Allowed: <?= number_format($usage['allowed']) ?> Blocked: <?= number_format($usage['rejected']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-outline-info btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                                            <div class="dropdown-menu">
                                                <div class="ms-2 me-2">
                                                    <span style="cursor: pointer;" onclick="openAppStarrAccess('<?= $app ?>', <?= $accessApp['id'] ?>)" title="Modify the <?= $accessApp['name'] ?> app's details"><i class="far fa-edit fa-fw"></i> Modify</span><br>
                                                    <span style="cursor: pointer;" onclick="viewAppLog('<?= LOGS_PATH . 'access_' . $accessApp['name'] . '.log' ?>', '<?= truncateMiddle($accessApp['apikey'], 20) ?>', '<?= $accessApp['name'] ?>')" title="View <?= $accessApp['name'] ?> app logs"><i class="fas fa-newspaper fa-fw"></i> Logs</span><br>
                                                    <span style="cursor: pointer;" onclick="openAppStarrAccess('<?= $app ?>', 99, <?= $accessApp['id'] ?>)" title="Clone the <?= $accessApp['name'] ?> app"><i class="far fa-clone fa-fw"></i> Clone</span><br>
                                                    <span style="cursor: pointer;" onclick="openTemplateStarrAccess('<?= $app ?>', <?= $accessApp['id'] ?>)" title="Create a new template based on <?= $accessApp['name'] ?>'s settings"><i class="far fa-file-alt fa-fw"></i> Create template</span><br>
                                                    <div class="dropdown-divider"></div>
                                                    <span style="cursor: pointer;" onclick="resetUsage('<?= $app ?>', <?= $accessApp['id'] ?>)" title="Reset usage counter"><i class="fas fa-recycle text-danger fa-fw"></i> Reset usage</span><br>
                                                    <span style="cursor: pointer;" onclick="deleteAppStarrAccess('<?= $app ?>', <?= $accessApp['id'] ?>)" title="Remove the <?= $accessApp['name'] ?> app's access"><i class="far fa-trash-alt text-danger fa-fw"></i> Delete</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        }

                        if (!$appRows) {
                            ?>
                            <tr>
                                <td colspan="7" class="text-center">No 3<sup>rd</sup> party apps configured yet.</td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
