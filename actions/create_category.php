<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=admin');
    exit;
}

if (!verifyCsrfToken()) {
    die('Invalid CSRF token.');
}

$name = sanitizePost('name');

if (strlen($name) < 1 || strlen($name) > 100) {
    die('Category name must be between 1 and 100 characters.');
}

$pdo  = getDbConnection();
$stmt = $pdo->prepare('INSERT INTO categories (name, created_by) VALUES (?, ?)');
$stmt->execute([$name, getCurrentUserId()]);

header('Location: ?page=admin&msg=created');
exit;
