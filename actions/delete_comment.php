<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=index');
    exit;
}

if (!verifyCsrfToken()) {
    die('Invalid CSRF token.');
}

$commentId = isset($_POST['comment_id']) ? (int) $_POST['comment_id'] : 0;
$threadId  = isset($_POST['thread_id']) ? (int) $_POST['thread_id'] : 0;

if ($commentId < 1) {
    die('Invalid comment.');
}

$pdo  = getDbConnection();
$stmt = $pdo->prepare('DELETE FROM comments WHERE id = ?');
$stmt->execute([$commentId]);

header('Location: ?page=thread&id=' . $threadId);
exit;
