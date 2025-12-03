<?php

/*
----------------------------------
 ------  Created: 102324   ------
 ------  Austin Best	   ------
----------------------------------
*/

error_reporting(E_ERROR | E_PARSE);

if (!$_SESSION) {
    session_start();
}

if (!$_SESSION['IN_UI']) {
    exit('Invalid session, refresh the page');
}

require '../loader.php';

if ($_POST['m'] == 'testStarr') {
    $test = $starr->testConnection($app, $_POST['url'], $_POST['apikey']);

    $error = $result = '';
    if ($test['code'] != 200) {
        $error = 'Failed to connect with code: ' . $test['code'];
    } else {
        if (!is_array($test['response']) && str_contains($test['response'], 'doctype')) {
            $error = 'The starr app returned HTML, this typically happens with a bad base URL.';
        } else {
            $result = 'Connection successful to ' . $app . ': Instance ' . $test['response']['instanceName'];
        }
    }

    echo json_encode(['error' => $error, 'result' => $result]);
}

if ($_POST['m'] == 'deleteStarr') {
    $error = $proxyDb->deleteStarrApp($_POST['starrId']);

    if (!$error) {
        $usageDb->deleteStarrAppUsage($_POST['starrId']);
    }

    echo $error;
}

if ($_POST['m'] == 'saveStarr') {
    $error = '';

    //-- SOME BASIC SANITY CHECKING
    if (!str_contains($_POST['url'], 'http')) {
        $_POST['url'] = 'http://' . $_POST['url'];
    }

    $_POST['url'] = rtrim($_POST['url'], '/');

    //-- GET THE INSTANCE NAME
    $test = $starr->testConnection($app, $_POST['url'], $_POST['apikey']);
    $name = 'ERROR';

    if ($test['code'] == 200) {
        if (!is_array($test['response']) && str_contains($test['response'], 'doctype')) {
            $error = 'The starr app returned HTML, this typically happens with a bad base URL.';
        } else {
            $name = $test['response']['instanceName'];
        }
    }

    $fields = [
                'name'      => $name, 
                'url'       => $_POST['url'], 
                'apikey'    => $_POST['apikey'], 
                'username'  => rawurldecode($_POST['username']), 
                'password'  => rawurldecode($_POST['password'])
            ];

    if (!$error) {
        if ($_POST['starrId'] == '99') {
            $error = $proxyDb->addStarrApp($app, $fields);
        } else {
            $error = $proxyDb->updateStarrApp($_POST['starrId'], $fields);
        }
    }

    echo $error;
}

