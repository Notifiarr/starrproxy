<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

require 'loader.php';

if ($_POST['m'] == 'testStarr') {
    $test = testStarrConnection($app, $_POST['url'], $_POST['apikey']);

    $error = $result = '';
    if ($test['code'] != 200) {
        $error = 'Failed to connect with code: ' . $test['code'];
    } else {
        $result = 'Connection successful to ' . $app . ': Instance ' . $test['response']['instanceName'];
    }

    echo json_encode(['error' => $error, 'result' => $result]);
}

if ($_POST['m'] == 'deleteStarr') {
    unset($settingsFile[$app][$_POST['instance']]);
    setFile(APP_SETTINGS_FILE, $settingsFile);
}

if ($_POST['m'] == 'saveStarr') {
    //-- SOME BASIC SANITY CHECKING
    if (!str_contains($_POST['url'], 'http')) {
        $_POST['url'] = 'http://' . $_POST['url'];
    }

    $_POST['url'] = rtrim($_POST['url'], '/');

    //-- GET THE INSTANCE NAME
    $test = testStarrConnection($app, $_POST['url'], $_POST['apikey']);
    $name = 'ERROR';

    if ($test['code'] == 200) {
        $name = $test['response']['instanceName'];
    }

    //-- NEW INSTANCE
    if ($_POST['instance'] == '99') {
        $settingsFile[$app][] = ['name' => $name, 'url' => $_POST['url'], 'apikey' => $_POST['apikey']];
    } else {
        if ($_POST['instance'] >= 0) {
            $settingsFile[$app][$_POST['instance']] = ['name' => $name, 'url' => $_POST['url'], 'apikey' => $_POST['apikey']];
        }
    }

    setFile(APP_SETTINGS_FILE, $settingsFile);
}

