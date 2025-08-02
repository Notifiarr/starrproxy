<?php

/*
----------------------------------
 ------  Created: 102324   ------
 ------  Austin Best	   ------
----------------------------------
*/

if (!$_SESSION) {
    session_start();
}

if (!$_SESSION['IN_UI']) {
    exit('Invalid session, refresh the page');
}

$migrations = '<option value="000">000_fresh_start</option>';
$dir = opendir(MIGRATIONS_PATH);
while ($migration = readdir($dir)) {
    if (str_contains($migration, '.php')) {
        $migrations .= '<option ' . ($settingsTable['migration'] == substr($migration, 0, 3) ? 'selected ' : '') . 'value="' . substr($migration, 0, 3) . '">' . str_replace('.php', '', $migration) . '</option>';
    }
}
closedir($dir);

$backups    = $proxyDb->getBackups();
$cacheStats = $cache->stats();
?>
<div class="w-100 mb-2">
    <button class="btn btn-outline-success border-light" onclick="saveSettings()"><i class="far fa-save"></i> Save</button>
</div>
<div class="row">
    <div class="col-sm-12">
        <div class="card border-default mb-3">
            <div class="card-header">System</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <tbody>
                            <tr>
                                <td class="w-25">
                                    API key<br>
                                    <span class="text-small">File: <?= APP_APIKEY_FILE ?></span>
                                </td>
                                <td>
                                    <div class="input-group mb-3 w-25">
                                        <input type="text" class="form-control" aria-describedby="apikey-input" value="<?= APP_APIKEY ?>" id="setting-apikey">
                                        <button title="Copy" class="btn btn-primary" type="button" id="apikey-input" onclick="clipboard('setting-apikey', 'val')"><i class="far fa-copy"></i></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="card border-default mb-3">
            <div class="card-header">UI</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th colspan="2">Navigation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach (StarrApps::LIST as $index => $starrApp) {
                                $starrApp = ucfirst($starrApp);
                                ?>
                                <tr>
                                    <td class="w-25">Nav link: <?= $starrApp ?></td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="setting-uiHeader<?= $starrApp ?>" <?= $settingsTable['uiHeader' . $starrApp] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="setting-uiHeader<?= $starrApp ?>">Show</label>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                            <tr>
                                <td class="w-25">Nav link: Notifications</td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="setting-uiHeaderNotifications" <?= $settingsTable['uiHeaderNotifications'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="setting-uiHeaderNotifications">Show</label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="w-25">Nav link: Help</td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="setting-uiHeaderHelp" <?= $settingsTable['uiHeaderHelp'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="setting-uiHeaderHelp">Show</label>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <thead>
                            <tr>
                                <th colspan="2">Templates</th>
                            </tr>
                            <tr>
                                <td class="w-25">Order</td>
                                <td>
                                    <select id="setting-templateOrder" class="form-select w-50">
                                        <option <?= $settingsTable['templateOrder'] == 1 ? 'selected ' : '' ?>value="1">Group by app</option>
                                        <option <?= $settingsTable['templateOrder'] == 2 ? 'selected ' : '' ?>value="2">Group by starr</option>
                                    </select>
                                </td>
                            </tr>
                        </thead>
                    </table>
                    <br>** Refresh the page after changing UI settings
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="card border-default mb-3">
            <div class="card-header">Database</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <tbody>
                            <tr>
                                <td class="w-25">
                                    Migration<br>
                                    <span class="text-small">Newest: <?= $proxyDb->getNewestMigration() ?></span>
                                </td>
                                <td>
                                    <select class="form-select w-50" id="setting-migration"><?= $migrations ?></select>
                                    <span class="text-small">If you change this, refresh the page after you click save.</span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Backup retention length<br>
                                    <span class="text-small">Default: <?= BACKUP_AGE ?></span>
                                </td>
                                <td><input type="number" class="form-control d-inline-block w-25" id="setting-backupRetentionLength" value="<?= BACKUP_AGE ?>"> days</td>
                            </tr>
                            <tr>
                                <td>Backups</td>
                                <td>
                                    <div class="row m-0 p-0">
                                        <?php
                                        if (!$backups) {
                                            ?>No backups found yet<?php
                                        } else {
                                            foreach ($backups as $date => $databases) {
                                                ?>
                                                <div class="col-sm-12 col-lg-3">
                                                    <span><?= $date ?></span><br> 
                                                    <span class="text-secondary ms-2">⤷ <?= PROXY_DATABASE_NAME ?>: <?= $databases[PROXY_DATABASE_NAME] ?></span><br>
                                                    <span class="text-secondary ms-2">⤷ <?= USAGE_DATABASE_NAME ?>: <?= $databases[USAGE_DATABASE_NAME] ?></span><br>
                                                </div>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="card border-default mb-3">
            <div class="card-header">Logging</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <tbody>
                            <tr>
                                <td class="w-25">
                                    Rotation size<br>
                                    <span class="text-small">Default: <?= LOG_ROTATE_SIZE ?></span>
                                </td>
                                <td><input type="number" class="form-control d-inline-block w-25" id="setting-logRotationSize" value="<?= LOG_ROTATE_SIZE ?>">MiB</td>
                            </tr>
                            <tr>
                                <td>
                                    Log retention length<br>
                                    <span class="text-small">Default: <?= LOG_AGE ?></span>
                                </td>
                                <td><input type="number" class="form-control d-inline-block w-25" id="setting-logRetentionLength" value="<?= LOG_AGE ?>"> days</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="card border-default mb-3">
            <div class="card-header">Cache</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <tbody>
                            <tr>
                                <td class="w-25">Memcache enabled</td>
                                <td><?= !empty($cacheStats) ? 'Yes (' . byteConversion($cacheStats['127.0.0.1:11211']['bytes']) . ', ' . $cacheStats['127.0.0.1:11211']['curr_items'] . ' items)' : 'No' ?></td>
                            </tr>
                            <tr>
                                <td>Cache</td>
                                <td>
                                    <i class="far fa-trash-alt text-danger" title="Bust cache" style="cursor:pointer;" onclick="bustCache('<?= REQUEST_COUNTER_KEY ?>')"></i> Key: <?= REQUEST_COUNTER_KEY ?>, Utilized: <?= $cache->get(REQUEST_COUNTER_KEY) ? 'Yes' : 'No' ?><br>
                                    <i class="far fa-trash-alt text-danger" title="Bust cache" style="cursor:pointer;" onclick="bustCache('<?= STARRS_TABLE_CACHE_KEY ?>')"></i> Key: <?= STARRS_TABLE_CACHE_KEY ?>, Utilized: <?= $cache->get(STARRS_TABLE_CACHE_KEY) ? 'Yes' : 'No' ?><br>
                                    <i class="far fa-trash-alt text-danger" title="Bust cache" style="cursor:pointer;" onclick="bustCache('<?= APPS_TABLE_CACHE_KEY ?>')"></i> Key: <?= APPS_TABLE_CACHE_KEY ?>, Utilized: <?= $cache->get(APPS_TABLE_CACHE_KEY) ? 'Yes' : 'No' ?><br>
                                    <?php
                                    foreach (StarrApps::LIST as $starr) {
                                        $cacheKey = sprintf(STARR_ENDPOINT_LIST_KEY, $starr);
                                        ?>
                                        <i class="far fa-trash-alt text-danger" title="Bust cache" style="cursor:pointer;" onclick="bustCache('<?= $cacheKey ?>')"></i> Key: <?= $cacheKey ?>, Utilized: <?= $cache->get($cacheKey) ? 'Yes' : 'No' ?><br>
                                        <?php
                                    }
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
