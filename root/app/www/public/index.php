<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

error_reporting(E_ERROR | E_PARSE);

if (!$_SESSION) {
    session_start();
}

$_SESSION['IN_UI'] = true;

require 'loader.php';
require ABSOLUTE_PATH . 'includes/header.php';

?>
<div class="row w-100" style="margin: 1rem;">
    <?php
    switch (true) {
        case in_array($app, StarrApps::LIST):
            $requiredPage = 'starr';
            break;
        case $page:
            $requiredPage = $page;
            break;
        default:
            $requiredPage = 'home';
            break;
    }
    ?>
    <?php require ABSOLUTE_PATH . 'pages/' . $requiredPage . '.php'; ?>
</div>
<?php

require ABSOLUTE_PATH . 'includes/footer.php';