if ($_POST['m'] == 'newAppStarrAccess') {
    $existing       = $_POST['id'] != 99 ? $settingsFile['access'][$app][$_POST['id']] : [];
    $clone          = isset($_POST['clone']) ? $settingsFile['access'][$app][$_POST['clone']] : [];
    $endpoints      = getStarrEndpoints($app);
    $appInstances   = '';

    if ($clone) {
        $existing = $clone;
        unset($existing['apikey']);
        $existing['name'] .= ' - Clone';
    }

    if ($settingsFile[$app]) {
        foreach ($settingsFile[$app] as $instance => $instanceSettings) {
            $appInstances .= '<option ' . (isset($existing['instances']) && $instance == $existing['instances'] ? 'selected ' : '') . 'value="' . $instance . '">' . $instanceSettings['name'] . ' (' . $instanceSettings['url'] . ')</option>';
        }
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
                <select class="form-select" id="access-instances"><option value="">-- Select instance --</option><?= $appInstances ?></select>
            </td>
        </tr>
        <tr>
            <td>Endpoint template<br><span class="text-small">Automatically select the endpoints based on an app template</span></td>
            <td>
                <select class="form-select" id="access-template" onchange="applyTemplateOptions()"><?= getTemplateOptions() ?></select>
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
        <tr>
            <td colspan="2" align="center"><button class="btn btn-outline-success" onclick="saveAppStarrAccess('<?= $app ?>', <?= $_POST['id'] ?>)"><i class="far fa-save"></i> Enable access</button></td>
        </tr>
    </table>
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

    $access['name']         = $_POST['name'];
    $access['apikey']       = $_POST['apikey'];
    $access['instances']    = $_POST['instances'];
    $access['endpoints']    = $endpoints;

    if ($_POST['id'] != 99) {
        $access['usage'] = $settingsFile['access'][$app][$_POST['id']]['usage'];
        $settingsFile['access'][$app][$_POST['id']] = $access;
    } else {
        $settingsFile['access'][$app][] = $access;
    }

    setFile(APP_SETTINGS_FILE, $settingsFile);
}

if ($_POST['m'] == 'deleteAppStarrAccess') {
    unset($settingsFile['access'][$app][$_POST['id']]);
    setFile(APP_SETTINGS_FILE, $settingsFile);
}

if ($_POST['m'] == 'openAppAccessLog') {
    $logfile    = APP_LOG_PATH . 'access_' . $_POST['accessApp'] . '.log';
    $file       = file_get_contents($logfile);
    $lines      = explode("\n", $file);

    ?>
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" data-bs-toggle="tab" href="#access" aria-selected="true" role="tab">Access log</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="tab" href="#endpoints" aria-selected="false" tabindex="-1" role="tab">Endpoint usage</a>
        </li>
    </ul>
    <div id="myTabContent" class="tab-content">
        <div class="tab-pane fade show active" id="access" role="tabpanel">
            <table class="table table-bordered table-hover">
                <?php
                if ($lines) {
                    $proxiedApp     = getAppFromProxiedKey($_POST['key'], true);
                    $endpointUsage  = [];
                    foreach ($lines as $line) {
                        $error = '';
                        if (str_contains_any($line, ['Code:3', 'Code:4', 'Code:5'])) {
                            $error = '<span class="text-danger">[ERROR]</span> ';
                        }

                        if (!str_contains($line, 'key:' . $_POST['key'])) {
                            continue;
                        }

                        preg_match('/endpoint:(.*);/U', $line, $endpointMatch);
                        preg_match('/method:(.*);/U', $line, $methodMatch);
                        if ($endpointMatch[1]) {
                            $endpointUsage[$endpointMatch[1]][$methodMatch[1]]++;
                        }

                        ?><tr><td><?= $error . $line ?></td></tr><?php
                    }
                } else {
                    ?><tr><td>No log data found.</td></tr><?php   
                }
                ?></table>
        </div>
        <div class="tab-pane fade" id="endpoints" role="tabpanel">
            <h4>Endpoint usage <span class="text-small">(<?= count($endpointUsage) ?> endpoint<?= count($endpointUsage) == 1 ? '' : 's' ?>)</span></h4>
            <?php
            foreach ($endpointUsage as $endpoint => $methods) {
                $endpoint = strtolower($endpoint);

                foreach ($methods as $method => $usage) {
                    $accessError = true;

                    if ($proxiedApp['access'][$endpoint]) {
                        if (in_array(strtolower($method), $proxiedApp['access'][$endpoint])) {
                            $accessError = false;
                        }
                    }

                    ?>
                        <i id="disallowed-endpoint-<?= md5($endpoint.$method) ?>" class="far fa-times-circle text-danger" title="Disallowed endpoint, click to allow it" style="display: <?= $accessError ? 'inline-block' : 'none' ?>; cursor: pointer;" onclick="addEndpointAccess('<?= $app ?>', <?= $_POST['accessId'] ?>, '<?= $endpoint ?>', '<?= $method ?>', '<?= md5($endpoint.$method) ?>')"></i> 
                        <i id="allowed-endpoint-<?= md5($endpoint.$method) ?>" class="far fa-check-circle text-success" title="Allowed endpoint" style="display: <?= !$accessError ? 'inline-block' : 'none' ?>;"></i>
                        [<?= strtoupper($method) ?>] <?= $endpoint . ': ' . number_format($usage) ?> hit<?= $usage == 1 ? '' : 's' ?><br>
                    <?php
                }
            }
            ?>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'openTemplateStarrAccess') {
    ?>
    <table class="table table-bordered table-hover">
        <tr>
            <td>Path</td>
            <td>./templates/<?= $_POST['app'] ?>/*.json</td>
        </tr>
        <tr>
            <td>Name</td>
            <td><input id="new-template-name" type="text" class="form-control" placeholder="notifiarr"></td>
        </tr>
        <tr>
            <td colspan="2" align="center"><button class="btn btn-outline-success" onclick="saveTemplateStarrAccess('<?= $_POST['app'] ?>', <?= $_POST['id'] ?>)">Add template</button></td>
        </tr>
    </table>
    Notes:<br>
    <ul>
        <li>Using an existing template name will overwrite it</li>
        <li>Custom templates will be removed with an update, they are not intended to be one off templates but instead used to build community templates for the repo</li>
    </ul>
    <?php
}

if ($_POST['m'] == 'saveTemplateStarrAccess') {
    $existing = $settingsFile['access'][$app][$_POST['id']]['endpoints'];
    $name = strtolower(preg_replace('/[^a-zA-Z0-9 _-]/', '', $_POST['name']));
    file_put_contents('templates/' . $app . '/' . $name . '.json', json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

if ($_POST['m'] == 'viewTemplate') {
    $template = file_get_contents('templates/' . $_POST['template']);
    list($starr, $app) = explode('/', $_POST['template']);
    ?>
    <h5><?= ucfirst($starr) ?> ... <?= str_replace('.json', '', $app) ?></h5>
    <pre><i class="far fa-copy fa-2x text-info" style="cursor: pointer; float: right;" onclick="clipboard('template-json', 'html')" title="Copy template to clipboard"></i><span id="template-json"><?= $template ?></span></pre>
    <?php
}

if ($_POST['m'] == 'applyTemplateOptions') {
    echo file_get_contents('templates/' . $_POST['template']);
}

if ($_POST['m'] == 'resetUsage') {
    unset($usageFile[$app][$_POST['id']]);
    setFile(APP_USAGE_FILE, $usageFile);
}

if ($_POST['m'] == 'addEndpointAccess') {
    $settingsFile['access'][$app][$_POST['id']]['endpoints'][$_POST['endpoint']][] = $_POST['method'];
    print_r($settingsFile['access'][$app][$_POST['id']]);
    setFile(APP_SETTINGS_FILE, $settingsFile);
}
