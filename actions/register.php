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
    header('Location: ?page=register');
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

if (strlen($username) < 3 || strlen($username) > 50) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Username must be between 3 and 50 characters.'], 422);
    }
    die('Username must be between 3 and 50 characters.');
}

if (strlen($password) < 6) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Password must be at least 6 characters.'], 422);
    }
    die('Password must be at least 6 characters.');
}

$pdo  = getDbConnection();
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$username]);

if ($stmt->fetch()) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Username already taken.'], 409);
    }
    die('Username already taken.');
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
$stmt->execute([$username, $hashedPassword, 'user']);

loginUser((int) $pdo->lastInsertId(), $username, 'user');

if ($isAjax) {
    jsonResponse([
        'success'  => true,
        'username' => $username,
        'role'     => 'user',
    ]);
}

header('Location: ?page=index');
exit;
