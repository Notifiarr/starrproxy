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

?>

<div class="row p3">
    <div class="col-sm-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted small">Auto-refresh: <span id="auto-refresh-status">3s</span></span>
            <button id="auto-refresh-toggle" class="btn btn-sm btn-outline-success border-light" onclick="toggleLogAutoRefresh()" title="Toggle auto-refresh">
                <i class="fas fa-sync"></i>
            </button>
        </div>
        <div id="log-sidebar">
            <?php renderLogList(); ?>
        </div>
    </div>
    <div class="col-sm-9">
        <div id="log-viewer" class="mt-3"></div>
    </div>
</div>