if ($_POST['m'] == 'openAppStarrAccess') {
    $existing               = $proxyDb->getAppFromId($_POST['id'], $appsTable);
    $existing['endpoints']  = $existing['endpoints'] ? json_decode($existing['endpoints'], true) : [];
    $existing['redactions'] = trim($existing['redactions']) ? array_filter(explode(',', $existing['redactions'])) : [];
    $clone                  = isset($_POST['clone']) ? $proxyDb->getAppFromId($_POST['clone'], $appsTable) : [];
    $endpoints              = $starr->getEndpoints($app);
    $redactedOptions        = array_filter(explode(',', $settingsTable['redactionFields']));
    $appInstances           = '';

    if ($clone) {
        $existing = $clone;
        unset($existing['apikey']);
        $existing['name'] .= ' - Clone';
    }

    if ($starrsTable) {
        foreach ($starrsTable as $starrInstance) {
            if ($starrInstance['starr'] != $starr->getStarrInterfaceIdFromName($app)) {
                continue;
            }

            $appInstances .= '<option ' . ($existing && $starrInstance['id'] == $existing['starr_id'] ? 'selected ' : '') . 'value="' . $starrInstance['id'] . '">' . $starrInstance['name'] . ' (' . $starrInstance['url'] . ')</option>';
        }
    }

    $templateOptions = getTemplateOptions();
    if ($existing['template']) {
        $templateOptions = str_replace('value="' . $existing['template'] . '"', 'selected value="' . $existing['template'] . '"', $templateOptions);
    }

    ?>
    <?php if ($clone) { ?>
        <center><h4>Cloning: <span class="text-warning"><?= $clone['name'] ?></span></h4></center>
    <?php } ?>
    <table class="table table-bordered table-hover">
        <tr>
            <td class="w-50">App<br><span class="text-small">The app that needs access to the <?= ucfirst($app) ?> API</span></td>
            <td><input type="text" class="form-control" placeholder="Notifiarr" id="access-name" value="<?= $existing['name'] ?>"></td>
        </tr>
        <tr>
            <td>Apikey<br><span class="text-small">The apikey used to negotiate between the app and the starr proxy</span></td>
            <td><input type="text" class="form-control" id="access-apikey" value="<?= $existing['apikey'] ?: generateApikey() ?>"></td>
        </tr>
        <tr>
            <td><?= ucfirst($app) ?> instance<br><span class="text-small">Select which instance this app will access</span></td>
            <td>
                <select class="form-select" id="access-instance"><option value="">-- Select instance --</option><?= $appInstances ?></select>
            </td>
        </tr>
        <tr>
            <td>Redactions<br><span class="text-small">Selected fields will have their value replaced with <code>{PROXY-REDACTED}</code> in the response</span></td>
            <td>
                <select class="form-select" multiple size="5" id="access-redactions">
                <?php
                foreach ($redactedOptions as $redactedOption) {
                    $redactedOption = trim($redactedOption);
                    ?><option <?= !empty($existing['redactions']) && in_array($redactedOption, $existing['redactions']) ? 'selected ' : '' ?>value="<?= $redactedOption ?>"><?= $redactedOption ?></option><?php
                }
                ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>Endpoint template<br><span class="text-small">Automatically select the endpoints based on an app template</span></td>
            <td>
                <select class="form-select" id="access-template" onchange="applyTemplateOptions()"><?= $templateOptions ?></select>
            </td>
        </tr>
        <tr>
            <td>
                <?= ucfirst($app) ?> endpoints<br>
                <span class="text-small">
                    Check all: 
                        <span class="text-info" style="cursor: pointer;" onclick="$('.endpoint-get').prop('checked', true)">get</span>,
                        <span class="text-info" style="cursor: pointer;" onclick="$('.endpoint-post').prop('checked', true)">post</span>,
                        <span class="text-info" style="cursor: pointer;" onclick="$('.endpoint-put').prop('checked', true)">put</span>,
                        <span class="text-info" style="cursor: pointer;" onclick="$('.endpoint-delete').prop('checked', true)">delete</span><br>
                    Uncheck all: 
                        <span class="text-info" style="cursor: pointer;" onclick="$('.endpoint-get').prop('checked', false)">get</span>,
                        <span class="text-info" style="cursor: pointer;" onclick="$('.endpoint-post').prop('checked', false)">post</span>,
                        <span class="text-info" style="cursor: pointer;" onclick="$('.endpoint-put').prop('checked', false)">put</span>,
                        <span class="text-info" style="cursor: pointer;" onclick="$('.endpoint-delete').prop('checked', false)">delete</span><br>
                </span>
            </td>
            <td>
                <table class="table table-hover">
                    <?php
                    $counter = 1;
                    foreach ($endpoints as $endpoint => $endpointInfo) {
                        if (!$endpointInfo['label']) {
                            continue;
                        }

                        ?>
                        <tr class="table-primary">
                            <td><?= $endpointInfo['label'] ?><br><span class="text-small"><?= $endpoint ?></span></td>
                            <td>
                                <?php
                                foreach ($endpointInfo['methods'] as $method) {
                                    if (!$method) {
                                        continue;
                                    }

                                    $checked = is_array($existing['endpoints']) && is_array($existing['endpoints'][$endpoint]) && in_array($method, $existing['endpoints'][$endpoint]) ? 'checked' : '';
                                    ?>
                                    <div class="form-check form-switch">
                                        <input <?= $checked ?> id="endpoint-counter-<?= $counter ?>" data-endpoint="<?= $endpoint ?>" data-method="<?= $method ?>" type="checkbox" class="form-check-input endpoint-<?= $method ?>">
                                        <label for="endpoint-counter-<?= $counter ?>"><?= $method ?></label>
                                    </div>
                                    <?php
                                    $counter++;
                                }
                                ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
            </td>
        </tr>
    </table>
    <div class="bg-dark w-100 p-3 text-center" style="position: sticky; bottom: 0px;">
        <button class="btn btn-success" onclick="saveAppStarrAccess('<?= $app ?>', <?= $_POST['id'] ?>)"><i class="far fa-save"></i> Enable access</button>
    </div>
    <?php
}

if ($_POST['m'] == 'saveAppStarrAccess') {
    $endpoints = [];
    foreach ($_POST as $key => $val) {
        if (!str_contains($key, 'endpoint-')) {
            continue;
        }

        $id = str_replace('endpoint-', '', $key);
        if (!$_POST['enabled-' . $id]) {
            continue;
        }

        $endpoints[$val][] = $_POST['method-' . $id];
    }

    $fields = [];
    $fields['name']         = $_POST['name'];
    $fields['apikey']       = $_POST['apikey'];
    $fields['starr_id']     = intval($_POST['starr_id']);
    $fields['endpoints']    = json_encode($endpoints, JSON_UNESCAPED_SLASHES);
    $fields['template']     = $_POST['template'];
    $fields['redactions']   = $_POST['redactions'];

    if ($_POST['id'] != 99) {
        $error = $proxyDb->updateApp($_POST['id'], $fields);
    } else {
        $error = $proxyDb->addApp($fields);
    }

    echo $error;
}

if ($_POST['m'] == 'deleteAppStarrAccess') {
    $error = $proxyDb->deleteApp($_POST['id']);

    echo $error;
}

if ($_POST['m'] == 'resetUsage') {
    $error = $usageDb->resetAppUsage($_POST['id']);

    echo $error;
}

if ($_POST['m'] == 'addEndpointAccess') {
    $app = $proxyDb->getAppFromId($_POST['id'], $appsTable);
    $app['endpoints'] = json_decode($app['endpoints'], true);
    $app['endpoints'][$_POST['endpoint']][] = $_POST['method'];
    $app['endpoints'] = json_encode($app['endpoints'], JSON_UNESCAPED_SLASHES);

    $error = $proxyDb->updateApp($_POST['id'], $app);

    echo $error;
}

if ($_POST['m'] == 'removeEndpointAccess') {
    $app = $proxyDb->getAppFromId($_POST['id'], $appsTable);
    $app['endpoints'] = json_decode($app['endpoints'], true);

    if (count($app['endpoints'][$_POST['endpoint']]) == 1) { //-- ONLY ONE METHOD, REMOVE THE ENDPOINT
        unset($app['endpoints'][$_POST['endpoint']]);
    } else { //-- MULTIPLE METHODS, REMOVE JUST THE ONE
        foreach ($app['endpoints'][$_POST['endpoint']] as $methodIndex => $method) {
            if ($method == $_POST['method']) {
                unset($app['endpoints'][$_POST['endpoint']][$methodIndex]);
                break;
            }
        }
    }

    $app['endpoints'] = json_encode($app['endpoints'], JSON_UNESCAPED_SLASHES);

    $error = $proxyDb->updateApp($_POST['id'], $app);

    echo $error;
}

if ($_POST['m'] == 'autoAdjustAppEndpoints') {
    foreach ($appsTable as $app) {
        if ($app['id'] != $_POST['appId']) {
            continue;
        }

        $templateFile   = file_exists($app['template']) ? $app['template'] : str_replace('../', './', $app['template']);
        $appTemplate    = getFile($templateFile);

        if ($appTemplate) {
            $app['endpoints'] = json_encode($appTemplate);
            $error = $proxyDb->updateApp($_POST['appId'], $app);
        }

        break;
    }
}

if ($_POST['m'] == 'viewAppEndpointDiff') {
    $templateFile = $appEndpoints = $templateDiff = $appDiff = [];

    foreach ($appsTable as $app) {
        if ($app['id'] != $_POST['appId']) {
            continue;
        }

        $templateFile   = file_exists($app['template']) ? $app['template'] : str_replace('../', './', $app['template']);
        $templateFile   = getFile($templateFile);
        $appEndpoints   = json_decode($app['endpoints'], true);

        break;
    }

    $endpoints = [];
    foreach ($templateFile as $templateEndpoint => $methods) {
        foreach ($methods as $method) {
            $endpoints[$templateEndpoint][$method][] = 'template';
        }
    }

    foreach ($appEndpoints as $appEndpoint => $methods) {
        foreach ($methods as $method) {
            $endpoints[$appEndpoint][$method][] = 'app';
        }
    }

    ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>Endpoint</th>
                    <th>Method</th>
                    <th>Template</th>
                    <th>App</th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($endpoints as $endpoint => $methods) {
                foreach ($methods as $method => $matches) {
                    ?>
                    <tr>
                        <td><?= $endpoint ?></td>
                        <td><?= $method ?></td>
                        <?php
                        if (count($matches) == 2) {
                            ?><td><i class="far fa-check-circle text-success"></i></td><?php
                            ?><td><i class="far fa-check-circle text-success"></i></td><?php
                        } else {
                            if (in_array('template', $matches)) {
                                ?><td><i class="far fa-check-circle text-success"></i></td><?php
                            } else {
                                ?><td><i class="far fa-times-circle text-danger"></i></td><?php
                            }
                            if (in_array('app', $matches)) {
                                ?><td><i class="far fa-check-circle text-success"></i></td><?php
                            } else {
                                ?><td><i class="far fa-times-circle text-danger"></i></td><?php
                            }
                        }
                        ?>
                    </tr>
                    <?php
                }
            }
            ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-center"><button class="btn btn-success" onclick="autoAdjustAppEndpoints(<?= $_POST['appId'] ?>)">Match them</button></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php
}
