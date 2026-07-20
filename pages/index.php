<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

$pageTitle    = 'Categories';
$categoryId   = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($categoryId > 0) {
    // Show threads in a category
    $pdo  = getDbConnection();
    $stmt = $pdo->prepare('SELECT id, name FROM categories WHERE id = ?');
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch();

    if (!$category) {
        die('Category not found.');
    }

    $pageTitle = escapeHtml($category['name']);

    $stmt = $pdo->prepare(
        'SELECT t.id, t.title, t.created_at, u.username
         FROM threads t
         JOIN users u ON t.user_id = u.id
         WHERE t.category_id = ?
         ORDER BY t.created_at DESC'
    );
    $stmt->execute([$categoryId]);
    $threads = $stmt->fetchAll();

    require_once __DIR__ . '/../includes/header.php';
?>

    <div class="breadcrumb">
        <a href="?page=index">Home</a> &raquo; <?php echo $pageTitle; ?>
    </div>

    <h1><?php echo $pageTitle; ?></h1>

    <?php if (isLoggedIn()): ?>
        <div class="create-form">
            <h2>New Thread</h2>
            <div id="form-error" class="alert alert-error" style="display:none;"></div>
            <form action="actions/create_thread.php" method="POST" data-action="create_thread">
                <?php echo csrfField(); ?>
                <input type="hidden" name="category_id" value="<?php echo $categoryId; ?>">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" maxlength="255" required>
                <label for="content">Content:</label>
                <textarea id="content" name="content" rows="5" required></textarea>
                <button type="submit">Create Thread</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if (empty($threads)): ?>
        <p class="empty-state" id="empty-threads">No threads yet. Be the first to create one!</p>
    <?php else: ?>
        <div class="thread-list" id="thread-list">
            <?php foreach ($threads as $thread): ?>
                <div class="thread-item">
                    <a href="?page=thread&id=<?php echo $thread['id']; ?>">
                        <?php echo escapeHtml($thread['title']); ?>
                    </a>
                    <span class="thread-meta">
                        by <?php echo escapeHtml($thread['username']); ?>
                        on <?php echo escapeHtml($thread['created_at']); ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php
    require_once __DIR__ . '/../includes/footer.php';
} else {
    // Show all categories
    $pdo  = getDbConnection();
    $stmt = $pdo->query(
        'SELECT c.id, c.name, c.created_at, u.username,
                (SELECT COUNT(*) FROM threads t WHERE t.category_id = c.id) AS thread_count
         FROM categories c
         JOIN users u ON c.created_by = u.id
         ORDER BY c.name ASC'
    );
    $categories = $stmt->fetchAll();

    $chatMessages = [];
    if (isLoggedIn() && !empty($categories)) {
        $chatStmt = $pdo->prepare(
            'SELECT cm.id, cm.content, cm.created_at, u.username
             FROM chat_messages cm
             JOIN users u ON u.id = cm.user_id
             WHERE cm.category_id = ?
             ORDER BY cm.created_at DESC, cm.id DESC
             LIMIT 50'
        );
        $chatStmt->execute([(int) $categories[0]['id']]);
        $chatMessages = array_reverse($chatStmt->fetchAll());
    }

    require_once __DIR__ . '/../includes/header.php';
?>

    <h1>Categories</h1>

    <?php if (empty($categories)): ?>
        <p class="empty-state">No categories yet.</p>
    <?php else: ?>
        <div class="category-list">
            <?php foreach ($categories as $cat): ?>
                <div class="category-item">
                    <a href="?page=category&id=<?php echo $cat['id']; ?>">
                        <?php echo escapeHtml($cat['name']); ?>
                    </a>
                    <span class="category-meta">
                        <?php echo $cat['thread_count']; ?> thread(s)
                        &mdash; created by <?php echo escapeHtml($cat['username']); ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (isLoggedIn() && !empty($categories)): ?>
        <section class="chat-panel" id="chat-panel" data-chat-endpoint="actions/get_chat_messages.php" data-can-delete="<?php echo isAdmin() ? '1' : '0'; ?>">
            <div class="chat-heading">
                <div>
                    <h2>Chat della classe</h2>
                    <p>Scegli un canale e scrivi ai tuoi compagni.</p>
                </div>
                <label class="chat-channel-label" for="chat-channel">Canale</label>
                <select id="chat-channel" class="chat-channel-select">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int) $cat['id']; ?>"><?php echo escapeHtml($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="chat-messages" id="chat-messages" aria-live="polite">
                <?php if (empty($chatMessages)): ?>
                    <p class="chat-empty">Nessun messaggio. Inizia tu la conversazione!</p>
                <?php else: ?>
                    <?php foreach ($chatMessages as $message): ?>
                        <div class="chat-message" data-message-id="<?php echo (int) $message['id']; ?>">
                            <div class="chat-message-meta">
                                <strong><?php echo escapeHtml($message['username']); ?></strong>
                                <time><?php echo escapeHtml($message['created_at']); ?></time>
                            </div>
                            <p><?php echo escapeHtml($message['content']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="alert alert-error chat-error" id="chat-error" role="alert" hidden></div>
            <form class="chat-form" id="chat-form" action="actions/create_chat_message.php" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="category_id" id="chat-category-id" value="<?php echo (int) $categories[0]['id']; ?>">
                <label class="sr-only" for="chat-content">Messaggio</label>
                <input type="text" id="chat-content" name="content" maxlength="500" placeholder="Scrivi un messaggio..." autocomplete="off" required>
                <button type="submit">Invia</button>
            </form>
        </section>
    <?php elseif (!isLoggedIn()): ?>
        <p class="chat-login-note"><a href="?page=login">Accedi</a> per partecipare alla chat della classe.</p>
    <?php endif; ?>

<?php
    require_once __DIR__ . '/../includes/footer.php';
}
