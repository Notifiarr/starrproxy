<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

function logger($logfile, $logParameters)
{
    global $settingsTable, $LOG_ROTATE_SIZE;

    $backtrace  = debug_backtrace();
    $log        = [];
    $date       = date(sprintf('Y-m-d\TH:i:s%s', substr(microtime(), 1, 8)));
    $log[]      = $date;

    if ($logParameters['req']) {
        $log[] = '[' . $logParameters['req'] . ']';
    }

    if ($logParameters['starr']) {
        $log[] = '[' . $logParameters['starr'] . ']';
    }

    if ($logParameters['text']) {
        $log[] = $logParameters['text'];

        if ($logParameters['notificationCode']) {
            $log[] = 'notificationCode:' . $logParameters['notificationCode'] . ';';
        }
    } else {
        $log[] = 'ua:' . $_SERVER['HTTP_USER_AGENT'] . ';';
        if ($logParameters['apikey']) {
            $log[] = 'key:' . truncateMiddle($logParameters['apikey'], 20) . ';';
        }
        if ($logParameters['endpoint']) {
            $log[] = 'endpoint:' . $logParameters['endpoint'] . ';';
        }

        $log[] = 'method:' . strtolower($_SERVER['REQUEST_METHOD']) . ';';
        $log[] = 'proxyCode:' . $logParameters['proxyCode'] . ';';
    
        if ($logParameters['starrCode']) {
            $log[] = 'starrCode:' . $logParameters['starrCode'] . ';';
    
            if ($logParameters['starrCode'] != 200 && $logParameters['starrRequest']) {
                $log[] = 'starrResponse:' . json_encode($logParameters['starrRequest']);
            }
        }
    }

    $log[] = 'file:' . $backtrace[0]['file'] . ';';
    $log[] = 'line:' . $backtrace[0]['line'] . ';';

    file_put_contents($logfile, implode(' ', $log) . "\n", FILE_APPEND);

    $rotateSize = ($settingsTable['logRotationSize'] ?: $LOG_ROTATE_SIZE) * pow(1024, 2);
    if (filesize($logfile) >= $rotateSize) {
        $rotated = str_replace('.log', '_' . time() . '.log', $logfile);
        rename($logfile, $rotated);
    }
}

function getLogs()
{
    $folders   = [
                    LOGS_PATH,
                    LOGS_PATH . 'system/',
                    LOGS_PATH . 'notifications/'
                ];

    $list = [];
    foreach ($folders as $folder) {
        if (is_dir($folder)) {
            $dir = opendir($folder);
            while ($log = readdir($dir)) {
                if ($log[0] == '.') {
                    continue;
                }
    
                if ($folder == LOGS_PATH) {
                    if (str_contains($log, '.log')) {
                        $logfile = str_replace('.log', '', $log);
                        list($access, $app, $date) = explode('_', $logfile);

                        if ($app && !is_numeric($app)) {
                            $list[$app][$logfile] = $folder . $logfile . '.log';
                            ksort($list[$app]);
                        } else {
                            $list['system']['access'][$logfile] = $folder . $logfile . '.log';
                            ksort($list['system']['access']);
                        }
                    }
                } else {
                    $level2 = opendir($folder);
                    while ($level2Log = readdir($level2)) {
                        if ($level2Log[0] == '.') {
                            continue;
                        }

                        $logfile = str_replace('.log', '', $level2Log);
                        $group = str_replace(LOGS_PATH, '', $folder);
                        if (!is_array($list['system'][$group]) || !in_array($folder . $logfile . '.log', $list['system'][$group])) {
                            $list['system'][$group][$logfile] = $folder . $logfile . '.log';
                            ksort($list['system'][$group]);
                        }
                    }
                    closedir($level2);
                }
            }
            closedir($dir);
        }
    }

    ksort($list['system']);
    ksort($list);
    return $list;
}

