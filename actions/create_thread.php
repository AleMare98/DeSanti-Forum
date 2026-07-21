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
$title = sanitizePost('title');
$content = sanitizePost('content');
$redirect = '../index.php?page=category&id=' . $categoryId;

if ($categoryId < 1 || $title === '' || mb_strlen($title) > 255 || $content === '' || mb_strlen($content) > 10000) {
    requestError('Controlla categoria, titolo e contenuto del thread.', 422, $redirect);
}

$pdo = getDbConnection();
$category = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
$category->execute([$categoryId]);
if (!$category->fetch()) {
    requestError('La categoria selezionata non esiste.', 404, '../index.php?page=index');
}

$stmt = $pdo->prepare('INSERT INTO threads (title, content, user_id, category_id) VALUES (?, ?, ?, ?)');
$stmt->execute([$title, $content, $userId, $categoryId]);
$threadId = (int) $pdo->lastInsertId();
requestSuccess(['thread' => ['id' => $threadId, 'title' => $title]], '../index.php?page=thread&id=' . $threadId);
