<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

if (isLoggedIn()) {
    header('Location: ?page=index');
    exit;
}

$pageTitle = 'Register';
require_once __DIR__ . '/../includes/header.php';
?>

    <h1>Register</h1>

    <form action="actions/register.php" method="POST" class="auth-form">
        <?php echo csrfField(); ?>
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" minlength="3" maxlength="50" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" minlength="6" required>
        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="?page=login">Login here</a>.</p>

<?php
require_once __DIR__ . '/../includes/footer.php';
