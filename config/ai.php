<?php

if (!defined('AI_PROVIDER')) {
    $provider = getenv('AI_PROVIDER') ?: 'openai';
    define('AI_PROVIDER', strtolower($provider));
}

if (!defined('AI_OPENAI_MODEL')) {
    define('AI_OPENAI_MODEL', getenv('OPENAI_MODEL') ?: 'gpt-4.1-mini');
}

if (!defined('AI_OPENAI_API_KEY')) {
    define('AI_OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');
}

if (!defined('AI_GITHUB_MODEL')) {
    define('AI_GITHUB_MODEL', getenv('GITHUB_MODEL') ?: 'openai/gpt-4.1-mini');
}

if (!defined('AI_GITHUB_TOKEN')) {
    define('AI_GITHUB_TOKEN', getenv('GITHUB_TOKEN') ?: '');
}

if (!defined('AI_REQUEST_TIMEOUT_SECONDS')) {
    define('AI_REQUEST_TIMEOUT_SECONDS', 30);
}

if (!defined('AI_MAX_INPUT_BYTES')) {
    define('AI_MAX_INPUT_BYTES', 7000);
}

if (!defined('AI_RATE_LIMIT_SECONDS')) {
    define('AI_RATE_LIMIT_SECONDS', 15);
}

if (!defined('AI_FOLLOWUP_MODEL')) {
    define('AI_FOLLOWUP_MODEL', getenv('GITHUB_FOLLOWUP_MODEL') ?: AI_GITHUB_MODEL);
}

if (!defined('AI_FOLLOWUP_TIMEOUT_SECONDS')) {
    define('AI_FOLLOWUP_TIMEOUT_SECONDS', 20);
}

if (!defined('AI_FOLLOWUP_MAX_LENGTH')) {
    define('AI_FOLLOWUP_MAX_LENGTH', 2000);
}

if (!defined('AI_MIN_CATEGORIES')) {
    define('AI_MIN_CATEGORIES', 1);
}

if (!defined('AI_MAX_CATEGORIES')) {
    define('AI_MAX_CATEGORIES', 5);
}

if (!defined('AI_MIN_THREADS_PER_CATEGORY')) {
    define('AI_MIN_THREADS_PER_CATEGORY', 1);
}

if (!defined('AI_MAX_THREADS_PER_CATEGORY')) {
    define('AI_MAX_THREADS_PER_CATEGORY', 5);
}

if (!defined('AI_MIN_COMMENTS_PER_THREAD')) {
    define('AI_MIN_COMMENTS_PER_THREAD', 0);
}

if (!defined('AI_MAX_COMMENTS_PER_THREAD')) {
    define('AI_MAX_COMMENTS_PER_THREAD', 6);
}
