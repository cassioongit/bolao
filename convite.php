<?php
require __DIR__ . '/includes/bootstrap.php';

$token = trim($_GET['t'] ?? '');
if ($token === '') {
    http_response_code(404);
    die('Convite inválido.');
}

$pool = get_pool_by_token($token);
if (!$pool) {
    $page_title = 'Convite';
    require __DIR__ . '/includes/header.php';
    echo '<div class="card center"><h1>Convite inválido</h1><p class="muted">Este link de convite não existe ou expirou.</p></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Precisa estar logado; se não estiver, manda para login/registro preservando o convite
if (!is_logged_in()) {
    $page_title = 'Convite — ' . $pool['nome'];
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="card center">
        <h1>Você foi convidado! 🎉</h1>
        <p>Entre ou crie sua conta para participar do bolão <strong><?= e($pool['nome']) ?></strong>.</p>
        <p style="margin-top:18px">
            <a class="btn" href="<?= e(APP_URL) ?>/registro.php?convite=<?= e(urlencode($token)) ?>">Criar conta</a>
            &nbsp;
            <a class="btn btn-secondary" href="<?= e(APP_URL) ?>/login.php?convite=<?= e(urlencode($token)) ?>">Já tenho conta</a>
        </p>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$u = current_user();
add_pool_member((int)$pool['id'], (int)$u['id']);
flash('Você entrou no bolão "' . $pool['nome'] . '"! Bora palpitar.', 'success');
redirect('palpites.php?id=' . (int)$pool['id']);
