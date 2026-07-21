<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

$categoryId = getInteger('id');
if ($categoryId < 1) {
    header('Location: index.php?page=index');
    exit;
}

$pdo = getDbConnection();
$categoryStmt = $pdo->prepare('SELECT id, name FROM categories WHERE id = ?');
$categoryStmt->execute([$categoryId]);
$category = $categoryStmt->fetch();
if (!$category) {
    http_response_code(404);
    $pageTitle = 'Categoria non trovata';
    require __DIR__ . '/../includes/header.php';
    echo '<h1>Categoria non trovata</h1><p><a href="index.php?page=index">Torna alle categorie</a></p>';
    require __DIR__ . '/../includes/footer.php';
    return;
}

$threadsStmt = $pdo->prepare(
    'SELECT t.id, t.title, t.created_at, u.username, COUNT(c.id) AS comment_count
     FROM threads t JOIN users u ON u.id = t.user_id
     LEFT JOIN comments c ON c.thread_id = t.id
     WHERE t.category_id = ? GROUP BY t.id, t.title, t.created_at, u.username
     ORDER BY t.created_at DESC, t.id DESC'
);
$threadsStmt->execute([$categoryId]);
$threads = $threadsStmt->fetchAll();
$pageTitle = $category['name'];
require __DIR__ . '/../includes/header.php';
?>
<nav class="breadcrumb" aria-label="Percorso"><a href="index.php?page=index">Categorie</a> / <?php echo escapeHtml($category['name']); ?></nav>
<h1><?php echo escapeHtml($category['name']); ?></h1>

<?php if (isLoggedIn()): ?>
    <section class="card create-form">
        <h2>Apri una discussione</h2>
        <div class="form-error" role="alert" hidden></div>
        <form action="actions/create_thread.php" method="post" data-action="create_thread">
            <?php echo csrfField(); ?>
            <input type="hidden" name="category_id" value="<?php echo $categoryId; ?>">
            <label for="thread-title">Titolo</label>
            <input id="thread-title" name="title" type="text" maxlength="255" required>
            <label for="thread-content">Testo</label>
            <textarea id="thread-content" name="content" rows="5" maxlength="10000" required></textarea>
            <button type="submit">Pubblica discussione</button>
        </form>
    </section>
<?php endif; ?>

<section aria-labelledby="threads-heading">
    <h2 id="threads-heading">Discussioni</h2>
    <?php if (!$threads): ?>
        <p class="empty-state">Nessuna discussione: inizia tu.</p>
    <?php else: ?>
        <div class="thread-list">
            <?php foreach ($threads as $thread): ?>
                <article class="thread-item">
                    <a href="index.php?page=thread&amp;id=<?php echo (int) $thread['id']; ?>"><?php echo escapeHtml($thread['title']); ?></a>
                    <span class="thread-meta"><?php echo escapeHtml($thread['username']); ?> · <?php echo (int) $thread['comment_count']; ?> commenti</span>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if (isLoggedIn()): ?>
    <section class="chat-panel" id="chat-panel" data-category-id="<?php echo $categoryId; ?>" data-can-delete="<?php echo isAdmin() ? '1' : '0'; ?>">
        <h2>Chat della categoria</h2>
        <p class="chat-description">I messaggi sono visibili agli studenti di questa categoria.</p>
        <div class="chat-messages" id="chat-messages" aria-live="polite"></div>
        <div class="alert alert-error" id="chat-error" role="alert" hidden></div>
        <form id="chat-form" action="actions/create_chat_message.php" method="post">
            <?php echo csrfField(); ?>
            <input type="hidden" name="category_id" value="<?php echo $categoryId; ?>">
            <label class="sr-only" for="chat-content">Messaggio</label>
            <input id="chat-content" name="content" type="text" maxlength="500" autocomplete="off" required placeholder="Scrivi un messaggio">
            <button type="submit">Invia</button>
        </form>
    </section>
<?php else: ?>
    <p class="chat-login-note"><a href="index.php?page=login">Accedi</a> per scrivere nella chat della categoria.</p>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
