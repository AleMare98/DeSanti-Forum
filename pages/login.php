<?php
require_once __DIR__ . '/../includes/auth.php'; require_once __DIR__ . '/../includes/csrf.php'; require_once __DIR__ . '/../includes/sanitize.php';
if (isLoggedIn()) { header('Location: index.php?page=index'); exit; }
$pageTitle = 'Accedi'; require __DIR__ . '/../includes/header.php';
?>
<section class="auth-form card"><h1>Accedi</h1><div class="form-error" role="alert" hidden></div><form action="actions/login.php" method="post" data-action="login"><?php echo csrfField(); ?><label for="username">Nome utente</label><input id="username" name="username" autocomplete="username" required><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required><button type="submit">Accedi</button></form><p>Non hai un account? <a href="index.php?page=register">Registrati</a>.</p></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
