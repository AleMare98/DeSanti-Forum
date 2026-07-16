<?php

function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn(): bool
{
    startSession();
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    startSession();
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getCurrentUserId(): ?int
{
    startSession();
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function getCurrentUsername(): ?string
{
    startSession();
    return $_SESSION['username'] ?? null;
}

function loginUser(int $userId, string $username, string $role): void
{
    startSession();
    session_regenerate_id(true);
    $_SESSION['user_id']     = $userId;
    $_SESSION['username']    = $username;
    $_SESSION['user_role']   = $role;
}

function logoutUser(): void
{
    startSession();
    session_unset();
    session_destroy();
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ?page=login');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        header('Location: ?page=index');
        exit;
    }
}
