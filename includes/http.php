<?php

function isAjaxRequest(): bool
{
    return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
}

function jsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function requestError(string $message, int $status, string $redirect): never
{
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'error' => $message], $status);
    }

    startSession();
    $_SESSION['flash_error'] = $message;
    header('Location: ' . $redirect);
    exit;
}

function requestSuccess(array $payload, string $redirect): never
{
    if (isAjaxRequest()) {
        jsonResponse(['success' => true] + $payload);
    }

    header('Location: ' . $redirect);
    exit;
}

function requirePost(string $redirect = '?page=index'): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        requestError('Richiesta non valida.', 405, $redirect);
    }
}

function requireValidCsrf(string $redirect = '?page=index'): void
{
    if (!verifyCsrfToken()) {
        requestError('La sessione è scaduta. Riprova.', 403, $redirect);
    }
}

function requireAuthenticatedApi(string $redirect = '?page=login'): int
{
    $userId = getCurrentUserId();
    if ($userId === null) {
        requestError('Devi accedere per continuare.', 401, $redirect);
    }
    return $userId;
}

function requireAdminApi(string $redirect = '?page=index'): int
{
    $userId = requireAuthenticatedApi('?page=login');
    if (!isAdmin()) {
        requestError('Non hai i permessi necessari.', 403, $redirect);
    }
    return $userId;
}
