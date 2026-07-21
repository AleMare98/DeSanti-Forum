<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

requireAdmin();
$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);
$categories = getDbConnection()->query('SELECT c.id, c.name, c.created_at, COUNT(t.id) AS thread_count FROM categories c LEFT JOIN threads t ON t.category_id = c.id GROUP BY c.id, c.name, c.created_at ORDER BY c.name')->fetchAll();
$pageTitle = 'Amministrazione';
require __DIR__ . '/../includes/header.php';
?>
<h1>Amministrazione</h1>
<?php if ($flashSuccess !== ''): ?><div class="alert alert-success" role="status"><?php echo escapeHtml($flashSuccess); ?></div><?php endif; ?>
<section class="card create-form">
    <h2>Crea una categoria</h2>
    <div class="form-error" role="alert" hidden></div>
    <form action="actions/create_category.php" method="post" data-action="create_category">
        <?php echo csrfField(); ?>
        <label for="category-name">Nome</label><input id="category-name" name="name" maxlength="100" required>
        <button type="submit">Crea categoria</button>
    </form>
</section>
<section class="card create-form">
    <h2>Genera una bozza con AI</h2>
    <p class="helper-text">La bozza non viene pubblicata e non modifica il forum.</p>
    <div class="form-error" role="alert" hidden></div>
    <form action="actions/generate_forum_ai.php" method="post" data-action="generate_forum_ai">
        <?php echo csrfField(); ?>
        <label for="seed-prompt">Argomento</label><textarea id="seed-prompt" name="seed_prompt" maxlength="1500" rows="4" required></textarea>
        <div class="form-grid"><div><label for="language">Lingua</label><input id="language" name="language" maxlength="40" value="Italiano" required></div></div>
        <div class="form-grid"><div><label for="category-count">Categorie</label><input id="category-count" name="category_count" type="number" min="1" max="5" value="1" required></div><div><label for="threads-count">Discussioni per categoria</label><input id="threads-count" name="threads_per_category" type="number" min="1" max="5" value="2" required></div><div><label for="comments-count">Commenti per discussione</label><input id="comments-count" name="comments_per_thread" type="number" min="0" max="6" value="1" required></div></div>
        <button type="submit">Crea bozza</button>
    </form>
    <div id="ai-draft" class="ai-draft" hidden aria-live="polite"></div>
</section>
<section><h2>Categorie esistenti</h2>
<?php if (!$categories): ?><p class="empty-state">Nessuna categoria.</p><?php else: ?><div class="category-list"><?php foreach ($categories as $category): ?><article class="category-item"><a href="index.php?page=category&amp;id=<?php echo (int) $category['id']; ?>"><?php echo escapeHtml($category['name']); ?></a><span class="category-meta"><?php echo (int) $category['thread_count']; ?> discussioni</span></article><?php endforeach; ?></div><?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
