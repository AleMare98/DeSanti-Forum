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

$content  = sanitizePost('content');
$threadId = isset($_POST['thread_id']) ? (int) $_POST['thread_id'] : 0;

if (strlen($content) < 1) {
    die('Comment cannot be empty.');
}

if ($threadId < 1) {
    die('Invalid thread.');
}

$pdo  = getDbConnection();
$stmt = $pdo->prepare('INSERT INTO comments (content, user_id, thread_id) VALUES (?, ?, ?)');
$stmt->execute([$content, getCurrentUserId(), $threadId]);

header('Location: ?page=thread&id=' . $threadId);
exit;
