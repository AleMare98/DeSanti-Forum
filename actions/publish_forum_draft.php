<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/http.php';

$redirect = '../index.php?page=admin';
$userId = requireAdminApi($redirect);
requirePost($redirect);
requireValidCsrf($redirect);
startSession();

$stored = $_SESSION['ai_forum_draft'] ?? null;
$token = $_POST['draft_token'] ?? '';
$json = $_POST['draft_json'] ?? '';
if (!is_array($stored) || !is_string($stored['token'] ?? null) || !is_string($token)
    || !hash_equals($stored['token'], $token) || !is_string($json) || mb_strlen($json) > 500000) {
    requestError('La bozza non è più disponibile. Generane una nuova.', 422, $redirect);
}

$edited = json_decode($json, true);
$original = $stored['draft'] ?? null;
if (!is_array($edited) || !is_array($original) || !isset($edited['categories'], $original['categories'])
    || !is_array($edited['categories']) || count($edited['categories']) !== count($original['categories'])) {
    requestError('La struttura della bozza non è valida.', 422, $redirect);
}

$categories = [];
$categoryNames = [];
foreach ($edited['categories'] as $categoryIndex => $category) {
    if (!is_array($category) || !is_string($category['name'] ?? null) || !is_array($category['threads'] ?? null)) {
        requestError('Controlla i campi della bozza.', 422, $redirect);
    }
    $name = trim($category['name']);
    $nameKey = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
    if ($name === '' || mb_strlen($name) > 100 || isset($categoryNames[$nameKey])) {
        requestError('I nomi delle categorie devono essere compilati e univoci.', 422, $redirect);
    }
    $categoryNames[$nameKey] = true;

    $originalCategory = $original['categories'][$categoryIndex] ?? null;
    if (!is_array($originalCategory) || !is_array($originalCategory['threads'] ?? null)
        || count($category['threads']) !== count($originalCategory['threads'])) {
        requestError('Non puoi aggiungere o rimuovere elementi dalla bozza.', 422, $redirect);
    }
    foreach ($category['threads'] as $threadIndex => $thread) {
        $originalThread = $originalCategory['threads'][$threadIndex] ?? null;
        if (!is_array($thread) || !is_array($originalThread) || !is_array($thread['comments'] ?? null)
            || !is_array($originalThread['comments'] ?? null)
            || count($thread['comments']) !== count($originalThread['comments'])) {
            requestError('Non puoi aggiungere o rimuovere elementi dalla bozza.', 422, $redirect);
        }
    }
    $categories[] = ['name' => $name, 'threads' => $category['threads']];
}

$pdo = getDbConnection();
$existingCategory = $pdo->prepare('SELECT id FROM categories WHERE name = ?');
foreach ($categories as $category) {
    $existingCategory->execute([$category['name']]);
    if ($existingCategory->fetch()) {
        requestError('Una categoria della bozza esiste già. Modifica il nome e riprova.', 409, $redirect);
    }
}

try {
    $pdo->beginTransaction();
    $insertCategory = $pdo->prepare('INSERT INTO categories (name, created_by, source, ai_prompt_hash) VALUES (?, ?, ?, ?)');
    $insertThread = $pdo->prepare('INSERT INTO threads (title, content, user_id, category_id, source, ai_prompt_hash) VALUES (?, ?, ?, ?, ?, ?)');
    $insertComment = $pdo->prepare('INSERT INTO comments (content, user_id, thread_id, source, ai_prompt_hash) VALUES (?, ?, ?, ?, ?)');
    $createdThreads = 0;
    $createdComments = 0;

    foreach ($categories as $category) {
        $insertCategory->execute([$category['name'], $userId, 'ai', $stored['prompt_hash'] ?? null]);
        $categoryId = (int) $pdo->lastInsertId();
        foreach ($category['threads'] as $thread) {
            if (!is_array($thread) || !is_string($thread['title'] ?? null) || !is_string($thread['content'] ?? null) || !is_array($thread['comments'] ?? null)) {
                throw new InvalidArgumentException('Controlla i campi dei thread.');
            }
            $title = trim($thread['title']);
            $content = trim($thread['content']);
            if ($title === '' || mb_strlen($title) > 255 || $content === '' || mb_strlen($content) > 10000) {
                throw new InvalidArgumentException('Titolo o contenuto del thread non valido.');
            }
            $insertThread->execute([$title, $content, $userId, $categoryId, 'ai', $stored['prompt_hash'] ?? null]);
            $threadId = (int) $pdo->lastInsertId();
            $createdThreads++;
            foreach ($thread['comments'] as $comment) {
                if (!is_string($comment)) {
                    throw new InvalidArgumentException('Contenuto del commento non valido.');
                }
                $comment = trim($comment);
                if ($comment === '' || mb_strlen($comment) > 5000) {
                    throw new InvalidArgumentException('Il commento deve contenere da 1 a 5000 caratteri.');
                }
                $insertComment->execute([$comment, $userId, $threadId, 'ai', $stored['prompt_hash'] ?? null]);
                $createdComments++;
            }
        }
    }
    $pdo->commit();
    unset($_SESSION['ai_forum_draft']);
    $_SESSION['flash_success'] = sprintf(
        'Bozza pubblicata: %d categorie, %d discussioni e %d commenti.',
        count($categories),
        $createdThreads,
        $createdComments
    );
    requestSuccess(['published' => ['categories' => count($categories), 'threads' => $createdThreads, 'comments' => $createdComments]], $redirect);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('AI draft publication failed: ' . $exception->getMessage());
    requestError('Non è stato possibile pubblicare la bozza. Controlla i contenuti e riprova.', 422, $redirect);
}
