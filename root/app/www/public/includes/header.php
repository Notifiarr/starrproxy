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
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <title><?= APP_NAME ?></title>
        <meta content="width=device-width, initial-scale=1.0" name="viewport">

        <!-- Favicon -->
        <link href="images/favicon.ico" rel="icon">

        <!-- Google Web Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Roboto:wght@500;700&display=swap" rel="stylesheet">

        <!-- Icon Font Stylesheet -->
        <link href="libraries/fontawesome/all.min.css" rel="stylesheet">
        <link href="libraries/bootstrap/bootstrap-icons.css" rel="stylesheet">

        <!-- Customized Bootstrap Stylesheet -->
        <link href="libraries/bootstrap/bootstrap.min.css" rel="stylesheet">
        <link href="themes/base.css?t=<?= filemtime('themes/base.css') ?>" rel="stylesheet">
        <link href="themes/<?= USER_THEME ?>.min.css?t=<?= filemtime('themes/' . USER_THEME . '.min.css') ?>" rel="stylesheet">

        <!-- Select2 Stylesheet -->
        <link href="libraries/select2/select2.min.css" rel="stylesheet">
        <link href="libraries/select2/select2-bootstrap-5-theme.min.css" rel="stylesheet">

        <!-- Internal Stylesheet -->
        <link href="css/style.css" rel="stylesheet">
    </head>

    <body data-bs-theme="<?= USER_THEME_MODE ?>">
        <nav class="navbar navbar-expand-lg bg-dark fixed-top" data-bs-theme="dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="?page=home">
                    <p><span>starr</span>proxy</p>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarColor02" aria-controls="navbarColor02" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarColor02">
                    <ul class="navbar-nav">
                        <?php
                        foreach (StarrApps::LIST as $index => $starrApp) {
                            $active        = $app == $starrApp;
                            $starrAppCount = 0;

                            foreach ($starrsTable as $starrDbApp) {
                                $rowApp = $starr->getStarrInterfaceNameFromId($starrDbApp['starr']);
                                if ($rowApp == $starrApp) {
                                    $starrAppCount++;
                                }
                            }

                            ?>
                            <li class="nav-item <?= !$settingsTable['uiHeader' . ucfirst($starrApp)] ? 'd-none' : '' ?>">
                                <a class="nav-link <?= $active ? 'active' : '' ?>" href="/?app=<?= $starrApp ?>"><img src="images/logos/<?= $starrApp ?>.png" style="height: 18px;"> <span><?= ucfirst($starrApp) ?> <small><?= $starrAppCount ?></small></span></a>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                    <ul class="navbar-nav navbar-icons ms-auto">
                        <li class="nav-item <?= !$settingsTable['uiHeaderNotifications'] ? 'd-none' : '' ?>">
                            <a class="nav-link <?= $page == 'notifications' ? 'active' : '' ?>" href="/?page=notifications" title="Notifications"><i class="fas fa-comment-dots"></i>
                                <span class="d-md-none">Notifications</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'templates' ? 'active' : '' ?>" href="/?page=templates" title="Templates"><i class="far fa-file-alt"></i>
                                <span class="d-md-none">Templates</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'logs' ? 'active' : '' ?>" href="/?page=logs" title="Logs"><i class="fas fa-clipboard-list"></i>
                                <span class="d-md-none">Logs</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'settings' ? 'active' : '' ?>" href="/?page=settings" title="Settings"><i class="fas fa-cog"></i>
                                <span class="d-md-none">Settings</span>
                            </a>
                        </li>
                        <li class="nav-item <?= !$settingsTable['uiHeaderHelp'] ? 'd-none' : '' ?>">
                            <a class="nav-link <?= $page == 'help' ? 'active' : '' ?>" href="/?page=help" title="Help"><i class="far fa-question-circle"></i>
                                <span class="d-md-none">Help</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="theme-menu" aria-expanded="false" data-bs-toggle="dropdown" data-bs-display="static" aria-label="Toggle theme" style="transform: translateY(3px);">
                                <i class="fas fa-cloud-sun"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light" onclick="swapLightDark('light')">
                                        <i class="bi bi-sun-fill"></i><span class="ms-2">Light</span>
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark" onclick="swapLightDark('dark')">
                                        <i class="bi bi-moon-stars-fill"></i><span class="ms-2">Dark</span>
                                    </button>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="w-100 d-inline-flex">
