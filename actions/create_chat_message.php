<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function chatJsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if (!isLoggedIn()) {
    chatJsonResponse(['success' => false, 'error' => 'Login richiesto.'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chatJsonResponse(['success' => false, 'error' => 'Richiesta non valida.'], 405);
}
if (!verifyCsrfToken()) {
    chatJsonResponse(['success' => false, 'error' => 'Token CSRF non valido.'], 403);
}

$categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
$content = sanitizePost('content');
if ($categoryId < 1 || strlen($content) < 1 || strlen($content) > 500) {
    chatJsonResponse(['success' => false, 'error' => 'Il messaggio deve contenere da 1 a 500 caratteri.'], 422);
}

$pdo = getDbConnection();
$categoryStmt = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
$categoryStmt->execute([$categoryId]);
if (!$categoryStmt->fetch()) {
    chatJsonResponse(['success' => false, 'error' => 'Canale non trovato.'], 404);
}

$stmt = $pdo->prepare('INSERT INTO chat_messages (category_id, user_id, content) VALUES (?, ?, ?)');
$stmt->execute([$categoryId, getCurrentUserId(), $content]);

if ($isAjax) {
    chatJsonResponse([
        'success' => true,
        'message' => [
            'id' => (int) $pdo->lastInsertId(),
            'content' => $content,
            'username' => getCurrentUsername(),
            'created_at' => date('Y-m-d H:i:s'),
        ],
    ]);
}

header('Location: ../index.php?page=index');
exit;
