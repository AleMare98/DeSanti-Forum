<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/ai_followup.php';

$userId = requireAuthenticatedApi('../index.php?page=login');
requirePost('../index.php?page=index');
$threadId = postInteger('thread_id');
$redirect = '../index.php?page=thread&id=' . $threadId;
requireValidCsrf($redirect);

$content = sanitizePost('content');
if ($threadId < 1 || $content === '' || mb_strlen($content) > 5000) {
    requestError('Il commento deve contenere da 1 a 5000 caratteri.', 422, $redirect);
}

$pdo = getDbConnection();
$thread = $pdo->prepare('SELECT id FROM threads WHERE id = ?');
$thread->execute([$threadId]);
if (!$thread->fetch()) {
    requestError('La discussione non esiste più.', 404, '../index.php?page=index');
}

$threadStmt = $pdo->prepare('SELECT t.id, t.title, t.content FROM threads t WHERE t.id = ?');
$threadStmt->execute([$threadId]);
$threadData = $threadStmt->fetch();
$pdo->beginTransaction();
$stmt = $pdo->prepare('INSERT INTO comments (content, user_id, thread_id, source) VALUES (?, ?, ?, \'human\')');
$stmt->execute([$content, $userId, $threadId]);
$commentId = (int) $pdo->lastInsertId();
$settingStmt = $pdo->query('SELECT ai_followups_enabled FROM forum_settings WHERE id = 1');
$followupsEnabled = (bool) ($settingStmt->fetchColumn() ?? false);
$runId = null;
if ($followupsEnabled) {
    $runStmt = $pdo->prepare('INSERT INTO ai_comment_followups (trigger_comment_id, thread_id, provider, model) VALUES (?, ?, ?, ?)');
    $runStmt->execute([$commentId, $threadId, AI_PROVIDER, AI_FOLLOWUP_MODEL]);
    $runId = (int) $pdo->lastInsertId();
}
$pdo->commit();

if ($followupsEnabled && $runId !== null) {
    try {
        $commentsStmt = $pdo->prepare('SELECT c.content, u.username FROM comments c JOIN users u ON u.id = c.user_id WHERE c.thread_id = ? ORDER BY c.created_at ASC, c.id ASC');
        $commentsStmt->execute([$threadId]);
        $decision = decideAiFollowup($threadData, $commentsStmt->fetchAll());
        if (!$decision['should_reply']) {
            $update = $pdo->prepare('UPDATE ai_comment_followups SET status = \'skipped\', should_reply = 0, completed_at = NOW() WHERE id = ?');
            $update->execute([$runId]);
        } else {
            $pdo->beginTransaction();
            $botStmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $botStmt->execute(['Assistente IA']);
            $botId = $botStmt->fetchColumn();
            if (!$botId) {
                $insertBot = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, \'user\')');
                $insertBot->execute(['Assistente IA', password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT)]);
                $botId = $pdo->lastInsertId();
            }
            $insertAi = $pdo->prepare('INSERT INTO comments (content, user_id, thread_id, source) VALUES (?, ?, ?, \'ai\')');
            $insertAi->execute([$decision['content'], (int) $botId, $threadId]);
            $aiCommentId = (int) $pdo->lastInsertId();
            $update = $pdo->prepare('UPDATE ai_comment_followups SET status = \'replied\', should_reply = 1, response_content = ?, ai_comment_id = ?, completed_at = NOW() WHERE id = ?');
            $update->execute([$decision['content'], $aiCommentId, $runId]);
            $pdo->commit();
        }
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('AI follow-up failed: ' . $exception->getMessage());
        $failed = $pdo->prepare('UPDATE ai_comment_followups SET status = \'failed\', error_message = ?, completed_at = NOW() WHERE id = ?');
        $failed->execute(['AI follow-up unavailable', $runId]);
    }
}
requestSuccess(['comment' => ['id' => $commentId, 'content' => $content]], $redirect);
