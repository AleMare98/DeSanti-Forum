<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=login');
    exit;
}

if (!verifyCsrfToken()) {
    die('Invalid CSRF token.');
}

$username = sanitizePost('username');
$password = sanitizePost('password');

$pdo  = getDbConnection();
$stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    loginUser((int) $user['id'], $user['username'], $user['role']);
    header('Location: ?page=index');
    exit;
}

die('Invalid username or password.');
