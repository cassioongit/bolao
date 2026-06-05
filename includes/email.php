<?php
/**
 * Envio de e-mail.
 * `MAIL_DRIVER=mail`: usa mail() do PHP.
 * `MAIL_DRIVER=log`: grava o e-mail em arquivo para testes locais.
 */

function mail_from_email(): string
{
    if (MAIL_FROM_EMAIL !== '') {
        return MAIL_FROM_EMAIL;
    }

    $host = preg_replace('#^https?://#', '', APP_URL);
    $host = preg_replace('#/.*$#', '', $host);
    $host = preg_replace('/:\d+$/', '', $host);

    return 'no-reply@' . $host;
}

function log_email_locally(string $para, string $assunto, string $htmlCorpo): bool
{
    $dir = MAIL_LOG_DIR;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return false;
    }

    $stamp = gmdate('Ymd_His');
    $safeTo = preg_replace('/[^a-z0-9_.@-]+/i', '-', $para);
    $file = rtrim($dir, '/') . '/' . $stamp . '_' . $safeTo . '.html';

    $meta = '<!--'
        . "\nTo: {$para}"
        . "\nSubject: {$assunto}"
        . "\nGenerated-At-UTC: " . gmdate('c')
        . "\n-->\n";

    return file_put_contents($file, $meta . $htmlCorpo) !== false;
}

function send_email(string $para, string $assunto, string $htmlCorpo): bool
{
    if (MAIL_DRIVER === 'log') {
        return log_email_locally($para, $assunto, $htmlCorpo);
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . MAIL_FROM_NAME . ' <' . mail_from_email() . '>',
    ];
    $assuntoEnc = '=?UTF-8?B?' . base64_encode($assunto) . '?=';

    return @mail($para, $assuntoEnc, $htmlCorpo, implode("\r\n", $headers));
}
