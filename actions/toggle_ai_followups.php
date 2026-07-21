<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ai.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/http.php';

$redirect = '../index.php?page=admin';
$adminId = requireAdminApi($redirect);
requirePost($redirect);
requireValidCsrf($redirect);
$enabled = filter_input(INPUT_POST, 'enabled', FILTER_VALIDATE_INT);
if ($enabled !== 0 && $enabled !== 1) requestError('Impostazione non valida.', 422, $redirect);
if ($enabled === 1 && (AI_PROVIDER !== 'github' || AI_GITHUB_TOKEN === '')) {
    requestError('Configura prima il PAT GitHub Models sul server.', 503, $redirect);
}
$stmt = getDbConnection()->prepare('UPDATE forum_settings SET ai_followups_enabled = ?, updated_by = ? WHERE id = 1');
$stmt->execute([$enabled, $adminId]);
requestSuccess([], $redirect);
