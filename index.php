<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$page_title = 'Bem-vindo';
require __DIR__ . '/includes/header.php';
?>
<div class="card center">
    <h1>⚽ <?= e(APP_NAME) ?></h1>
    <p class="muted">O bolão da Copa do Mundo 2026 entre amigos. Sem propaganda, só diversão.</p>
    <p>Palpite o placar dos jogos, dispute o ranking e mostre quem entende de futebol.</p>
    <p style="margin-top:20px">
        <a class="btn" href="<?= e(APP_URL) ?>/registro.php">Criar minha conta</a>
        &nbsp;
        <a class="btn btn-secondary" href="<?= e(APP_URL) ?>/login.php">Já tenho conta</a>
    </p>
    <p style="margin-top:14px"><a href="<?= e(APP_URL) ?>/regras.php">Ver as regras de pontuação</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
