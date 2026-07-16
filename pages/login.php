<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

if (isLoggedIn()) {
    header('Location: ?page=index');
    exit;
}

$pageTitle = 'Login';
require_once __DIR__ . '/../includes/header.php';
?>

    <h1>Login</h1>

    <form action="actions/login.php" method="POST" class="auth-form">
        <?php echo csrfField(); ?>
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Login</button>
    </form>

    <p>Don't have an account? <a href="?page=register">Register here</a>.</p>

<?php
require_once __DIR__ . '/../includes/footer.php';
