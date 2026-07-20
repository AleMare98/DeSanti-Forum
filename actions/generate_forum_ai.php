<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ai.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/ai_forum_generator.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function validateRangeInt(string $field, int $min, int $max): int
{
    $value = isset($_POST[$field]) ? (int) $_POST[$field] : ($min - 1);
    if ($value < $min || $value > $max) {
        throw new RuntimeException(sprintf(
            '%s must be between %d and %d.',
            str_replace('_', ' ', $field),
            $min,
            $max
        ));
    }
    return $value;
}

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Invalid request.'], 405);
    }
    header('Location: ?page=admin');
    exit;
}

if (!verifyCsrfToken()) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Invalid CSRF token.'], 403);
    }
    die('Invalid CSRF token.');
}

startSession();
$lastRunAt = isset($_SESSION['last_ai_generation_at']) ? (int) $_SESSION['last_ai_generation_at'] : 0;
if ($lastRunAt > 0 && (time() - $lastRunAt) < AI_RATE_LIMIT_SECONDS) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Please wait a few seconds before generating again.'], 429);
    }
    die('Please wait a few seconds before generating again.');
}

$seedPrompt = sanitizePost('seed_prompt');
$language = sanitizePost('language');
$tone = sanitizePost('tone');

if ($seedPrompt === '' || strlen($seedPrompt) > 2000) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Seed prompt must be between 1 and 2000 characters.'], 422);
    }
    die('Seed prompt must be between 1 and 2000 characters.');
}

if ($language === '' || strlen($language) > 40) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Language must be between 1 and 40 characters.'], 422);
    }
    die('Language must be between 1 and 40 characters.');
}

if ($tone === '' || strlen($tone) > 40) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Tone must be between 1 and 40 characters.'], 422);
    }
    die('Tone must be between 1 and 40 characters.');
}

if (AI_PROVIDER === 'openai' && AI_OPENAI_API_KEY === '') {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'OPENAI_API_KEY is not configured on the server.'], 500);
    }
    die('OPENAI_API_KEY is not configured on the server.');
}

if (AI_PROVIDER === 'github' && AI_GITHUB_TOKEN === '') {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'GITHUB_TOKEN is not configured on the server.'], 500);
    }
    die('GITHUB_TOKEN is not configured on the server.');
}

if (AI_PROVIDER !== 'openai' && AI_PROVIDER !== 'github') {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => 'Unsupported AI provider configuration.'], 500);
    }
    die('Unsupported AI provider configuration.');
}

try {
    $categoryCount = validateRangeInt('category_count', AI_MIN_CATEGORIES, AI_MAX_CATEGORIES);
    $threadsPerCategory = validateRangeInt('threads_per_category', AI_MIN_THREADS_PER_CATEGORY, AI_MAX_THREADS_PER_CATEGORY);
    $commentsPerThread = validateRangeInt('comments_per_thread', AI_MIN_COMMENTS_PER_THREAD, AI_MAX_COMMENTS_PER_THREAD);
} catch (RuntimeException $e) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()], 422);
    }
    die($e->getMessage());
}

$pdo = getDbConnection();
$runId = null;

try {
    $insertRunStmt = $pdo->prepare(
        'INSERT INTO ai_generation_runs (
            created_by, provider, model, seed_prompt, language, tone,
            requested_categories, requested_threads_per_category, requested_comments_per_thread,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insertRunStmt->execute([
        getCurrentUserId(),
        AI_PROVIDER,
        AI_PROVIDER === 'github' ? AI_GITHUB_MODEL : AI_OPENAI_MODEL,
        $seedPrompt,
        $language,
        $tone,
        $categoryCount,
        $threadsPerCategory,
        $commentsPerThread,
        'running',
    ]);
    $runId = (int) $pdo->lastInsertId();

    $generated = generateForumStructure(
        $seedPrompt,
        $language,
        $tone,
        $categoryCount,
        $threadsPerCategory,
        $commentsPerThread
    );

    $promptHash = hash('sha256', $seedPrompt);
    $currentUserId = getCurrentUserId();
    if ($currentUserId === null) {
        throw new RuntimeException('User session is invalid.');
    }

    $createdCategories = 0;
    $createdThreads = 0;
    $createdComments = 0;

    $pdo->beginTransaction();

    $insertCategoryStmt = $pdo->prepare(
        'INSERT INTO categories (name, created_by, source, ai_prompt_hash) VALUES (?, ?, ?, ?)'
    );
    $insertThreadStmt = $pdo->prepare(
        'INSERT INTO threads (title, content, user_id, category_id, source, ai_prompt_hash) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insertCommentStmt = $pdo->prepare(
        'INSERT INTO comments (content, user_id, thread_id, source, ai_prompt_hash) VALUES (?, ?, ?, ?, ?)'
    );

    foreach ($generated['categories'] as $category) {
        $insertCategoryStmt->execute([
            $category['name'],
            $currentUserId,
            'ai',
            $promptHash,
        ]);
        $categoryId = (int) $pdo->lastInsertId();
        $createdCategories++;

        foreach ($category['threads'] as $thread) {
            $insertThreadStmt->execute([
                $thread['title'],
                $thread['content'],
                $currentUserId,
                $categoryId,
                'ai',
                $promptHash,
            ]);
            $threadId = (int) $pdo->lastInsertId();
            $createdThreads++;

            foreach ($thread['comments'] as $commentText) {
                $insertCommentStmt->execute([
                    $commentText,
                    $currentUserId,
                    $threadId,
                    'ai',
                    $promptHash,
                ]);
                $createdComments++;
            }
        }
    }

    $pdo->commit();
    $_SESSION['last_ai_generation_at'] = time();

    $updateRunStmt = $pdo->prepare(
        'UPDATE ai_generation_runs
         SET created_categories = ?, created_threads = ?, created_comments = ?, status = ?, completed_at = NOW()
         WHERE id = ?'
    );
    $updateRunStmt->execute([
        $createdCategories,
        $createdThreads,
        $createdComments,
        'success',
        $runId,
    ]);

    if ($isAjax) {
        jsonResponse([
            'success' => true,
            'summary' => [
                'categories' => $createdCategories,
                'threads' => $createdThreads,
                'comments' => $createdComments,
                'run_id' => $runId,
            ],
        ]);
    }

    header('Location: ?page=admin&msg=ai_generated');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($runId !== null) {
        $updateRunFailStmt = $pdo->prepare(
            'UPDATE ai_generation_runs
             SET status = ?, error_message = ?, completed_at = NOW()
             WHERE id = ?'
        );
        $updateRunFailStmt->execute([
            'failed',
            substr($e->getMessage(), 0, 500),
            $runId,
        ]);
    }

    if ($isAjax) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }

    die($e->getMessage());
}
