<?php

/*
----------------------------------
 ------  Created: 102124   ------
 ------  Austin Best	   ------
----------------------------------
*/

if (!$_SESSION) {
    session_start();
}

if (!$_SESSION['IN_UI']) {
    exit('Invalid session, refresh the page');
}

$logList = getLogs();
?>

<div class="row p3">
    <div class="col-sm-3">
        <?php
        $index = 0;
        foreach ($logList['system'] as $group => $groupLogs) {
            ?><h4 class="mt-2 fst-italic"><?= rtrim($group, '/') ?></h4><?php
            foreach ($groupLogs as $log) {
                $index++;
                ?>
                <ul style="margin-bottom: 0px; padding-bottom: 0px;">
                    <li>
                        <i class="far fa-trash-alt text-danger" title="Delete access log" style="cursor:pointer;" onclick="deleteLog('<?= $log ?>')"></i> 
                        <span id="app-log-<?= $index ?>" style="cursor:pointer;" onclick="viewLog('<?= $log ?>', <?= $index ?>)"><?= basename($log) ?> (<?= date('m/d', filemtime($log)) ?>)</span>
                        <div style="float: right;"><?= byteConversion(filesize($log)) ?></div>
                    </li>
                </ul>
                <?php
            }
        }

        foreach ($logList as $app => $logs) {
            if ($app == 'system') {
                continue;
            }

            ?><h4 class="mt-2"><?= $app ?></h4><?php
            foreach ($logs as $log) {
                $index++;
                ?>
                <ul style="margin-bottom: 0px; padding-bottom: 0px;">
                    <li>
                        <i class="far fa-trash-alt text-danger" title="Delete access log" style="cursor:pointer;" onclick="deleteLog('<?= $log ?>')"></i> 
                        <span id="app-log-<?= $index ?>" style="cursor:pointer;" onclick="viewLog('<?= $log ?>', <?= $index ?>)"><?= str_replace('access_', '', basename($log)) ?> (<?= date('m/d', filemtime($log)) ?>)</span>
                        <div style="float: right;"><?= byteConversion(filesize($log)) ?></div>
                    </li>
                </ul>
                <?php
            }
        }
        ?>
    </div>
    <div class="col-sm-9">
        <div id="log-viewer" class="mt-3"></div>
    </div>
</div>
