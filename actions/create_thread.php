<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=index');
    exit;
}

if (!verifyCsrfToken()) {
    die('Invalid CSRF token.');
}

$title      = sanitizePost('title');
$content    = sanitizePost('content');
$categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;

if (strlen($title) < 1 || strlen($title) > 255) {
    die('Title must be between 1 and 255 characters.');
}

if (strlen($content) < 1) {
    die('Content cannot be empty.');
}

if ($categoryId < 1) {
    die('Invalid category.');
}

$pdo  = getDbConnection();
$stmt = $pdo->prepare('INSERT INTO threads (title, content, user_id, category_id) VALUES (?, ?, ?, ?)');
$stmt->execute([$title, $content, getCurrentUserId(), $categoryId]);

$threadId = $pdo->lastInsertId();
header('Location: ?page=thread&id=' . $threadId);
exit;
