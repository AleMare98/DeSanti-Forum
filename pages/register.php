<?php
require_once __DIR__ . '/../includes/auth.php'; require_once __DIR__ . '/../includes/csrf.php'; require_once __DIR__ . '/../includes/sanitize.php';
if (isLoggedIn()) { header('Location: index.php?page=index'); exit; }
$pageTitle = 'Registrati'; require __DIR__ . '/../includes/header.php';
?>
<section class="auth-form card"><h1>Crea un account</h1><div class="form-error" role="alert" hidden></div><form action="actions/register.php" method="post" data-action="register"><?php echo csrfField(); ?><label for="username">Nome utente</label><input id="username" name="username" minlength="3" maxlength="50" autocomplete="username" required><label for="password">Password</label><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required><button type="submit">Registrati</button></form><p>Hai già un account? <a href="index.php?page=login">Accedi</a>.</p></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
