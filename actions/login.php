<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/sanitize.php';

$redirect = '../index.php?page=login';
requirePost($redirect);
requireValidCsrf($redirect);

$username = sanitizePost('username');
$password = sanitizePost('password');
if ($username === '' || $password === '') {
    requestError('Inserisci nome utente e password.', 422, $redirect);
}

$stmt = getDbConnection()->prepare('SELECT id, username, password, role FROM users WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch();
if (!$user || !password_verify($password, $user['password'])) {
    requestError('Nome utente o password non corretti.', 401, $redirect);
}

loginUser((int) $user['id'], $user['username'], $user['role']);
requestSuccess(['user' => ['username' => $user['username'], 'role' => $user['role']]], '../index.php?page=index');
