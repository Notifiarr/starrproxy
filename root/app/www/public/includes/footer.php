<?php

/*
----------------------------------
 ------  Created: 101124   ------
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
</div>
<footer id="footer" class="footer fixed-bottom bg-dark">
    <div class="container-fluid bg-dark">
        <div id="footer-content" class="row bg-dark">
            <div id="footer-branch" class="col-4 text-start">
                <a href="https://github.com/Notifiarr/starrproxy" title="Visit the <?= APP_NAME ?> github" target="_blank"><i class="fab fa-github fa-lg"></i></a>
                <a href="https://notifiarr.com/discord" title="Visit the <?= APP_NAME ?> github" target="_blank"><i class="fab fa-discord fa-lg"></i></a>
                <span><?= gitVersion(true) ?></span>
            </div>
        </div>
    </div>
</footer>

<!-- Toast container -->
<div class="toast-container bottom-0 end-0 p-3" style="z-index: 10001 !important; position: fixed;"></div>

<!-- Generic modal -->
<div id="dialog-modal-container">
    <div class="modal fade" id="dialog-modal" style="z-index: 9999 !important;" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" data-scrollbar="true" data-wheel-propagation="true"></div>
                <div class="modal-footer"></div>
            </div>
        </div>
    </div>
</div>

<!-- Loading modal -->
<div class="modal fade" id="loading-modal" style="z-index: 9999 !important;" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Loading</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>
                <div class="spinner-border text-primary" style="margin-right: 1em;"></div>
                I'm gathering everything needed to complete the request, give me just a moment...
                </p>
            </div>
            <div class="modal-footer">&nbsp;</div>
        </div>
    </div>
</div>

<!-- Javascript Libraries -->
<script src="libraries/jquery/jquery-3.4.1.min.js"></script>
<script src="libraries/jquery/jquery-ui-1.13.2.min.js"></script>
<script src="libraries/bootstrap/bootstrap.bundle.min.js"></script>
<script src="libraries/select2/select2.min.js"></script>

<!-- Internal functions -->
<?php
$dir = opendir('js');
while ($file = readdir($dir)) {
    if (!str_contains($file, '.js')) {
        continue;
    }

    ?>
    <script src="js/<?= $file ?>?t=<?= filemtime('js/' . $file) ?>"></script>
    <?php
}
closedir($dir);
?>
</body>

</html>
