<?php

require_once __DIR__ . '/ai_client.php';

function generateForumStructure(
    string $seedPrompt,
    string $language,
    int $categoryCount,
    int $threadsPerCategory,
    int $commentsPerThread
): array {
    $raw = generateForumContentWithAiProvider(
        $seedPrompt,
        $language,
        $categoryCount,
        $threadsPerCategory,
        $commentsPerThread
    );

    return normalizeGeneratedForumStructure(
        $raw,
        $categoryCount,
        $threadsPerCategory,
        $commentsPerThread
    );
}

function normalizeGeneratedForumStructure(
    array $payload,
    int $categoryCount,
    int $threadsPerCategory,
    int $commentsPerThread
): array {
    if (!isset($payload['categories']) || !is_array($payload['categories'])) {
        throw new RuntimeException('AI payload must contain a categories array.');
    }

    if (count($payload['categories']) !== $categoryCount) {
        throw new RuntimeException('AI payload category count does not match the requested amount.');
    }

    $normalized = ['categories' => []];

    foreach ($payload['categories'] as $category) {
        if (!is_array($category)) {
            throw new RuntimeException('Each category entry must be an object.');
        }

        $categoryName = trim((string) ($category['name'] ?? ''));
        if ($categoryName === '' || strlen($categoryName) > 100) {
            throw new RuntimeException('Category name is missing or exceeds 100 characters.');
        }

        if (!isset($category['threads']) || !is_array($category['threads'])) {
            throw new RuntimeException('Each category must contain a threads array.');
        }

        if (count($category['threads']) !== $threadsPerCategory) {
            throw new RuntimeException('AI payload thread count does not match the requested amount.');
        }

        $normalizedCategory = [
            'name' => $categoryName,
            'threads' => [],
        ];

        foreach ($category['threads'] as $thread) {
            if (!is_array($thread)) {
                throw new RuntimeException('Each thread entry must be an object.');
            }

            $threadTitle = trim((string) ($thread['title'] ?? ''));
            $threadContent = trim((string) ($thread['content'] ?? ''));

            if ($threadTitle === '' || strlen($threadTitle) > 255) {
                throw new RuntimeException('Thread title is missing or exceeds 255 characters.');
            }

            if ($threadContent === '') {
                throw new RuntimeException('Thread content cannot be empty.');
            }

            if (!isset($thread['comments']) || !is_array($thread['comments'])) {
                throw new RuntimeException('Each thread must contain a comments array.');
            }

            if (count($thread['comments']) !== $commentsPerThread) {
                throw new RuntimeException('AI payload comment count does not match the requested amount.');
            }

            $normalizedComments = [];
            foreach ($thread['comments'] as $comment) {
                $commentText = trim((string) $comment);
                if ($commentText === '') {
                    throw new RuntimeException('Comment content cannot be empty.');
                }
                $normalizedComments[] = $commentText;
            }

            $normalizedCategory['threads'][] = [
                'title' => $threadTitle,
                'content' => $threadContent,
                'comments' => $normalizedComments,
            ];
        }

        $normalized['categories'][] = $normalizedCategory;
    }

    return $normalized;
}
