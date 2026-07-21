<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/sanitize.php';

requireAuthenticatedApi('../index.php?page=login');
$categoryId = getInteger('category_id');
if ($categoryId < 1) {
    jsonResponse(['success' => false, 'error' => 'Canale non valido.'], 422);
}

$pdo = getDbConnection();
$category = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
$category->execute([$categoryId]);
if (!$category->fetch()) {
    jsonResponse(['success' => false, 'error' => 'Canale non trovato.'], 404);
}

$stmt = $pdo->prepare(
    'SELECT cm.id, cm.content, cm.created_at, u.username
     FROM chat_messages cm JOIN users u ON u.id = cm.user_id
     WHERE cm.category_id = ? ORDER BY cm.created_at ASC, cm.id ASC LIMIT 50'
);
$stmt->execute([$categoryId]);
jsonResponse(['success' => true, 'messages' => $stmt->fetchAll()]);
