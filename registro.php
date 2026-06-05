<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$erros = [];
$nome = $email = '';
// Se veio de um convite, preservamos o token para entrar no bolão após cadastrar
$invite = isset($_GET['convite']) ? trim($_GET['convite']) : ($_POST['convite'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $nome  = trim($_POST['nome'] ?? '');
    $email = mb_strtolower(trim($_POST['email'] ?? ''));
    $senha = $_POST['senha'] ?? '';
    $senha2 = $_POST['senha2'] ?? '';

    if (mb_strlen($nome) < 2)                       $erros[] = 'Informe seu nome.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if (strlen($senha) < 6)                          $erros[] = 'A senha precisa ter ao menos 6 caracteres.';
    if ($senha !== $senha2)                          $erros[] = 'As senhas não conferem.';

    if (!$erros) {
        $chk = db()->prepare('SELECT id FROM users WHERE email = ?');
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $erros[] = 'Já existe uma conta com esse e-mail.';
        }
    }

    if (!$erros) {
        // Primeiro usuário pode virar admin
        $total = (int)db()->query('SELECT COUNT(*) c FROM users')->fetch()['c'];
        $isAdmin = (FIRST_USER_IS_ADMIN && $total === 0) ? 1 : 0;
        $verificado = REQUIRE_EMAIL_VERIFICATION ? 0 : 1;

        $stmt = db()->prepare(
            'INSERT INTO users (nome, email, senha_hash, is_admin, email_verificado, criado_em)
             VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP())'
        );
        $stmt->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $isAdmin, $verificado]);
        $userId = (int)db()->lastInsertId();

        login_user($userId);
        flash('Conta criada! Bem-vindo(a), ' . $nome . '.', 'success');

        if ($invite !== '') {
            redirect('convite.php?t=' . urlencode($invite));
        }
        redirect('dashboard.php');
    }
}

$page_title = 'Criar conta';
require __DIR__ . '/includes/header.php';
?>
<div class="card form-narrow">
    <h1>Criar conta</h1>
    <?php foreach ($erros as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="convite" value="<?= e($invite) ?>">
        <label>Nome</label>
        <input type="text" name="nome" value="<?= e($nome) ?>" required maxlength="80">
        <label>E-mail</label>
        <input type="email" name="email" value="<?= e($email) ?>" required maxlength="190">
        <label>Senha</label>
        <input type="password" name="senha" required minlength="6">
        <label>Repita a senha</label>
        <input type="password" name="senha2" required minlength="6">
        <p style="margin-top:16px"><button class="btn btn-block" type="submit">Criar conta</button></p>
    </form>
    <p class="center muted">Já tem conta? <a href="<?= e(APP_URL) ?>/login.php<?= $invite ? '?convite=' . e(urlencode($invite)) : '' ?>">Entrar</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
