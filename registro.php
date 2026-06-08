<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$erros = [];
$nome = $apelido = $email = $email2 = '';
// Se veio de um convite, preservamos o token para entrar no bolão após cadastrar
$invite = isset($_GET['convite']) ? trim($_GET['convite']) : ($_POST['convite'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $nome  = trim($_POST['nome'] ?? '');
    $apelido = trim($_POST['apelido'] ?? '');
    $email = mb_strtolower(trim($_POST['email'] ?? ''));
    $email2 = mb_strtolower(trim($_POST['email2'] ?? ''));
    $senha = $_POST['senha'] ?? '';
    $senha2 = $_POST['senha2'] ?? '';

    if (mb_strlen($nome) < 2)                       $erros[] = 'Informe seu nome.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if ($email !== $email2)                         $erros[] = 'Os e-mails não conferem. Verifique se digitou correto.';
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
            'INSERT INTO users (nome, apelido, email, senha_hash, is_admin, email_verificado, token_verificacao, criado_em)
             VALUES (?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())'
        );
        $verifyToken = random_token(32);
        $stmt->execute([$nome, $apelido ?: null, $email, password_hash($senha, PASSWORD_DEFAULT), $isAdmin, $verificado, $verificado ? null : $verifyToken]);
        $userId = (int)db()->lastInsertId();

        if (REQUIRE_EMAIL_VERIFICATION && !$verificado) {
            $verifyUrl = APP_URL . '/verificar-email.php?token=' . urlencode($verifyToken);
            $body = "<h2>Bem-vindo(a) ao Bolão da Paz!</h2>";
            $body .= "<p>Clique no link abaixo para confirmar seu e-mail:</p>";
            $body .= "<p><a href='{$verifyUrl}' style='display: inline-block; padding: 10px 20px; background: #2ecc71; color: white; text-decoration: none; border-radius: 5px;'>Confirmar E-mail</a></p>";
            $body .= "<p>Ou copie e cole este link no navegador:</p>";
            $body .= "<p><code>{$verifyUrl}</code></p>";
            $body .= "<p><small>Se você não criou esta conta, ignore este e-mail.</small></p>";
            send_email($email, 'Confirme seu e-mail - Bolão da Paz', $body);
            flash('Conta criada! Verifique seu e-mail (inclusive Spam) para confirmar.', 'info');
            redirect('login.php');
        } else {
            login_user($userId);
            flash('Conta criada! Bem-vindo(a), ' . $nome . '.', 'success');
            if ($invite !== '') {
                redirect('convite.php?t=' . urlencode($invite));
            }
            redirect('dashboard.php');
        }
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
        <label>Nome completo</label>
        <input type="text" name="nome" value="<?= e($nome) ?>" required maxlength="80">
        <label>Apelido (opcional)</label>
        <input type="text" name="apelido" value="<?= e($apelido) ?>" maxlength="40" placeholder="Como você quer ser chamado no ranking">

        <label>E-mail</label>
        <input type="email" name="email" value="<?= e($email) ?>" required maxlength="190">

        <label>Confirme o e-mail</label>
        <input type="email" name="email2" value="<?= e($email2) ?>" required maxlength="190" placeholder="Digite o mesmo e-mail acima">
        <p class="muted" style="font-size: 0.9rem; margin-top: 6px;">⚠️ Verifique se digitou o e-mail corretamente. Vamos enviar um link de confirmação. Se não receber, procure na pasta de <strong>Spam</strong>.</p>

        <label style="margin-top: 12px">Senha</label>
        <input type="password" name="senha" required minlength="6">
        <label>Repita a senha</label>
        <input type="password" name="senha2" required minlength="6">
        <p style="margin-top:16px"><button class="btn btn-block" type="submit">Criar conta</button></p>
    </form>
    <p class="center muted">Já tem conta? <a href="<?= e(APP_URL) ?>/login.php<?= $invite ? '?convite=' . e(urlencode($invite)) : '' ?>">Entrar</a></p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const email1 = document.querySelector('input[name="email"]');
    const email2 = document.querySelector('input[name="email2"]');
    const senha1 = document.querySelector('input[name="senha"]');
    const senha2 = document.querySelector('input[name="senha2"]');

    function createFeedback(inputEl, isValid) {
        let feedback = inputEl.parentElement.querySelector('.validation-feedback');
        if (!feedback) {
            feedback = document.createElement('small');
            feedback.className = 'validation-feedback';
            inputEl.parentElement.appendChild(feedback);
        }
        if (isValid === null) {
            feedback.textContent = '';
            inputEl.style.borderColor = '';
        } else if (isValid) {
            feedback.textContent = '✓ Correto';
            feedback.style.color = '#27ae60';
            inputEl.style.borderColor = '#27ae60';
        } else {
            feedback.textContent = '✗ Não confere';
            feedback.style.color = '#e74c3c';
            inputEl.style.borderColor = '#e74c3c';
        }
    }

    // Validação de email em tempo real
    email2.addEventListener('input', function() {
        if (email2.value === '') {
            createFeedback(email2, null);
        } else if (email1.value === email2.value) {
            createFeedback(email2, true);
        } else {
            createFeedback(email2, false);
        }
    });

    email1.addEventListener('input', function() {
        if (email2.value !== '') {
            email2.dispatchEvent(new Event('input'));
        }
    });

    // Validação de senha em tempo real
    senha2.addEventListener('input', function() {
        if (senha2.value === '') {
            createFeedback(senha2, null);
        } else if (senha1.value === senha2.value) {
            createFeedback(senha2, true);
        } else {
            createFeedback(senha2, false);
        }
    });

    senha1.addEventListener('input', function() {
        if (senha2.value !== '') {
            senha2.dispatchEvent(new Event('input'));
        }
    });
});
</script>

<style>
.validation-feedback {
    display: block;
    margin-top: 4px;
    font-size: 0.85rem;
}
input[style*="border-color"] {
    transition: border-color 0.2s;
}
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
