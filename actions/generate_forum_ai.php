<?php

require_once __DIR__ . '/../config/ai.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/http.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/ai_forum_generator.php';

$redirect = '../index.php?page=admin';
requireAdminApi($redirect);
requirePost($redirect);
requireValidCsrf($redirect);

startSession();
if (!empty($_SESSION['last_ai_generation_at']) && time() - (int) $_SESSION['last_ai_generation_at'] < AI_RATE_LIMIT_SECONDS) {
    requestError('Attendi qualche secondo prima di generare un’altra bozza.', 429, $redirect);
}

$seedPrompt = sanitizePost('seed_prompt');
$language = sanitizePost('language');
$tone = sanitizePost('tone');
$categoryCount = postInteger('category_count');
$threadsPerCategory = postInteger('threads_per_category');
$commentsPerThread = postInteger('comments_per_thread');

if ($seedPrompt === '' || mb_strlen($seedPrompt) > 2000 || $language === '' || mb_strlen($language) > 40 || $tone === '' || mb_strlen($tone) > 40) {
    requestError('Controlla prompt, lingua e tono della bozza.', 422, $redirect);
}
if ($categoryCount < AI_MIN_CATEGORIES || $categoryCount > AI_MAX_CATEGORIES || $threadsPerCategory < AI_MIN_THREADS_PER_CATEGORY || $threadsPerCategory > AI_MAX_THREADS_PER_CATEGORY || $commentsPerThread < AI_MIN_COMMENTS_PER_THREAD || $commentsPerThread > AI_MAX_COMMENTS_PER_THREAD) {
    requestError('I quantitativi richiesti non sono consentiti.', 422, $redirect);
}
if ((AI_PROVIDER === 'openai' && AI_OPENAI_API_KEY === '') || (AI_PROVIDER === 'github' && AI_GITHUB_TOKEN === '') || !in_array(AI_PROVIDER, ['openai', 'github'], true)) {
    requestError('Il servizio di generazione non è disponibile.', 503, $redirect);
}

try {
    $draft = generateForumStructure($seedPrompt, $language, $tone, $categoryCount, $threadsPerCategory, $commentsPerThread);
    $_SESSION['last_ai_generation_at'] = time();
    requestSuccess(['draft' => $draft], $redirect);
} catch (Throwable $exception) {
    error_log('AI draft generation failed: ' . $exception->getMessage());
    requestError('Non è stato possibile creare la bozza. Riprova più tardi.', 502, $redirect);
}