function getLog($logfile, $page = 1, $app = false)
{
    global $starr, $shell;

    $page   = $page <= 1 ? 1 : $page;
    $start  = $page == 1 ? 0 : $page * LOG_LINES_PER_PAGE - LOG_LINES_PER_PAGE;
    $end    = $start + LOG_LINES_PER_PAGE;

    $starr      = $starr ?:new Starr();
    list($logLines, $file) = explode(' ', $shell->exec('wc -l "' . $logfile . '"'));
    $logLines   = intval(trim($logLines));
    $cmd        = $app ? 'tail -' . LOG_LINES_PER_PAGE . ' "' . $logfile . '"' : 'awk -vs="' . $start . '" -ve="' . $end . '" \'NR>=s&&NR<=e\' "' . $logfile . '"';
    $file       = $shell->exec($cmd);
    $lines      = explode("\n", $file);
    rsort($lines);

    $pages = ceil($logLines / LOG_LINES_PER_PAGE);
    $pages = $pages > 1 ? $pages : 1;

    ?>
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" data-bs-toggle="tab" href="#access" aria-selected="true" role="tab">Access log (<?= number_format($logLines) ?> lines)</a>
        </li>
        <?php if ($app) { ?>
        <li class="nav-item" role="presentation">
            <a class="nav-link" data-bs-toggle="tab" href="#endpoints" aria-selected="false" tabindex="-1" role="tab">Endpoint usage</a>
        </li>
        <?php } ?>
        <?php if (!$app) { ?>
        <li class="ms-5">
            <?php if ($page != 1) { ?>
                <?php if ($pages >= 2) { ?>
                <button class="btn btn-sm btn-info" onclick="viewLog('<?= $logfile ?>', '<?= $_POST['index'] ?>', 1)"><i class="fas fa-fast-backward"></i> Start</button>
                <?php } ?>
                <button class="btn btn-sm btn-info" onclick="viewLog('<?= $logfile ?>', '<?= $_POST['index'] ?>', <?= $page - 1 ?>)"><i class="fas fa-backward"></i> Back</button>
            <?php } ?>
            <?php if ($page != $pages) { ?>
                <button class="btn btn-sm btn-info" onclick="viewLog('<?= $logfile ?>', '<?= $_POST['index'] ?>', <?= $page + 1 ?>)"><i class="fas fa-forward"></i> Next</button>
                <?php if ($pages >= 2) { ?>
                <button class="btn btn-sm btn-info" onclick="viewLog('<?= $logfile ?>', '<?= $_POST['index'] ?>', <?= $pages ?>)"><i class="fas fa-fast-forward"></i> End</button>
                <?php } ?>
            <?php } ?>
            <span class="ms-3">Page: <?= $page ?>/<?= $pages ?></span>
        </li>
        <?php } else { ?>
            <span class="ms-3">Newest <?= LOG_LINES_PER_PAGE ?> filtered lines</span>
        <?php } ?>
    </ul>
    <div id="myTabContent" class="tab-content">
        <div class="tab-pane fade show active" id="access" role="tabpanel">
            <table class="table table-bordered table-hover">
                <?php
                if ($lines) {
                    $endpointUsage  = [];
                    foreach ($lines as $line) {
                        $error = '';
                        if (str_contains_any($line, ['endpoint map matched'])) {
                            $error = '<span class="text-warning">[WARNING]</span> ';
                        }

                        if (str_contains_any($line, ['Code:3', 'Code:4', 'Code:5'])) {
                            $error = '<span class="text-danger">[ERROR]</span> ';
                        }

                        if (str_contains($line, 'getAppFromProxiedKey')) {
                            $error = '<span class="text-info">[VERIFICATION]</span> ';
                        }

                        if ($app && !str_contains($line, 'key:' . $_POST['key'])) {
                            continue;
                        }

                        if (!str_contains($logfile, 'migrations')) {
                            $line = htmlspecialchars($line);
                        }

                        $line = str_replace('key:' . $_POST['key'] . ';', '<span class="text-warning">key:' . $_POST['key'] . ';</span>', $line);

                        preg_match('/endpoint:(.*);/U', $line, $endpointMatch);
                        preg_match('/method:(.*);/U', $line, $methodMatch);
                        if ($endpointMatch[1]) {
                            $endpoint = rtrim($endpointMatch[1], '/');
                            $endpointUsage[$endpoint][$methodMatch[1]]++;
                        }

                        ?><tr><td><?= $error . $line ?></td></tr><?php
                    }
                } else {
                    ?><tr><td>No log data found.</td></tr><?php   
                }
                ?></table>
        </div>
        <?php 
        if ($app) {
            $proxiedApp = $starr->getAppFromProxiedKey($_POST['key'], true);
            $starrApp   = $starr->getStarrInterfaceNameFromId($proxiedApp['starrAppDetails']['starr']);

            ?>
            <div class="tab-pane fade" id="endpoints" role="tabpanel">
                <h4>Endpoint usage <span class="text-small">(<?= count($endpointUsage) ?> endpoint<?= count($endpointUsage) == 1 ? '' : 's' ?>)</span></h4>
                <?php
                foreach ($endpointUsage as $endpoint => $methods) {
                    $isAllowedEndpoint  = $starr->isAllowedEndpoint($starrApp, $proxiedApp['access'], $endpoint);
                    $starrEndpoint      = $isAllowedEndpoint['starrEndpoint'];
                    $isAllowed          = $isAllowedEndpoint['allowed'];

                    foreach ($methods as $method => $usage) {
                        $isAllowedEndpointMethod    = $isAllowed ? $starr->isAllowedEndpointMethod($proxiedApp['access'], $starrEndpoint, $method) : false;
                        $hash                       = md5(($starrEndpoint && $starrEndpoint != $endpoint ? $starrEndpoint : $endpoint).$method);

                        ?>
                        <i id="disallowed-endpoint-<?= $hash ?>" class="far fa-times-circle text-danger" title="Disallowed endpoint, click to allow it" style="display: <?= !$isAllowedEndpointMethod ? 'inline-block' : 'none' ?>; cursor: pointer;" onclick="addEndpointAccess('<?= $app ?>', <?= $proxiedApp['proxiedAppDetails']['id'] ?>, '<?= $starrEndpoint ?: $endpoint ?>', '<?= $method ?>', '<?= $hash ?>')"></i> 
                        <i id="allowed-endpoint-<?= $hash ?>" class="far fa-check-circle text-success" title="Allowed endpoint, click to block it" style="display: <?= $isAllowedEndpointMethod ? 'inline-block' : 'none' ?>; cursor: pointer;" onclick="removeEndpointAccess('<?= $app ?>', <?= $proxiedApp['proxiedAppDetails']['id'] ?>, '<?= $starrEndpoint ?: $endpoint ?>', '<?= $method ?>', '<?= $hash ?>')"></i> 
                        [<?= strtoupper($method) ?>] <?= ($starrEndpoint && $starrEndpoint != $endpoint ? $starrEndpoint . ' → ' : '') . $endpoint . ': ' . number_format($usage) ?> hit<?= $usage == 1 ? '' : 's' ?><br>
                        <?php
                    }
                }
                ?>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
}
