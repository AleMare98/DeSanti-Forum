<?php startSession(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escapeHtml($pageTitle ?? 'Forum'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <a href="?page=index">Forum</a>
        </div>
        <div class="nav-links" id="nav-links">
            <?php if (isLoggedIn()): ?>
                <span class="nav-user" id="nav-logged-in">
                    Logged in as <strong id="nav-username"><?php echo escapeHtml(getCurrentUsername()); ?></strong>
                </span>
                <?php if (isAdmin()): ?>
                    <a href="?page=admin" id="nav-admin-link">Admin Panel</a>
                <?php endif; ?>
                <a href="actions/logout.php" id="nav-logout-link">Logout</a>
            <?php else: ?>
                <span id="nav-logged-out">
                    <a href="?page=login">Login</a>
                    <a href="?page=register">Register</a>
                </span>
            <?php endif; ?>
        </div>
    </nav>
    <main class="container">
