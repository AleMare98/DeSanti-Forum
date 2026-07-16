<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Invalid request.'], 405);
    }
    header('Location: ?page=index');
    exit;
}

if (!verifyCsrfToken()) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Invalid CSRF token.'], 403);
    }
    die('Invalid CSRF token.');
}

$threadId = isset($_POST['thread_id']) ? (int) $_POST['thread_id'] : 0;
$referer  = $_SERVER['HTTP_REFERER'] ?? '?page=index';

if ($threadId < 1) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Invalid thread.'], 422);
    }
    die('Invalid thread.');
}

$pdo  = getDbConnection();
$stmt = $pdo->prepare('DELETE FROM threads WHERE id = ?');
$stmt->execute([$threadId]);

if ($isAjax) {
    jsonResponse(['success' => true]);
}

header('Location: ' . $referer);
exit;
