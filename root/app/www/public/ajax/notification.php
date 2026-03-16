<?php

/*
----------------------------------
 ------  Created: 110924   ------
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

if ($_POST['m'] == 'openNotificationTriggers') {
    $_POST['linkId']            = $_POST['linkId'] ?: 0;
    $notificationPlatformTable  = $proxyDb->getNotificationPlatforms();
    $notificationTriggersTable  = $proxyDb->getNotificationTriggers();
    $notificationLinkTable      = $proxyDb->getNotificationLinks();
    $platformParameters         = json_decode($notificationPlatformTable[$_POST['platformId']]['parameters'], true);
    $platformName               = $notifications->getNotificationPlatformNameFromId($_POST['platformId'], $notificationPlatformTable);
    $linkRow                    = $notificationLinkTable[$_POST['linkId']];
    $existingTriggers           = $existingParameters = [];

    if ($linkRow) {
        $existingTriggers   = $linkRow['trigger_ids'] ? json_decode($linkRow['trigger_ids'], true) : [];
        $existingParameters = $linkRow['platform_parameters'] ? json_decode($linkRow['platform_parameters'], true) : [];
        $existingName       = $linkRow['name'];
    }

    ?>
    <div class="container">
        <h3><?= $platformName ?></h3>
        <div class="bg-primary rounded p-2">
            <table class="table table-no-squish table-bordered table-hover">
                <thead>
                    <tr>
                        <th><input type="checkbox" class="form-check-input" onchange="$('.notification-trigger').prop('checked', $(this).prop('checked'))"></th>
                        <th width="25%">Trigger</th>
                        <th>Description</th>
                        <th>Event</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($notificationTriggersTable as $notificationTrigger) {
                        ?>
                        <tr>
                            <td><input <?= in_array($notificationTrigger['id'], $existingTriggers) ? 'checked' : '' ?> type="checkbox" class="form-check-input notification-trigger" id="notificationTrigger-<?= $notificationTrigger['id'] ?>"></td>
                            <td><i class="far fa-bell text-light" style="cursor: pointer;" title="Send test notification" onclick="testNotify(<?= $linkRow['id'] ?>, '<?= $notificationTrigger['name'] ?>')"></i> <?= $notificationTrigger['label'] ?></td>
                            <td><?= $notificationTrigger['description'] ?></td>
                            <td><?= $notificationTrigger['event'] ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
            <table class="table table-no-squish table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Setting</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            Name <span class="ms-2 small-text text-danger">Required</span><br>
                            <span class="small-text">The name of this notification sender</span>
                        </td>
                        <td><input data-required="true" type="text" class="form-control" value="<?= $existingName ?: $platformName ?>" id="notificationPlatformParameter-name"></td>
                    </tr>
                    <?php
                    foreach ($platformParameters as $platformParameterField => $platformParameterData) {
                        ?>
                        <tr>
                            <td width="50%"><?= $platformParameterData['label'] . ($platformParameterData['required'] ? '<span class="ms-2 small-text text-danger">Required</span>' : '') ?><br><span class="small-text"><?= $platformParameterData['description'] ?></span></td>
                            <td>
                            <?php
                            switch ($platformParameterData['type']) {
                                case 'text':
                                    ?><input <?= $platformParameterData['required'] ? 'data-required="true"' : '' ?> type="text" id="notificationPlatformParameter-<?= $platformParameterField ?>" class="form-control" value="<?= $existingParameters[$platformParameterField] ?>"><?php
                                    break;
                            }
                            ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
            <hr>
            <div class="text-center w-100">
                <?php if ($linkRow) { ?>
                    <button class="btn btn-outline-success" onclick="saveNotification(<?= $_POST['platformId'] ?>, <?= $_POST['linkId'] ?>)">Save</button>
                    <button class="btn btn-outline-danger" onclick="deleteNotification(<?= $_POST['linkId'] ?>)">Remove</button>
                <?php } else { ?>
                    <button class="btn btn-outline-success" onclick="addNotification(<?= $_POST['platformId'] ?>)">Add</button>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php
}

if ($_POST['m'] == 'addNotification') {
    if (!$_POST['platformId']) {
        $error = 'Missing required platform id';
    }

    if (!$error) {
        $notificationPlatformTable  = $proxyDb->getNotificationPlatforms();
        $notificationTriggersTable  = $proxyDb->getNotificationTriggers();
        $notificationLinkTable      = $proxyDb->getNotificationLinks();
        $platformParameters         = json_decode($notificationPlatformTable[$_POST['platformId']]['parameters'], true);
        $platformName               = $notifications->getNotificationPlatformNameFromId($_POST['platformId'], $notificationPlatformTable);

        //-- CHECK FOR REQUIRED FIELDS
        foreach ($platformParameters as $platformParameterField => $platformParameterData) {
            if ($platformParameterData['required'] && !$_POST['notificationPlatformParameter-' . $platformParameterField]) {
                $error = 'Missing required platform field: ' . $platformParameterData['label'];
                break;
            }
        }

        if (!$error) {
            $triggerIds = $platformParameters = [];
            $senderName = $platformName;

            foreach ($_POST as $key => $val) {
                if (str_contains($key, 'notificationTrigger-') && $val) {
                    $triggerIds[] = str_replace('notificationTrigger-', '', $key);
                }

                if (str_contains($key, 'notificationPlatformParameter-')) {
                    $field = str_replace('notificationPlatformParameter-', '', $key);

                    if ($field != 'name') {
                        $platformParameters[$field] = $val;
                    } else {
                        $senderName = $val;
                    }
                }
            }

            $proxyDb->addNotificationLink($_POST['platformId'], $triggerIds, $platformParameters, $senderName);
        }
    }

    echo json_encode(['error' => $error]);
}

if ($_POST['m'] == 'saveNotification') {
    if (!$_POST['platformId']) {
        $error = 'Missing required platform id';
    }
    if (!$_POST['linkId']) {
        $error = 'Missing required link id';
    }

    if (!$error) {
        $notificationPlatformTable  = $proxyDb->getNotificationPlatforms();
        $notificationTriggersTable  = $proxyDb->getNotificationTriggers();
        $notificationLinkTable      = $proxyDb->getNotificationLinks();
        $platformParameters         = json_decode($notificationPlatformTable[$_POST['platformId']]['parameters'], true);
        $platformName               = $notifications->getNotificationPlatformNameFromId($_POST['platformId'], $notificationPlatformTable);

        //-- CHECK FOR REQUIRED FIELDS
        foreach ($platformParameters as $platformParameterField => $platformParameterData) {
            if ($platformParameterData['required'] && !$_POST['notificationPlatformParameter-' . $platformParameterField]) {
                $error = 'Missing required platform field: ' . $platformParameterData['label'];
                break;
            }
        }

        if (!$error) {
            $triggerIds = $platformParameters = [];
            $senderName = $platformName;

            foreach ($_POST as $key => $val) {
                if (str_contains($key, 'notificationTrigger-') && $val) {
                    $triggerIds[] = str_replace('notificationTrigger-', '', $key);
                }

                if (str_contains($key, 'notificationPlatformParameter-')) {
                    $field = str_replace('notificationPlatformParameter-', '', $key);

                    if ($field != 'name') {
                        $platformParameters[$field] = $val;
                    } else {
                        $senderName = $val;
                    }
                }
            }
            $proxyDb->updateNotificationLink($_POST['linkId'], $triggerIds, $platformParameters, $senderName);
        }
    }

    echo json_encode(['error' => $error]);
}

if ($_POST['m'] == 'deleteNotification') {
    $proxyDb->deleteNotificationLink($_POST['linkId']);
}

if ($_POST['m'] == 'testNotify') {
    $result = $error = '';
    $test = $notifications->sendTestNotification($_POST['linkId'], $_POST['name']);

    if ((is_array($test['result']) && $test['result']['code'] && $test['result']['code'] == 200) || ($test['code'] == 200)) {
        $result = 'Test notification sent';
    } else {
        $error = 'Failed to send test notification. ' . (is_array($test['result']) ? $test['result']['result'] : $test['result']);
    }

    echo json_encode(['error' => $error, 'result' => $result]);
}
