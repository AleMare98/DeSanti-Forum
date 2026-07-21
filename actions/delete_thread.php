<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/sanitize.php';

requireAdminApi('../index.php?page=index');
requirePost('../index.php?page=index');
$threadId = postInteger('thread_id');
$categoryId = postInteger('category_id');
$redirect = '../index.php?page=category&id=' . $categoryId;
requireValidCsrf($redirect);

$stmt = getDbConnection()->prepare('DELETE FROM threads WHERE id = ? AND category_id = ?');
$stmt->execute([$threadId, $categoryId]);
if ($threadId < 1 || $stmt->rowCount() !== 1) {
    requestError('La discussione non è stata trovata.', 404, $redirect);
}
requestSuccess([], $redirect);
