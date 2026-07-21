<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/sanitize.php';

requireAdminApi('../index.php?page=index');
requirePost('../index.php?page=index');
$commentId = postInteger('comment_id');
$threadId = postInteger('thread_id');
$redirect = '../index.php?page=thread&id=' . $threadId;
requireValidCsrf($redirect);

$stmt = getDbConnection()->prepare('DELETE FROM comments WHERE id = ? AND thread_id = ?');
$stmt->execute([$commentId, $threadId]);
if ($commentId < 1 || $stmt->rowCount() !== 1) {
    requestError('Il commento non è stato trovato.', 404, $redirect);
}
requestSuccess([], $redirect);
