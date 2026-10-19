<?php
require_once __DIR__ . '/userTracking.php';
require_once __DIR__ . '/theme_cookies.php';

$theme = get_theme();
$fullTitle = !empty($page_title) ? $page_title . ' · UsedCars' : 'UsedCars - Αγγελίες Αυτοκινήτων';
?>
<!doctype html>
<html lang="el" data-theme="<?= h($theme) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Αγγελίες μεταχειρισμένων αυτοκινήτων: αναζήτηση, φίλτρα και καταχώριση αγγελίας με φωτογραφίες.">
    <title><?= h($fullTitle) ?></title>
    <link rel="stylesheet" href="css/style.css?v=2">
</head>

<body>

    <header class="main-header">
        <div class="header-container">
            <a href="index.php" class="site-title">UsedCars</a>
            <nav class="main-nav">
                <a href="index.php">Αρχική</a>

                <a href="switch_theme.php" class="theme">
                    <?= $theme === 'dark' ? 'Light Mode' : 'Dark Mode' ?>
                </a>

                <?php if (is_logged_in()): ?>
                    <a href="vehicles.php">Τα Οχήματά μου</a>
                    <span class="nav-user"><?= h($_SESSION['username'] ?? '') ?></span>
                    <form method="post" action="logout.php" class="nav-logout">
                        <?= csrf_field() ?>
                        <button type="submit">Αποσύνδεση</button>
                    </form>
                <?php else: ?>
                    <a href="register.php">Εγγραφή</a>
                    <a href="login.php">Σύνδεση</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <!-- Οι σελίδες με δικό τους layout (αρχική, αναζήτηση, αγγελία) ορίζουν $no_container = true
         ώστε να μην μπαίνουν μέσα στην "κάρτα" .container -->
    <main class="<?= isset($no_container) ? '' : 'container' ?>">
