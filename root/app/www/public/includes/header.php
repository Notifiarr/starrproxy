<?php

/*
----------------------------------
 ------  Created: 101124   ------
 ------  Austin Best	   ------
----------------------------------
*/

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

        <!-- Internal Stylesheet -->
        <link href="css/style.css" rel="stylesheet">
    </head>
    <body>
        <nav class="navbar navbar-expand-lg bg-dark" data-bs-theme="dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="?page=home"><img src="images/logo-32.png"> <?= APP_NAME ?></a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarColor02" aria-controls="navbarColor02" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarColor02">
                    <ul class="navbar-nav">
                        <?php 
                        foreach ($starrApps as $index => $starrApp) {
                            $active = $app == $starrApp;

                            ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $active ? 'active' : '' ?>" href="/?app=<?= $starrApp ?>"><img src="images/logos/<?= $starrApp ?>.png" style="height: 18px;"> <span class="me-3"><?= ucfirst($starrApp) ?> - <?= is_array($settings[$starrApp]) ? count($settings[$starrApp]) : 0 ?></span></a>
                            </li>
                            <?php
                        }
                        ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'templates' ? 'active' : '' ?>" href="/?page=templates"><i class="far fa-file-alt"></i> <span class="me-3">Templates</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'help' ? 'active' : '' ?>" href="/?page=help"><i class="far fa-question-circle"></i> <span class="me-3">Help</span></a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="w-100 d-inline-flex">
