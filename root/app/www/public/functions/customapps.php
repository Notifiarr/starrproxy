<?php

/*
----------------------------------
 ------  Created: 111126   ------
 ------  Joly0             ------
----------------------------------
*/

function customAppFormFields($existing = [])
{
    ob_start();
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
                OpenAPI URL<br>
                <span class="text-small">http(s) URL to the app's openapi.json, used to discover its endpoints.</span>
            </td>
            <td><input type="text" class="form-control" id="custom-app-openapi-url" placeholder="https://example.com/openapi.json" value="<?= htmlspecialchars($existing['openapi_url']) ?>"></td>
        </tr>
        <tr>
            <td>
                Icon (optional)<br>
                <span class="text-small">PNG, JPEG or WebP image for the nav bar. Max 256KB.</span>
                <?php if ($existing['icon']) { ?>
                    <br><br>Current: <img src="<?= htmlspecialchars($existing['icon']) ?>" style="height: 24px;">
                <?php } ?>
            </td>
            <td>
                <input type="file" class="form-control" id="custom-app-icon" accept="image/png,image/jpeg,image/webp">
            </td>
        </tr>
    </table>
    <?php
    return ob_get_clean();
}

function addAppForm($customApps)
{
    ob_start();
    ?>
    <select class="form-select mb-3" id="add-app-select">
        <option value="">Select an app...</option>
        <optgroup label="Built in">
            <?php foreach (StarrApps::LIST as $starrApp) { ?>
                <option value="<?= htmlspecialchars($starrApp) ?>"><?= htmlspecialchars(ucfirst($starrApp)) ?></option>
            <?php } ?>
        </optgroup>
        <optgroup label="Custom">
            <?php foreach ($customApps as $customApp) { ?>
                <option value="<?= htmlspecialchars(strtolower($customApp['name'])) ?>"><?= htmlspecialchars($customApp['label']) ?></option>
            <?php } ?>
        </optgroup>
        <option value="__new__">+ New custom app...</option>
    </select>
    <div id="add-app-custom-fields" class="d-none">
        <?= customAppFormFields() ?>
    </div>
    <div id="add-app-instance-fields" class="d-none">
        <table class="table table-bordered table-hover">
            <tr>
                <td class="w-50">URL</td>
                <td><input type="text" class="form-control" id="add-app-url" placeholder="http://localhost:1111"></td>
            </tr>
            <tr>
                <td>API key</td>
                <td><input type="text" class="form-control" id="add-app-apikey"></td>
            </tr>
            <tr>
                <td>Username</td>
                <td><input type="text" class="form-control" id="add-app-username" placeholder="optional"></td>
            </tr>
            <tr>
                <td>Password</td>
                <td><input type="password" class="form-control" id="add-app-password" placeholder="optional"></td>
            </tr>
        </table>
    </div>
    <?php
    return ob_get_clean();
}
