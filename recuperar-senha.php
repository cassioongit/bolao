<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/email.php';

$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$msg = '';
$msgType = 'info';
$modo = $token !== '' ? 'reset' : 'pedido';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    if ($modo === 'pedido') {
        // Etapa 1: usuário pede o link
        $email = mb_strtolower(trim($_POST['email'] ?? ''));
        $stmt = db()->prepare('SELECT id, nome FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if ($u) {
            $tk = random_token(24);
            $upd = db()->prepare(
                'UPDATE users SET token_reset = ?, token_reset_exp = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 2 HOUR) WHERE id = ?'
            );
            $upd->execute([$tk, $u['id']]);
            $link = APP_URL . '/recuperar-senha.php?token=' . urlencode($tk);
            $corpo = '<p>Olá, ' . e($u['nome']) . '!</p>'
                . '<p>Para criar uma nova senha no ' . e(APP_NAME) . ', clique no link abaixo (válido por 2 horas):</p>'
                . '<p><a href="' . e($link) . '">' . e($link) . '</a></p>';
            send_email($email, 'Recuperar senha — ' . APP_NAME, $corpo);
        }
        // Mensagem genérica (não revela se o e-mail existe)
        $msg = 'Se o e-mail estiver cadastrado, enviamos um link para redefinir a senha.';
        $msgType = 'success';
        $modo = 'feito';
    } else {
        // Etapa 2: redefinir senha com token
        $senha  = $_POST['senha'] ?? '';
        $senha2 = $_POST['senha2'] ?? '';
        $stmt = db()->prepare(
            'SELECT id FROM users WHERE token_reset = ? AND token_reset_exp > UTC_TIMESTAMP()'
        );
        $stmt->execute([$token]);
        $u = $stmt->fetch();
        if (!$u) {
            $msg = 'Link inválido ou expirado. Peça um novo.';
            $msgType = 'error';
            $modo = 'pedido';
            $token = '';
        } elseif (strlen($senha) < 6) {
            $msg = 'A senha precisa ter ao menos 6 caracteres.';
            $msgType = 'error';
        } elseif ($senha !== $senha2) {
            $msg = 'As senhas não conferem.';
            $msgType = 'error';
        } else {
            $upd = db()->prepare(
                'UPDATE users SET senha_hash = ?, token_reset = NULL, token_reset_exp = NULL WHERE id = ?'
            );
            $upd->execute([password_hash($senha, PASSWORD_DEFAULT), $u['id']]);
            flash('Senha alterada com sucesso. Faça login.', 'success');
            redirect('login.php');
        }
    }
}

$page_title = 'Recuperar senha';
require __DIR__ . '/includes/header.php';
?>
<div class="card form-narrow">
    <h1>Recuperar senha</h1>
    <?php if ($msg): ?><div class="flash flash-<?= e($msgType) ?>"><?= e($msg) ?></div><?php endif; ?>

    <?php if ($modo === 'feito'): ?>
        <?php if (MAIL_DRIVER === 'log'): ?>
            <div class="flash flash-info">Ambiente local: o e-mail foi salvo em <code>nimbalyst-local/outbox</code>.</div>
        <?php endif; ?>
        <p class="center"><a href="<?= e(APP_URL) ?>/login.php">Voltar para o login</a></p>
    <?php elseif ($modo === 'reset'): ?>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <label>Nova senha</label>
            <input type="password" name="senha" required minlength="6">
            <label>Repita a nova senha</label>
            <input type="password" name="senha2" required minlength="6">
            <p style="margin-top:16px"><button class="btn btn-block" type="submit">Salvar nova senha</button></p>
        </form>
    <?php else: ?>
        <p class="muted">Informe seu e-mail e enviaremos um link para criar uma nova senha.</p>
        <form method="post">
            <?= csrf_field() ?>
            <label>E-mail</label>
            <input type="email" name="email" required>
            <p style="margin-top:16px"><button class="btn btn-block" type="submit">Enviar link</button></p>
        </form>
        <p class="center"><a href="<?= e(APP_URL) ?>/login.php">Voltar</a></p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
