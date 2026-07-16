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
            <form action="actions/create_thread.php" method="POST">
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
        <p class="empty-state">No threads yet. Be the first to create one!</p>
    <?php else: ?>
        <div class="thread-list">
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

<?php
    require_once __DIR__ . '/../includes/footer.php';
}
