<?php
/**
 * Autenticação e sessão.
 */

/** Usuário logado (array) ou null. */
function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    if (empty($_SESSION['user_id'])) {
        return $cache = null;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    return $cache = ($u ?: null);
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $u = current_user();
    return $u && (int)$u['is_admin'] === 1;
}

/** Garante que há usuário logado; senão manda para o login. */
function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'dashboard.php';
        flash('Faça login para continuar.', 'info');
        redirect('login.php');
    }
    return $u;
}

/** Garante que o usuário é admin. */
function require_admin(): array
{
    $u = require_login();
    if ((int)$u['is_admin'] !== 1) {
        http_response_code(403);
        die('Acesso restrito ao administrador.');
    }
    return $u;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Tenta autenticar por e-mail/senha. Retorna o usuário ou null. */
function attempt_login(string $email, string $senha): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([mb_strtolower(trim($email))]);
    $u = $stmt->fetch();
    if ($u && password_verify($senha, $u['senha_hash'])) {
        return $u;
    }
    return null;
}
