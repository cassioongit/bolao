<?php
require __DIR__ . '/includes/bootstrap.php';

$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$msg = '';
$msgType = 'info';
$modo = $token !== '' ? 'reset' : 'pedido';
$senhaAlterada = false;

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
            $corpo = '<h2>Recuperação de Senha</h2>'
                . '<p>Olá, ' . e($u['nome']) . '!</p>'
                . '<p>Para criar uma nova senha no ' . e(APP_NAME) . ', clique no link abaixo (válido por 2 horas):</p>'
                . '<p><a href="' . e($link) . '" style="display: inline-block; padding: 10px 20px; background: #2ecc71; color: white; text-decoration: none; border-radius: 5px;">Redefinir Senha</a></p>'
                . '<p>Ou copie e cole este endereço: <code>' . e($link) . '</code></p>'
                . '<p><small>Se você não solicitou isso, ignore este email.</small></p>';
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
            $senhaAlterada = true;
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

<?php if ($senhaAlterada): ?>
<div id="success-modal" class="modal-overlay show">
    <div class="modal">
        <div class="modal-body">
            <h2 style="text-align: center; color: #27ae60; margin-bottom: 20px;">✓ Senha Alterada com Sucesso!</h2>
            <p style="text-align: center; margin-bottom: 24px;">Sua senha foi redefinida. Agora você pode fazer login com a nova senha.</p>
            <a href="<?= e(APP_URL) ?>/login.php" class="btn btn-block" style="text-align: center;">Ir para Login</a>
        </div>
    </div>
</div>

<style>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-overlay.show {
    display: flex;
}

.modal {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    max-width: 400px;
    padding: 0;
    animation: slideUp 0.3s ease-out;
}

.modal-body {
    padding: 30px 25px;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
