<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/sanitize.php';

$redirect = '../index.php?page=admin';
$userId = requireAdminApi($redirect);
requirePost($redirect);
requireValidCsrf($redirect);

$name = sanitizePost('name');
if ($name === '' || mb_strlen($name) > 100) {
    requestError('Il nome della categoria deve contenere da 1 a 100 caratteri.', 422, $redirect);
}

try {
    $stmt = getDbConnection()->prepare('INSERT INTO categories (name, created_by) VALUES (?, ?)');
    $stmt->execute([$name, $userId]);
} catch (PDOException $exception) {
    requestError('Esiste già una categoria con questo nome.', 409, $redirect);
}

requestSuccess(['category' => ['id' => (int) getDbConnection()->lastInsertId(), 'name' => $name]], $redirect);
