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
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'ai_generated'): ?>
        <div class="alert alert-success">AI content generated successfully.</div>
    <?php endif; ?>

    <div class="create-form">
        <h2>Create Category</h2>
        <div class="alert alert-error form-error" style="display:none;"></div>
        <form action="actions/create_category.php" method="POST" data-action="create_category">
            <?php echo csrfField(); ?>
            <label for="name">Category Name:</label>
            <input type="text" id="name" name="name" maxlength="100" required>
            <button type="submit">Create Category</button>
        </form>
    </div>

    <div class="create-form">
        <h2>Generate Forum with AI</h2>
        <div class="alert alert-error form-error" style="display:none;"></div>
        <form action="actions/generate_forum_ai.php" method="POST" data-action="generate_forum_ai">
            <?php echo csrfField(); ?>

            <label for="seed_prompt">Seed Prompt:</label>
            <textarea id="seed_prompt" name="seed_prompt" rows="4" maxlength="2000" required>Generate engaging forum content about technology trends, practical coding tips, and developer career growth.</textarea>

            <label for="language">Language:</label>
            <input type="text" id="language" name="language" maxlength="40" value="Italian" required>

            <label for="tone">Tone:</label>
            <input type="text" id="tone" name="tone" maxlength="40" value="Friendly and practical" required>

            <label for="category_count">New Categories:</label>
            <input type="number" id="category_count" name="category_count" min="1" max="5" value="2" required>

            <label for="threads_per_category">Threads per Category:</label>
            <input type="number" id="threads_per_category" name="threads_per_category" min="1" max="5" value="3" required>

            <label for="comments_per_thread">Comments per Thread:</label>
            <input type="number" id="comments_per_thread" name="comments_per_thread" min="0" max="6" value="2" required>

            <button type="submit">Generate Content</button>
        </form>
    </div>

    <h2>Existing Categories</h2>

    <?php if (empty($categories)): ?>
        <p class="empty-state" id="empty-categories">No categories yet.</p>
    <?php else: ?>
        <div class="category-list" id="category-list">
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
