<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

requireAdmin();

$pdo  = getDbConnection();
$stmt = $pdo->query(
    'SELECT id, name, created_at FROM categories ORDER BY name ASC'
);
$categories = $stmt->fetchAll();

$pageTitle = 'Admin Panel';
require_once __DIR__ . '/../includes/header.php';
?>

    <h1>Admin Panel</h1>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
        <div class="alert alert-success">Category created successfully.</div>
    <?php endif; ?>

    <div class="create-form">
        <h2>Create Category</h2>
        <form action="actions/create_category.php" method="POST">
            <?php echo csrfField(); ?>
            <label for="name">Category Name:</label>
            <input type="text" id="name" name="name" maxlength="100" required>
            <button type="submit">Create Category</button>
        </form>
    </div>

    <h2>Existing Categories</h2>

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
                        created on <?php echo escapeHtml($cat['created_at']); ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
