<?php

/*
----------------------------------
 ------  Created: 110624   ------
 ------  Austin Best	   ------
----------------------------------
*/

if (!$_SESSION) {
    session_start();
}

if (!$_SESSION['IN_UI']) {
    exit('Invalid session, refresh the page');
}

$notificationPlatformTable = $proxyDb->getNotificationPlatforms();
$notificationTriggersTable = $proxyDb->getNotificationTriggers();
$notificationLinkTable     = $proxyDb->getNotificationLinks();

?>
<div class="row">
    <div class="col-sm-12">
        <div class="card mb-3">
            <div class="card-header">Platforms</div>
            <div class="card-body">
                <div class="row" style="place-content: center; gap: 12px;">
                    <?php
                    foreach ($notificationPlatformTable as $notificationPlatform) {
                        $add = $notificationPlatform['parameters'] ? '<i class="fas fa-plus-circle text-light ms-2" style="cursor: pointer;" onclick="openNotificationTriggers(' . $notificationPlatform['id'] . ')"></i>' : '<span class="small-text fst-italic ms-3">Coming soon!</span>';

                        ?>
                        <div class="col-sm-3 card">
                            <div class="container">
                                <div class="text-center">
                                    <p class="p-3 platform-text" style="margin: auto; font-size: 22px;"><?= strtoupper($notificationPlatform['platform']) . $add ?></p>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="card border-primary mb-3">
            <div class="card-header">Configured senders</div>
            <div class="card-body">
                <div class="row" style="place-content: center; gap: 12px;">
                    <?php if (!$notificationLinkTable) { ?>
                        <div class="container">
                            <div class="rounded p-4">
                                Notifications have not been setup yet, click the plus icon above to set them up.
                            </div>
                        </div>
                    <?php } else { ?>
                        <?php
                        foreach ($notificationLinkTable as $notificationLink) {
                            ?>
                            <div class="col-sm-4 rounded card">
                                <div class="container">
                                    <div class="rounded text-center p-2">
                                        <h4 class="pt-2 px-3 platform-text">
                                            <?= strtoupper($notificationLink['name']) ?>
                                            <i class="fas fa-tools text-light ms-2" style="cursor: pointer;" title="Update this sender triggers" onclick="openNotificationTriggers(<?= $notificationLink['platform_id'] ?>, <?= $notificationLink['id'] ?>)"></i>
                                            <i class="far fa-bell text-light ms-1" style="cursor: pointer;" title="Send test notification" onclick="testNotify(<?= $notificationLink['id'] ?>, 'test')"></i>
                                        </h4>
                                        <div class="row text-left">
                                            <?php
                                            if (!$notificationLink['trigger_ids']) {
                                                ?>You have not configured any triggers for this notification<?php
                                            } else {
                                                $triggerIds      = $notificationLink['trigger_ids'] ? json_decode($notificationLink['trigger_ids'], true) : [];
                                                $enabledTriggers = [];
                                                foreach ($triggerIds as $triggerId) {
                                                    $trigger           = $notifications->getNotificationTriggerNameFromId($triggerId, $notificationTriggersTable);
                                                    $enabledTriggers[] = $trigger;
                                                }

                                                echo '<div><span class="text-success d-inline-block">Enabled:</span> ' . ($enabledTriggers ? implode(', ', $enabledTriggers) : 'No triggers enabled') . '</div>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
