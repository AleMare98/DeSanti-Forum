<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Login richiesto.']);
    exit;
}

$categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
if ($categoryId < 1) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Canale non valido.']);
    exit;
}

$pdo = getDbConnection();
$stmt = $pdo->prepare(
    'SELECT cm.id, cm.content, cm.created_at, u.username
     FROM chat_messages cm
     JOIN users u ON u.id = cm.user_id
     WHERE cm.category_id = ?
     ORDER BY cm.created_at DESC, cm.id DESC
     LIMIT 50'
);
$stmt->execute([$categoryId]);
$messages = array_reverse($stmt->fetchAll());

echo json_encode(['success' => true, 'messages' => $messages]);
