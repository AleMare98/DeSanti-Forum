<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sanitize.php';

$stmt = getDbConnection()->query(
    'SELECT c.id, c.name, c.created_at, u.username, COUNT(t.id) AS thread_count
     FROM categories c JOIN users u ON u.id = c.created_by
     LEFT JOIN threads t ON t.category_id = c.id
     GROUP BY c.id, c.name, c.created_at, u.username ORDER BY c.name ASC'
);
$categories = $stmt->fetchAll();
$pageTitle = 'Categorie';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-intro">
    <h1>Forum studenti</h1>
    <p>Confrontati, apri discussioni e partecipa alla chat della tua categoria.</p>
</section>

<?php if (!$categories): ?>
    <p class="empty-state">Non ci sono ancora categorie.</p>
<?php else: ?>
    <div class="category-list">
        <?php foreach ($categories as $category): ?>
            <article class="category-item">
                <a href="index.php?page=category&amp;id=<?php echo (int) $category['id']; ?>"><?php echo escapeHtml($category['name']); ?></a>
                <span class="category-meta"><?php echo (int) $category['thread_count']; ?> discussioni · creata da <?php echo escapeHtml($category['username']); ?></span>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
