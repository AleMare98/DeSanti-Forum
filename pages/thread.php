<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

$threadId = getInteger('id');
if ($threadId < 1) {
    header('Location: index.php?page=index');
    exit;
}
$pdo = getDbConnection();
$threadStmt = $pdo->prepare(
    'SELECT t.id, t.title, t.content, t.created_at, t.category_id, c.name AS category_name, u.username
     FROM threads t JOIN categories c ON c.id = t.category_id JOIN users u ON u.id = t.user_id WHERE t.id = ?'
);
$threadStmt->execute([$threadId]);
$thread = $threadStmt->fetch();
if (!$thread) {
    http_response_code(404);
    $pageTitle = 'Discussione non trovata';
    require __DIR__ . '/../includes/header.php';
    echo '<h1>Discussione non trovata</h1><p><a href="index.php?page=index">Torna alle categorie</a></p>';
    require __DIR__ . '/../includes/footer.php';
    return;
}
$commentStmt = $pdo->prepare('SELECT c.id, c.content, c.source, c.created_at, u.username FROM comments c JOIN users u ON u.id = c.user_id WHERE c.thread_id = ? ORDER BY c.created_at ASC, c.id ASC');
$commentStmt->execute([$threadId]);
$comments = $commentStmt->fetchAll();
$pageTitle = $thread['title'];
require __DIR__ . '/../includes/header.php';
?>
<nav class="breadcrumb" aria-label="Percorso"><a href="index.php?page=index">Categorie</a> / <a href="index.php?page=category&amp;id=<?php echo (int) $thread['category_id']; ?>"><?php echo escapeHtml($thread['category_name']); ?></a> / <?php echo escapeHtml($thread['title']); ?></nav>
<article class="thread card">
    <h1><?php echo escapeHtml($thread['title']); ?></h1>
    <p class="thread-meta">Pubblicata da <?php echo escapeHtml($thread['username']); ?> il <?php echo escapeHtml($thread['created_at']); ?></p>
    <div class="thread-content"><?php echo nl2br(escapeHtml($thread['content'])); ?></div>
    <?php if (isAdmin()): ?>
        <form class="inline-form" action="actions/delete_thread.php" method="post" data-action="delete_thread">
            <?php echo csrfField(); ?><input type="hidden" name="thread_id" value="<?php echo $threadId; ?>"><input type="hidden" name="category_id" value="<?php echo (int) $thread['category_id']; ?>">
            <button class="btn-delete" type="submit" data-confirm="Eliminare questa discussione?">Elimina discussione</button>
        </form>
    <?php endif; ?>
</article>
<section class="comments" aria-labelledby="comments-heading">
    <h2 id="comments-heading">Commenti (<?php echo count($comments); ?>)</h2>
    <?php foreach ($comments as $comment): ?>
        <article class="comment" id="comment-<?php echo (int) $comment['id']; ?>">
            <p class="comment-header"><strong><?php echo escapeHtml($comment['username']); ?></strong><?php if (($comment['source'] ?? 'human') === 'ai'): ?> <span class="ai-badge">Generato dall’IA</span><?php endif; ?> <time><?php echo escapeHtml($comment['created_at']); ?></time></p>
            <div class="comment-content"><?php echo nl2br(escapeHtml($comment['content'])); ?></div>
            <?php if (isAdmin()): ?>
                <form class="inline-form" action="actions/delete_comment.php" method="post" data-action="delete_comment">
                    <?php echo csrfField(); ?><input type="hidden" name="comment_id" value="<?php echo (int) $comment['id']; ?>"><input type="hidden" name="thread_id" value="<?php echo $threadId; ?>">
                    <button class="btn-delete" type="submit" data-confirm="Eliminare questo commento?">Elimina</button>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    <?php if (!$comments): ?><p class="empty-state">Nessun commento.</p><?php endif; ?>
</section>
<?php if (isLoggedIn()): ?>
    <section class="card create-form"><h2>Lascia un commento</h2><div class="form-error" role="alert" hidden></div>
        <form action="actions/create_comment.php" method="post" data-action="create_comment"><?php echo csrfField(); ?><input type="hidden" name="thread_id" value="<?php echo $threadId; ?>"><label for="comment-content">Commento</label><textarea id="comment-content" name="content" maxlength="5000" rows="4" required></textarea><button type="submit">Pubblica commento</button></form>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
