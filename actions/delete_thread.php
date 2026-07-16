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

$threadId = isset($_POST['thread_id']) ? (int) $_POST['thread_id'] : 0;
$referer  = $_SERVER['HTTP_REFERER'] ?? '?page=index';

if ($threadId < 1) {
    die('Invalid thread.');
}

$pdo  = getDbConnection();
$stmt = $pdo->prepare('DELETE FROM threads WHERE id = ?');
$stmt->execute([$threadId]);

header('Location: ' . $referer);
exit;
