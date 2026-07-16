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

requireLogin();

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

$content  = sanitizePost('content');
$threadId = isset($_POST['thread_id']) ? (int) $_POST['thread_id'] : 0;

if (strlen($content) < 1) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Comment cannot be empty.'], 422);
    }
    die('Comment cannot be empty.');
}

if ($threadId < 1) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Invalid thread.'], 422);
    }
    die('Invalid thread.');
}

$pdo  = getDbConnection();
$stmt = $pdo->prepare('INSERT INTO comments (content, user_id, thread_id) VALUES (?, ?, ?)');
$stmt->execute([$content, getCurrentUserId(), $threadId]);

$commentId = $pdo->lastInsertId();

if ($isAjax) {
    jsonResponse([
        'success' => true,
        'comment' => [
            'id'         => (int) $commentId,
            'content'    => $content,
            'username'   => getCurrentUsername(),
            'created_at' => date('Y-m-d H:i:s'),
        ],
    ]);
}

header('Location: ?page=thread&id=' . $threadId);
exit;
