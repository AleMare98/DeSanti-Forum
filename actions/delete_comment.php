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

$commentId = isset($_POST['comment_id']) ? (int) $_POST['comment_id'] : 0;
$threadId  = isset($_POST['thread_id']) ? (int) $_POST['thread_id'] : 0;

if ($commentId < 1) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Invalid comment.'], 422);
    }
    die('Invalid comment.');
}

$pdo  = getDbConnection();
$stmt = $pdo->prepare('DELETE FROM comments WHERE id = ?');
$stmt->execute([$commentId]);

if ($isAjax) {
    jsonResponse(['success' => true]);
}

header('Location: ?page=thread&id=' . $threadId);
exit;
