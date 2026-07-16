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
    header('Location: ?page=admin');
    exit;
}

if (!verifyCsrfToken()) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Invalid CSRF token.'], 403);
    }
    die('Invalid CSRF token.');
}

$name = sanitizePost('name');

if (strlen($name) < 1 || strlen($name) > 100) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Category name must be between 1 and 100 characters.'], 422);
    }
    die('Category name must be between 1 and 100 characters.');
}

$pdo  = getDbConnection();
$stmt = $pdo->prepare('INSERT INTO categories (name, created_by) VALUES (?, ?)');
$stmt->execute([$name, getCurrentUserId()]);

$categoryId = $pdo->lastInsertId();

if ($isAjax) {
    jsonResponse([
        'success'  => true,
        'category' => [
            'id'         => (int) $categoryId,
            'name'       => $name,
            'created_at' => date('Y-m-d H:i:s'),
            'username'   => getCurrentUsername(),
        ],
    ]);
}

header('Location: ?page=admin&msg=created');
exit;
