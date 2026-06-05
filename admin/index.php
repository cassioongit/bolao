<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_admin();

$counts = [
    'usuarios' => (int)db()->query('SELECT COUNT(*) c FROM users')->fetch()['c'],
    'boloes'   => (int)db()->query('SELECT COUNT(*) c FROM pools')->fetch()['c'],
    'jogos'    => (int)db()->query('SELECT COUNT(*) c FROM matches')->fetch()['c'],
    'encerrados' => (int)db()->query("SELECT COUNT(*) c FROM matches WHERE status='encerrado'")->fetch()['c'],
];

$page_title = 'Administração';
require __DIR__ . '/../includes/header.php';
?>
<h1>Administração</h1>
<div class="card">
    <p class="muted">
        <?= $counts['usuarios'] ?> usuários ·
        <?= $counts['boloes'] ?> bolões ·
        <?= $counts['encerrados'] ?>/<?= $counts['jogos'] ?> jogos com resultado
    </p>
    <ul class="pool-list">
        <li><a href="<?= e(APP_URL) ?>/admin/resultados.php"><strong>Lançar resultados</strong></a><span class="muted">Digitar o placar final dos jogos (calcula os pontos)</span></li>
        <li><a href="<?= e(APP_URL) ?>/admin/jogos.php"><strong>Jogos & mata-mata</strong></a><span class="muted">Editar horários e definir as seleções das fases eliminatórias</span></li>
        <li><a href="<?= e(APP_URL) ?>/admin/selecoes.php"><strong>Seleções</strong></a><span class="muted">Ver/editar as seleções e grupos</span></li>
        <li><a href="<?= e(APP_URL) ?>/admin/torneio.php"><strong>Gabarito dos bônus</strong></a><span class="muted">Definir campeão, vice etc. ao fim da Copa</span></li>
        <li><a href="<?= e(APP_URL) ?>/admin/usuarios.php"><strong>Usuários</strong></a><span class="muted">Promover admin / ver cadastro</span></li>
    </ul>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
