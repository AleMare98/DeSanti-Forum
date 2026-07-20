<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json');
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permesso negato.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Richiesta non valida.']);
    exit;
}

$messageId = isset($_POST['message_id']) ? (int) $_POST['message_id'] : 0;
if ($messageId < 1) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Messaggio non valido.']);
    exit;
}

$stmt = getDbConnection()->prepare('DELETE FROM chat_messages WHERE id = ?');
$stmt->execute([$messageId]);
echo json_encode(['success' => true, 'message_id' => $messageId]);
