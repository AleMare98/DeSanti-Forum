<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/sanitize.php';

$redirect = '../index.php?page=register';
requirePost($redirect);
requireValidCsrf($redirect);

$username = sanitizePost('username');
$password = sanitizePost('password');
if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
    requestError('Il nome utente deve avere 3-50 caratteri: lettere, numeri o _.', 422, $redirect);
}
if (mb_strlen($password) < 8 || mb_strlen($password) > 255) {
    requestError('La password deve contenere almeno 8 caratteri.', 422, $redirect);
}

$pdo = getDbConnection();
try {
    $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), 'user']);
} catch (PDOException $exception) {
    requestError('Questo nome utente è già in uso.', 409, $redirect);
}

loginUser((int) $pdo->lastInsertId(), $username, 'user');
requestSuccess(['user' => ['username' => $username, 'role' => 'user']], '../index.php?page=index');
