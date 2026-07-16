<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/sanitize.php';

$allowedPages = ['index', 'category', 'thread', 'login', 'register', 'admin'];
$page         = $_GET['page'] ?? 'index';

if (!in_array($page, $allowedPages, true)) {
    $page = 'index';
}

$filePath = __DIR__ . '/pages/' . $page . '.php';

if (!file_exists($filePath)) {
    $page     = 'index';
    $filePath = __DIR__ . '/pages/index.php';
}

require_once $filePath;
