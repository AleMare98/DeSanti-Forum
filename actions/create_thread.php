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

$title      = sanitizePost('title');
$content    = sanitizePost('content');
$categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;

if (strlen($title) < 1 || strlen($title) > 255) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Title must be between 1 and 255 characters.'], 422);
    }
    die('Title must be between 1 and 255 characters.');
}

if (strlen($content) < 1) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Content cannot be empty.'], 422);
    }
    die('Content cannot be empty.');
}

if ($categoryId < 1) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Invalid category.'], 422);
    }
    die('Invalid category.');
}

$pdo  = getDbConnection();
$stmt = $pdo->prepare('INSERT INTO threads (title, content, user_id, category_id) VALUES (?, ?, ?, ?)');
$stmt->execute([$title, $content, getCurrentUserId(), $categoryId]);

$threadId = $pdo->lastInsertId();

if ($isAjax) {
    jsonResponse([
        'success' => true,
        'thread'  => [
            'id'         => (int) $threadId,
            'title'      => $title,
            'username'   => getCurrentUsername(),
            'created_at' => date('Y-m-d H:i:s'),
        ],
    ]);
}

header('Location: ?page=thread&id=' . $threadId);
exit;
