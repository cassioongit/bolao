<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$erro = '';
$email = '';
$invite = isset($_GET['convite']) ? trim($_GET['convite']) : ($_POST['convite'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $email = mb_strtolower(trim($_POST['email'] ?? ''));
    $senha = $_POST['senha'] ?? '';

    $u = attempt_login($email, $senha);
    if (!$u) {
        $erro = 'E-mail ou senha incorretos.';
    } elseif (REQUIRE_EMAIL_VERIFICATION && (int)$u['email_verificado'] !== 1) {
        $erro = 'Confirme seu e-mail antes de entrar.';
    } else {
        login_user((int)$u['id']);
        if ($invite !== '') {
            redirect('convite.php?t=' . urlencode($invite));
        }
        $dest = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
        unset($_SESSION['redirect_after_login']);
        redirect($dest);
    }
}

$page_title = 'Entrar';
require __DIR__ . '/includes/header.php';
?>
<div class="card form-narrow">
    <h1>Entrar</h1>
    <?php if ($erro): ?><div class="flash flash-error"><?= e($erro) ?></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="convite" value="<?= e($invite) ?>">
        <label>E-mail</label>
        <input type="email" name="email" value="<?= e($email) ?>" required>
        <label>Senha</label>
        <input type="password" name="senha" required>
        <p style="margin-top:16px"><button class="btn btn-block" type="submit">Entrar</button></p>
    </form>
    <p class="center"><a href="<?= e(APP_URL) ?>/recuperar-senha.php">Esqueci minha senha</a></p>
    <p class="center muted">Não tem conta? <a href="<?= e(APP_URL) ?>/registro.php<?= $invite ? '?convite=' . e(urlencode($invite)) : '' ?>">Criar conta</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
