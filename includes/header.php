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
        <div class="nav-links">
            <?php if (isLoggedIn()): ?>
                <span class="nav-user">Logged in as <strong><?php echo escapeHtml(getCurrentUsername()); ?></strong></span>
                <?php if (isAdmin()): ?>
                    <a href="?page=admin">Admin Panel</a>
                <?php endif; ?>
                <a href="actions/logout.php">Logout</a>
            <?php else: ?>
                <a href="?page=login">Login</a>
                <a href="?page=register">Register</a>
            <?php endif; ?>
        </div>
    </nav>
    <main class="container">
