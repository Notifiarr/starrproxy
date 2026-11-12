<?php

/*
----------------------------------
 ------  Created: 022426   ------
 ------  Joly0             ------
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
                    $icon = $customApp['icon'] ?: 'images/logo-16.png';
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
                ?><tr><td colspan="6" class="text-center text-muted">No custom apps yet. Use Add app in the navigation bar.</td></tr><?php
            }
            ?>
        </tbody>
    </table>
    <?php
}

if ($_POST['m'] == 'openCustomAppForm') {
    $existing = $_POST['id'] ? $proxyDb->getCustomApp($_POST['id']) : [];

    echo customAppFormFields($existing);
}

if ($_POST['m'] == 'saveCustomApp') {
    $id         = intval($_POST['id']);
    $name       = trim($_POST['name']);
    $label      = trim($_POST['label']);
    $apiVersion = trim($_POST['api_version']) ?: 'v1';
    $openapiUrl = trim($_POST['openapi_url']);
    $customApp  = $id ? $proxyDb->getCustomApp($id) : [];

    //-- PAGE NAMES ARE RESERVED BECAUSE ?app= IS ROUTED BEFORE ?page=
    $reservedNames = array_merge(StarrApps::LIST, array_map(fn($page) => basename($page, '.php'), glob(ABSOLUTE_PATH . 'pages/*.php')), ['api', 'feed', 'backup']);

    if ($id && !$customApp) {
        $error = 'App not found';
    } elseif (!$id && !preg_match('/^[a-z][a-z0-9]{1,31}$/', $name)) {
        $error = 'Name must be 2 to 32 lowercase letters or digits, starting with a letter';
    } elseif (!$id && in_array($name, $reservedNames)) {
        $error = 'The name "' . $name . '" is reserved, pick another one';
    } elseif ($label === '' || mb_strlen($label) > 64) {
        $error = 'Label is required, max 64 characters';
    } elseif (!preg_match('/^v\d+$/', $apiVersion)) {
        $error = 'API version must look like v1 or v3';
    } elseif (!$starr->isHttpUrl($openapiUrl)) {
        $error = 'OpenAPI URL is required and must be an http or https URL';
    } else {
        $fields = ['label' => $label, 'api_version' => $apiVersion, 'openapi_url' => $openapiUrl];

        if ($id) {
            $error = $proxyDb->updateCustomApp($id, $fields);
            if (!$error) {
                $cache->bust(sprintf(STARR_ENDPOINT_LIST_KEY, $customApp['name']));
            }
        } else {
            $error = $proxyDb->addCustomApp($fields + ['name' => $name]);
            $id    = $proxyDb->insertId();
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
        $contents = file_get_contents($_FILES['icon']['tmp_name']);

        if ($contents === false) {
            $error = 'Could not store the icon';
        } else {
            $mime  = image_type_to_mime_type($iconType);
            $icon  = 'data:' . $mime . ';base64,' . base64_encode($contents);
            $error = $proxyDb->updateCustomApp($customApp['id'], ['icon' => $icon]);
        }
    }

    echo json_encode($error ? ['error' => $error] : ['icon' => $icon]);
}

if ($_POST['m'] == 'deleteCustomApp') {
    $customApp = $proxyDb->getCustomApp($_POST['id']);

    if (!$customApp) {
        $error = 'App not found';
    } elseif (array_filter($starrsTable ?: [], fn($starrInstance) => $starrInstance['starr'] == $customApp['id'] + StarrApps::CUSTOM_ID_OFFSET)) {
        $error = 'Cannot delete: there are starr instances using this custom app. Remove them first.';
    } elseif (!$error = $proxyDb->deleteCustomApp($customApp['id'])) {
        $cache->bust(sprintf(STARR_ENDPOINT_LIST_KEY, $customApp['name']));
    }

    echo json_encode($error ? ['error' => $error] : []);
}
