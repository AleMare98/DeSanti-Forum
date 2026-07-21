<?php

require_once __DIR__ . '/../config/ai.php';

function generateForumContentWithAiProvider(
    string $seedPrompt,
    string $language,
    int $categoryCount,
    int $threadsPerCategory,
    int $commentsPerThread
): array {
    if (!function_exists('curl_init')) {
        throw new RuntimeException('cURL extension is required for AI generation.');
    }

    $systemPrompt = 'You generate realistic forum content dedicated to ITS Umbria Academy. '
        . 'Use a cordial and formal tone. Return only valid JSON using this schema: '
        . '{"categories":[{"name":"string","threads":[{"title":"string","content":"string","comments":["string"]}]}]}. '
        . 'No markdown, no explanations, no extra keys.';

    $userPrompt = 'Seed prompt: ' . $seedPrompt . "\n"
        . 'Language: ' . $language . "\n"
        . 'Create exactly ' . $categoryCount . ' categories. '
        . 'Each category must have exactly ' . $threadsPerCategory . ' threads. '
        . 'Each thread must have exactly ' . $commentsPerThread . ' comments.';

    // UTF-8 byte length is a conservative upper bound for BPE token count.
    // Keep additional room for chat-message framing so the input stays below 8,000 tokens.
    $inputBytes = strlen($systemPrompt) + strlen($userPrompt);
    if ($inputBytes + 256 >= AI_MAX_INPUT_BYTES) {
        throw new InvalidArgumentException('La richiesta supera il limite di input consentito.');
    }

    $provider = strtolower((string) AI_PROVIDER);
    $model = '';
    $token = '';
    $url = '';

    if ($provider === 'openai') {
        $model = AI_OPENAI_MODEL;
        $token = AI_OPENAI_API_KEY;
        $url = 'https://api.openai.com/v1/chat/completions';
    } elseif ($provider === 'github') {
        $model = AI_GITHUB_MODEL;
        $token = AI_GITHUB_TOKEN;
        $url = 'https://models.github.ai/inference/chat/completions';
    } else {
        throw new RuntimeException('Unsupported AI provider: ' . $provider);
    }

    if ($token === '') {
        throw new RuntimeException('AI provider token is not configured for provider: ' . $provider);
    }

    $payload = [
        'model' => $model,
        'response_format' => ['type' => 'json_object'],
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ],
        'temperature' => 0.9,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => AI_REQUEST_TIMEOUT_SECONDS,
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);

    $rawResponse = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($rawResponse === false) {
        throw new RuntimeException('AI request failed: ' . $curlError);
    }

    $decoded = json_decode($rawResponse, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('AI provider returned invalid JSON.');
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        $providerMessage = $decoded['error']['message'] ?? 'Unknown provider error.';
        throw new RuntimeException('AI provider error: ' . $providerMessage);
    }

    $content = $decoded['choices'][0]['message']['content'] ?? null;
    if (!is_string($content) || trim($content) === '') {
        throw new RuntimeException('AI provider returned an empty content payload.');
    }

    $generated = json_decode($content, true);
    if (!is_array($generated)) {
        throw new RuntimeException('AI response content is not valid JSON.');
    }

    return $generated;
}
