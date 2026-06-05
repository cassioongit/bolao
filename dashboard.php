<?php
require __DIR__ . '/includes/bootstrap.php';
$u = require_login();

// Criar bolão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'criar') {
    csrf_require();
    $nome = trim($_POST['nome'] ?? '');
    if (mb_strlen($nome) < 2) {
        flash('Dê um nome ao bolão.', 'error');
    } else {
        $poolId = create_pool($nome, (int)$u['id']);
        flash('Bolão "' . $nome . '" criado! Convide a galera.', 'success');
        redirect('bolao.php?id=' . $poolId . '&aba=membros');
    }
    redirect('dashboard.php');
}

// Entrar por token (colado no campo)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'entrar') {
    csrf_require();
    $raw = trim($_POST['token'] ?? '');
    // Aceita o token puro OU a URL completa de convite
    if (preg_match('/[?&]t=([A-Za-z0-9]+)/', $raw, $m)) {
        $raw = $m[1];
    }
    redirect('convite.php?t=' . urlencode($raw));
}

$pools = my_pools((int)$u['id']);

$page_title = 'Meus bolões';
require __DIR__ . '/includes/header.php';
?>
<h1>Olá, <?= e($u['nome']) ?> 👋</h1>

<div class="card">
    <h2>Meus bolões</h2>
    <?php if (!$pools): ?>
        <p class="muted">Você ainda não participa de nenhum bolão. Crie o seu ou entre por um link de convite.</p>
    <?php else: ?>
        <ul class="pool-list">
            <?php foreach ($pools as $p): ?>
                <li>
                    <span>
                        <a href="<?= e(APP_URL) ?>/bolao.php?id=<?= (int)$p['id'] ?>"><strong><?= e($p['nome']) ?></strong></a>
                        <span class="muted">· <?= (int)$p['total_membros'] ?> participante(s)<?= $p['papel'] === 'owner' ? ' · você é o dono' : '' ?></span>
                    </span>
                    <a class="btn btn-sm" href="<?= e(APP_URL) ?>/palpites.php?id=<?= (int)$p['id'] ?>">Palpitar</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Criar um novo bolão</h2>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="criar">
        <label>Nome do bolão</label>
        <input type="text" name="nome" placeholder="Ex.: Bolão da Paz" maxlength="80" required>
        <p style="margin-top:14px"><button class="btn" type="submit">Criar bolão</button></p>
    </form>
</div>

<div class="card">
    <h2>Entrar em um bolão</h2>
    <p class="muted">Cole abaixo o link de convite (ou o código) que te enviaram.</p>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="entrar">
        <label>Link ou código do convite</label>
        <input type="text" name="token" placeholder="https://... ou código" required>
        <p style="margin-top:14px"><button class="btn btn-secondary" type="submit">Entrar no bolão</button></p>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
