<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/sanitize.php';

$userId = requireAuthenticatedApi('../index.php?page=login');
requirePost('../index.php?page=index');
$threadId = postInteger('thread_id');
$redirect = '../index.php?page=thread&id=' . $threadId;
requireValidCsrf($redirect);

$content = sanitizePost('content');
if ($threadId < 1 || $content === '' || mb_strlen($content) > 5000) {
    requestError('Il commento deve contenere da 1 a 5000 caratteri.', 422, $redirect);
}

$pdo = getDbConnection();
$thread = $pdo->prepare('SELECT id FROM threads WHERE id = ?');
$thread->execute([$threadId]);
if (!$thread->fetch()) {
    requestError('La discussione non esiste più.', 404, '../index.php?page=index');
}

$stmt = $pdo->prepare('INSERT INTO comments (content, user_id, thread_id) VALUES (?, ?, ?)');
$stmt->execute([$content, $userId, $threadId]);
requestSuccess(['comment' => ['id' => (int) $pdo->lastInsertId(), 'content' => $content]], $redirect);
