<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=register');
    exit;
}

if (!verifyCsrfToken()) {
    die('Invalid CSRF token.');
}

$username = sanitizePost('username');
$password = sanitizePost('password');

if (strlen($username) < 3 || strlen($username) > 50) {
    die('Username must be between 3 and 50 characters.');
}

if (strlen($password) < 6) {
    die('Password must be at least 6 characters.');
}

$pdo  = getDbConnection();
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$username]);

if ($stmt->fetch()) {
    die('Username already taken.');
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
$stmt->execute([$username, $hashedPassword, 'user']);

loginUser((int) $pdo->lastInsertId(), $username, 'user');

header('Location: ?page=index');
exit;
