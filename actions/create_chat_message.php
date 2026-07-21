<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/sanitize.php';

$userId = requireAuthenticatedApi('../index.php?page=login');
requirePost('../index.php?page=index');
requireValidCsrf('../index.php?page=index');

$categoryId = postInteger('category_id');
$content = sanitizePost('content');
if ($categoryId < 1 || $content === '' || mb_strlen($content) > 500) {
    requestError('Il messaggio deve contenere da 1 a 500 caratteri.', 422, '../index.php?page=index');
}

$pdo = getDbConnection();
$category = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
$category->execute([$categoryId]);
if (!$category->fetch()) {
    requestError('Il canale selezionato non esiste.', 404, '../index.php?page=index');
}

$stmt = $pdo->prepare('INSERT INTO chat_messages (category_id, user_id, content) VALUES (?, ?, ?)');
$stmt->execute([$categoryId, $userId, $content]);
requestSuccess(['message' => ['id' => (int) $pdo->lastInsertId(), 'content' => $content]], '../index.php?page=category&id=' . $categoryId);
