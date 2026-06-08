<?php
/**
 * Funções utilitárias compartilhadas.
 */

/** Escapa texto para HTML. */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Retorna apelido ou primeiro nome do usuário. */
function user_display_name(array $user): string
{
    if (!empty($user['apelido'])) {
        return $user['apelido'];
    }
    $nome = $user['nome'] ?? '';
    $partes = explode(' ', $nome);
    return $partes[0] ?? $nome;
}

/** Envia email (SMTP ou log). */
function send_email(string $to, string $subject, string $body): bool
{
    $from = MAIL_FROM_EMAIL ?: 'noreply@' . parse_url(APP_URL, PHP_URL_HOST);
    $fromName = MAIL_FROM_NAME;

    if (MAIL_DRIVER === 'smtp') {
        return send_email_smtp($to, $subject, $body, $from, $fromName);
    } else {
        return send_email_log($to, $subject, $body, $from, $fromName);
    }
}

/** Envia via SMTP real com PHPMailer. */
function send_email_smtp(string $to, string $subject, string $body, string $from, string $fromName): bool
{
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION === 'ssl' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        return $mail->send();
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("Erro ao enviar email: " . $mail->ErrorInfo);
        return false;
    }
}

/** Salva email em arquivo (para testes). */
function send_email_log(string $to, string $subject, string $body, string $from, string $fromName): bool
{
    $dir = MAIL_LOG_DIR;
    @mkdir($dir, 0755, true);
    $file = $dir . '/' . time() . '_' . md5($to) . '.txt';
    $content = "To: {$to}\nFrom: {$fromName} <{$from}>\nSubject: {$subject}\n\n{$body}";
    return file_put_contents($file, $content) !== false;
}

/** Redireciona e encerra. */
function redirect(string $path): void
{
    // Aceita caminho relativo ("dashboard.php") ou absoluto ("https://...").
    if (!preg_match('#^https?://#', $path)) {
        $path = APP_URL . '/' . ltrim($path, '/');
    }
    header('Location: ' . $path);
    exit;
}

/** Gera um token aleatório seguro (hex). */
function random_token(int $bytes = 16): string
{
    return bin2hex(random_bytes($bytes));
}

/** Timestamp atual em UTC (objeto). */
function now_utc(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone('UTC'));
}

/** Converte um DATETIME (string UTC do banco) para exibição no fuso de Brasília. */
function fmt_datetime(?string $utc, string $format = 'd/m/Y H:i'): string
{
    if (!$utc) {
        return '';
    }
    $dt = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
    $dt = $dt->setTimezone(new DateTimeZone(DISPLAY_TZ));
    return $dt->format($format);
}

/** Retorna true se o jogo já está travado para palpites. */
function match_is_locked(string $kickoff_utc): bool
{
    $kick = new DateTimeImmutable($kickoff_utc, new DateTimeZone('UTC'));
    $deadline = $kick->modify('-' . LOCK_MINUTES . ' minutes');
    return now_utc() >= $deadline;
}

/** Mensagens flash simples via sessão. */
function flash(string $msg, string $type = 'info'): void
{
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}

function get_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Resposta JSON para endpoints AJAX. */
function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Lê JSON do corpo da requisição (para endpoints AJAX). */
function json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** Nome legível das fases. */
function stage_label(string $stage): string
{
    return [
        'grupos'   => 'Fase de Grupos',
        'r32'      => 'Oitavas (32 avos)',
        'r16'      => 'Oitavas de Final',
        'qf'       => 'Quartas de Final',
        'sf'       => 'Semifinal',
        'terceiro' => 'Disputa de 3º lugar',
        'final'    => 'Final',
    ][$stage] ?? $stage;
}
