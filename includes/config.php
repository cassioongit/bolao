<?php
/**
 * Configuração central do Bolão da Paz (Copa 2026).
 *
 * As credenciais e ajustes vêm do arquivo .env na raiz do projeto.
 * Copie .env.example para .env e preencha com os dados do seu banco remoto.
 */

/** Carrega variáveis do arquivo .env (uma vez). */
function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        // Remove aspas envolventes, se houver
        if (strlen($val) >= 2 && ($val[0] === '"' || $val[0] === "'") && $val[strlen($val) - 1] === $val[0]) {
            $val = substr($val, 1, -1);
        }
        $_ENV[$key] = $val;
        putenv("$key=$val");
    }
}

/** Lê uma variável de ambiente com valor padrão. */
function env(string $key, $default = null)
{
    $v = $_ENV[$key] ?? getenv($key);
    if ($v === false || $v === null || $v === '') {
        return $default;
    }
    // Normaliza booleanos textuais
    $low = strtolower((string)$v);
    if ($low === 'true')  return true;
    if ($low === 'false') return false;
    return $v;
}

/** Descobre a URL base atual quando o ambiente não fornece uma definitiva. */
function detect_app_url(): string
{
    $https = $_SERVER['HTTPS'] ?? '';
    $scheme = (!empty($https) && $https !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8050';
    return $scheme . '://' . $host;
}

load_env(dirname(__DIR__) . '/.env');

// ----- Banco de dados (MySQL) -----
define('DB_HOST',    env('DB_HOST', '127.0.0.1'));
define('DB_PORT',    env('DB_PORT', '3306'));
define('DB_NAME',    env('DB_NAME', 'bolao'));
define('DB_USER',    env('DB_USER', 'root'));
define('DB_PASS',    env('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

// ----- Aplicação -----
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_NAME', env('APP_NAME', 'Bolão da Paz'));
// URL base SEM barra no final (ex.: https://seudominio.com.br)
$configuredAppUrl = trim((string)env('APP_URL', ''));
if ($configuredAppUrl === '' || $configuredAppUrl === 'https://seudominio.com.br') {
    $configuredAppUrl = detect_app_url();
}
define('APP_URL',  rtrim($configuredAppUrl, '/'));

// Minutos antes do início do jogo em que o palpite trava
define('LOCK_MINUTES', (int)env('LOCK_MINUTES', 5));

// Fuso horário usado para EXIBIR datas/horários (armazenamos tudo em UTC)
define('DISPLAY_TZ', env('DISPLAY_TZ', 'America/Sao_Paulo'));

// Verificação de e-mail no cadastro (false = conta liberada após registrar)
define('REQUIRE_EMAIL_VERIFICATION', (bool)env('REQUIRE_EMAIL_VERIFICATION', false));

// O primeiro usuário cadastrado vira admin automaticamente
define('FIRST_USER_IS_ADMIN', (bool)env('FIRST_USER_IS_ADMIN', true));

// Mostrar erros na tela? (true em dev, false em produção)
define('SHOW_ERRORS', (bool)env('SHOW_ERRORS', true));

// ----- E-mail -----
define('MAIL_DRIVER', env('MAIL_DRIVER', APP_ENV === 'local' ? 'log' : 'mail'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', APP_NAME));
define('MAIL_FROM_EMAIL', env('MAIL_FROM_EMAIL', ''));
define('MAIL_LOG_DIR', env('MAIL_LOG_DIR', dirname(__DIR__) . '/nimbalyst-local/outbox'));

// SMTP (quando MAIL_DRIVER=smtp)
define('MAIL_HOST', env('MAIL_HOST', ''));
define('MAIL_PORT', (int)env('MAIL_PORT', 587));
define('MAIL_USERNAME', env('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', env('MAIL_PASSWORD', ''));
define('MAIL_ENCRYPTION', env('MAIL_ENCRYPTION', 'tls'));

// Internamente trabalhamos sempre em UTC
date_default_timezone_set('UTC');

error_reporting(E_ALL);
ini_set('display_errors', SHOW_ERRORS ? '1' : '0');
