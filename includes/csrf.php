<?php
/**
 * Proteção CSRF para formulários e endpoints AJAX.
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = random_token(24);
    }
    return $_SESSION['csrf'];
}

/** Campo oculto para formulários. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Valida o token enviado (POST field "csrf" ou header X-CSRF-Token). */
function csrf_check(): bool
{
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent) && hash_equals($_SESSION['csrf'] ?? '', $sent);
}

/** Aborta se o CSRF for inválido. */
function csrf_require(): void
{
    if (!csrf_check()) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            json_response(['ok' => false, 'error' => 'Sessão expirada. Recarregue a página.'], 419);
        }
        http_response_code(419);
        die('Falha de validação (CSRF). Recarregue a página e tente de novo.');
    }
}
