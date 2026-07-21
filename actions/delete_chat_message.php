<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/sanitize.php';

requireAdminApi('../index.php?page=index');
requirePost('../index.php?page=index');
requireValidCsrf('../index.php?page=index');

$messageId = postInteger('message_id');
if ($messageId < 1) {
    requestError('Messaggio non valido.', 422, '../index.php?page=index');
}

$stmt = getDbConnection()->prepare('DELETE FROM chat_messages WHERE id = ?');
$stmt->execute([$messageId]);
if ($stmt->rowCount() !== 1) {
    requestError('Il messaggio non è stato trovato.', 404, '../index.php?page=index');
}
requestSuccess(['message_id' => $messageId], '../index.php?page=index');
