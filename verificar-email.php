<?php
require __DIR__ . '/includes/bootstrap.php';

$token = $_GET['token'] ?? '';
$msg = '';
$success = false;

if (!$token) {
    $msg = 'Token de verificação não fornecido.';
} else {
    $user = db()->prepare('SELECT id, email FROM users WHERE token_verificacao = ?')->execute([$token])->fetch();
    if (!$user) {
        $msg = 'Token inválido ou expirado.';
    } else {
        db()->prepare('UPDATE users SET email_verificado = 1, token_verificacao = NULL WHERE id = ?')->execute([$user['id']]);
        $msg = 'E-mail confirmado com sucesso! Você já pode fazer login.';
        $success = true;
    }
}

$page_title = 'Verificar E-mail';
require __DIR__ . '/includes/header.php';
?>
<div class="card form-narrow">
    <h1>Verificação de E-mail</h1>
    <?php if ($success): ?>
        <div class="flash flash-success"><?= e($msg) ?></div>
        <p style="margin-top: 16px; text-align: center;">
            <a class="btn" href="<?= e(APP_URL) ?>/login.php">Ir para Login</a>
        </p>
    <?php else: ?>
        <div class="flash flash-error"><?= e($msg) ?></div>
        <p style="margin-top: 16px; text-align: center;">
            <a href="<?= e(APP_URL) ?>/registro.php">Criar nova conta</a>
        </p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
