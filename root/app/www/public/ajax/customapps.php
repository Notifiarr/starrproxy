<?php

/*
----------------------------------
 ------  Created: 022426   ------
 ------  Custom Apps        ------
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

const CUSTOM_APP_ICON_TYPES    = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_WEBP => 'webp'];
const CUSTOM_APP_ICON_MAX_SIZE = 262144;

if ($_POST['m'] == 'getCustomApps') {
    $customApps = $proxyDb->getCustomApps();

    ?>
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>Icon</th>
                <th>Name</th>
                <th>Label</th>
                <th>API Version</th>
                <th>OpenAPI URL</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($customApps) {
                foreach ($customApps as $customApp) {
                    $icon = $customApp['icon'] ? 'images/custom/' . $customApp['icon'] : 'images/logo-16.png';
                    ?>
                    <tr>
                        <td><img src="<?= htmlspecialchars($icon) ?>" style="height: 24px;"></td>
                        <td><?= htmlspecialchars($customApp['name']) ?></td>
                        <td><?= htmlspecialchars($customApp['label']) ?></td>
                        <td><?= htmlspecialchars($customApp['api_version']) ?></td>
                        <td class="text-small"><?= htmlspecialchars($customApp['openapi_url']) ?: '<span class="text-muted">None (manual endpoints only)</span>' ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-info" onclick="openCustomAppForm(<?= intval($customApp['id']) ?>)"><i class="far fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCustomApp(<?= intval($customApp['id']) ?>)"><i class="far fa-trash-alt"></i></button>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                ?><tr><td colspan="6" class="text-center text-muted">No custom apps yet. Add one below.</td></tr><?php
            }
            ?>
        </tbody>
    </table>
    <?php
}

if ($_POST['m'] == 'openCustomAppForm') {
    $existing = $_POST['id'] ? $proxyDb->getCustomApp($_POST['id']) : [];

    ?>
    <table class="table table-bordered table-hover">
        <tr>
            <td class="w-50">
                Name<br>
                <span class="text-small">Internal identifier: lowercase letters and digits, starting with a letter, like "myapp". Cannot be changed later.</span>
            </td>
            <td><input type="text" class="form-control" id="custom-app-name" placeholder="myapp" value="<?= htmlspecialchars($existing['name']) ?>" <?= $existing ? 'readonly' : '' ?>></td>
        </tr>
        <tr>
            <td>
                Display Label<br>
                <span class="text-small">Shown in the navigation bar</span>
            </td>
            <td><input type="text" class="form-control" id="custom-app-label" placeholder="My App" value="<?= htmlspecialchars($existing['label']) ?>"></td>
        </tr>
        <tr>
            <td>
                API Version<br>
                <span class="text-small">The API version prefix (e.g. v1, v3)</span>
            </td>
            <td><input type="text" class="form-control" id="custom-app-api-version" placeholder="v1" value="<?= htmlspecialchars($existing['api_version'] ?: 'v1') ?>"></td>
        </tr>
        <tr>
            <td>
                OpenAPI URL (optional)<br>
                <span class="text-small">http(s) URL to an openapi.json for endpoint discovery. Leave blank to manage endpoints manually.</span>
            </td>
            <td><input type="text" class="form-control" id="custom-app-openapi-url" placeholder="https://example.com/openapi.json" value="<?= htmlspecialchars($existing['openapi_url']) ?>"></td>
        </tr>
        <tr>
            <td>
                Icon (optional)<br>
                <span class="text-small">PNG, JPEG or WebP image for the nav bar. Max 256KB.</span>
                <?php if ($existing['icon']) { ?>
                    <br><br>Current: <img src="images/custom/<?= htmlspecialchars($existing['icon']) ?>" style="height: 24px;">
                <?php } ?>
            </td>
            <td>
                <input type="file" class="form-control" id="custom-app-icon" accept="image/png,image/jpeg,image/webp">
            </td>
        </tr>
    </table>
    <?php
}

if ($_POST['m'] == 'saveCustomApp') {
    $id         = intval($_POST['id']);
    $name       = trim($_POST['name']);
    $label      = trim($_POST['label']);
    $apiVersion = trim($_POST['api_version']) ?: 'v1';
    $openapiUrl = trim($_POST['openapi_url']);

    //-- PAGE NAMES ARE RESERVED BECAUSE ?app= IS ROUTED BEFORE ?page=
    $reservedNames = array_merge(StarrApps::LIST, array_map(fn($page) => basename($page, '.php'), glob(ABSOLUTE_PATH . 'pages/*.php')), ['api', 'feed', 'backup']);

    if (!$id && !preg_match('/^[a-z][a-z0-9]{1,31}$/', $name)) {
        $error = 'Name must be 2 to 32 lowercase letters or digits, starting with a letter';
    } elseif (!$id && in_array($name, $reservedNames)) {
        $error = 'The name "' . $name . '" is reserved, pick another one';
    } elseif ($label === '' || mb_strlen($label) > 64) {
        $error = 'Label is required, max 64 characters';
    } elseif (!preg_match('/^v\d+$/', $apiVersion)) {
        $error = 'API version must look like v1 or v3';
    } elseif ($openapiUrl && !$starr->isHttpUrl($openapiUrl)) {
        $error = 'OpenAPI URL must be an http or https URL';
    } else {
        $fields = ['label' => $label, 'api_version' => $apiVersion, 'openapi_url' => $openapiUrl];

        if ($id) {
            $error = $proxyDb->updateCustomApp($id, $fields);
        } else {
            $error = $proxyDb->addCustomApp($fields + ['name' => $name]);
            $id    = $proxyDb->insertId();

            if (!$error) {
                $proxyDb->setCustomAppNavSetting($name, true);
            }
        }
    }

    echo json_encode($error ? ['error' => $error] : ['id' => $id]);
}

if ($_POST['m'] == 'uploadCustomAppIcon') {
    $customApp = $proxyDb->getCustomApp($_POST['id']);
    $iconType  = $_FILES['icon']['tmp_name'] ? (getimagesize($_FILES['icon']['tmp_name'])[2] ?? 0) : 0;

    if (!$customApp) {
        $error = 'App not found';
    } elseif ($_FILES['icon']['error'] != UPLOAD_ERR_OK) {
        $error = 'No file uploaded or upload error';
    } elseif ($_FILES['icon']['size'] > CUSTOM_APP_ICON_MAX_SIZE) {
        $error = 'File too large, max 256KB';
    } elseif (!isset(CUSTOM_APP_ICON_TYPES[$iconType])) { //-- CHECK THE ACTUAL IMAGE CONTENT, NOT THE CLIENT FILENAME
        $error = 'Icon must be a PNG, JPEG or WebP image';
    } else {
        $iconDir  = ABSOLUTE_PATH . 'images/custom/';
        $filename = $customApp['name'] . '.' . CUSTOM_APP_ICON_TYPES[$iconType];

        if (!move_uploaded_file($_FILES['icon']['tmp_name'], $iconDir . $filename)) {
            $error = 'Could not store the icon';
        } else {
            if ($customApp['icon'] && $customApp['icon'] != $filename && file_exists($iconDir . $customApp['icon'])) {
                unlink($iconDir . $customApp['icon']);
            }

            $error = $proxyDb->updateCustomApp($customApp['id'], ['icon' => $filename]);
        }
    }

    echo json_encode($error ? ['error' => $error] : ['icon' => $filename]);
}

if ($_POST['m'] == 'deleteCustomApp') {
    $customApp = $proxyDb->getCustomApp($_POST['id']);

    if (!$customApp) {
        $error = 'App not found';
    } elseif (array_filter($starrsTable ?: [], fn($starrInstance) => $starrInstance['starr'] == $customApp['id'] + StarrApps::CUSTOM_ID_OFFSET)) {
        $error = 'Cannot delete: there are starr instances using this custom app. Remove them first.';
    } elseif (!$error = $proxyDb->deleteCustomApp($customApp['id'])) {
        $proxyDb->deleteCustomAppNavSetting($customApp['name']);

        if ($customApp['icon'] && file_exists(ABSOLUTE_PATH . 'images/custom/' . $customApp['icon'])) {
            unlink(ABSOLUTE_PATH . 'images/custom/' . $customApp['icon']);
        }
    }

    echo json_encode($error ? ['error' => $error] : []);
}
