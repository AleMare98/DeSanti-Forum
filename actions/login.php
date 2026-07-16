<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Invalid request.'], 405);
    }
    header('Location: ?page=login');
    exit;
}

if (!verifyCsrfToken()) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Invalid CSRF token.'], 403);
    }
    die('Invalid CSRF token.');
}

$username = sanitizePost('username');
$password = sanitizePost('password');

$pdo  = getDbConnection();
$stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    loginUser((int) $user['id'], $user['username'], $user['role']);
    if ($isAjax) {
        jsonResponse([
            'success'  => true,
            'username' => $user['username'],
            'role'     => $user['role'],
        ]);
    }
    header('Location: ?page=index');
    exit;
}

if ($isAjax) {
    jsonResponse(['success' => false, 'error' => 'Invalid username or password.'], 401);
}

die('Invalid username or password.');
