<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/csrf.php';

$threadId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($threadId < 1) {
    header('Location: ?page=index');
    exit;
}

$pdo  = getDbConnection();
$stmt = $pdo->prepare(
    'SELECT t.id, t.title, t.content, t.created_at, t.category_id, u.username
     FROM threads t
     JOIN users u ON t.user_id = u.id
     WHERE t.id = ?'
);
$stmt->execute([$threadId]);
$thread = $stmt->fetch();

if (!$thread) {
    die('Thread not found.');
}

$pageTitle = escapeHtml($thread['title']);

// Fetch comments
$stmt = $pdo->prepare(
    'SELECT c.id, c.content, c.created_at, u.username
     FROM comments c
     JOIN users u ON c.user_id = u.id
     WHERE c.thread_id = ?
     ORDER BY c.created_at ASC'
);
$stmt->execute([$threadId]);
$comments = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

    <div class="breadcrumb">
        <a href="?page=index">Home</a> &raquo;
        <a href="?page=category&id=<?php echo $thread['category_id']; ?>">Category</a> &raquo;
        <?php echo $pageTitle; ?>
    </div>

    <article class="thread">
        <h1><?php echo $pageTitle; ?></h1>
        <div class="thread-meta">
            by <strong><?php echo escapeHtml($thread['username']); ?></strong>
            on <?php echo escapeHtml($thread['created_at']); ?>
        </div>
        <div class="thread-content">
            <?php echo nl2br(escapeHtml($thread['content'])); ?>
        </div>

        <?php if (isAdmin()): ?>
            <form action="actions/delete_thread.php" method="POST" class="inline-form">
                <?php echo csrfField(); ?>
                <input type="hidden" name="thread_id" value="<?php echo $thread['id']; ?>">
                <button type="submit" class="btn-delete" onclick="return confirm('Delete this thread?')">Delete Thread</button>
            </form>
        <?php endif; ?>
    </article>

    <section class="comments">
        <h2>Comments (<?php echo count($comments); ?>)</h2>

        <?php if (empty($comments)): ?>
            <p class="empty-state">No comments yet.</p>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <div class="comment">
                    <div class="comment-header">
                        <strong><?php echo escapeHtml($comment['username']); ?></strong>
                        <span><?php echo escapeHtml($comment['created_at']); ?></span>
                    </div>
                    <div class="comment-content">
                        <?php echo nl2br(escapeHtml($comment['content'])); ?>
                    </div>
                    <?php if (isAdmin()): ?>
                        <form action="actions/delete_comment.php" method="POST" class="inline-form">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                            <input type="hidden" name="thread_id" value="<?php echo $thread['id']; ?>">
                            <button type="submit" class="btn-delete" onclick="return confirm('Delete this comment?')">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <?php if (isLoggedIn()): ?>
        <div class="create-form">
            <h2>Post a Comment</h2>
            <form action="actions/create_comment.php" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="thread_id" value="<?php echo $thread['id']; ?>">
                <label for="content">Comment:</label>
                <textarea id="content" name="content" rows="4" required></textarea>
                <button type="submit">Post Comment</button>
            </form>
        </div>
    <?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
