<?php

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function escapeUrl(string $value): string
{
    return urlencode($value);
}

function sanitizePost(string $key): string
{
    $value = $_POST[$key] ?? '';
    return trim($value);
}
