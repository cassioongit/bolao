<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_admin();

$counts = [
    'usuarios' => (int)db()->query('SELECT COUNT(*) c FROM users')->fetch()['c'],
    'boloes'   => (int)db()->query('SELECT COUNT(*) c FROM pools')->fetch()['c'],
    'jogos'    => (int)db()->query('SELECT COUNT(*) c FROM matches')->fetch()['c'],
    'encerrados' => (int)db()->query("SELECT COUNT(*) c FROM matches WHERE status='encerrado'")->fetch()['c'],
];

$pendingStmt = db()->prepare(
    "SELECT m.id, m.kickoff_utc, m.fase, m.grupo,
            th.nome AS home_nome, th.bandeira AS home_bandeira,
            ta.nome AS away_nome, ta.bandeira AS away_bandeira
     FROM matches m
     LEFT JOIN teams th ON th.id = m.home_team_id
     LEFT JOIN teams ta ON ta.id = m.away_team_id
     WHERE m.status <> 'encerrado'
       AND m.kickoff_utc < UTC_TIMESTAMP()
     ORDER BY m.kickoff_utc ASC, m.id ASC
     LIMIT 5"
);
$pendingStmt->execute();
$pendingResults = $pendingStmt->fetchAll();
$pendingCount = (int)db()->query("SELECT COUNT(*) c FROM matches WHERE status <> 'encerrado' AND kickoff_utc < UTC_TIMESTAMP()")->fetch()['c'];

$page_title = 'Administração';
require __DIR__ . '/../includes/header.php';
?>
<h1>Administração</h1>
<?php if ($pendingCount > 0): ?>
<div class="card alert-card">
    <h2>Resultados pendentes</h2>
    <p><strong><?= $pendingCount ?></strong> jogo(s) já passaram do horário e ainda não tiveram resultado lançado.</p>
    <p><a class="btn" href="<?= e(APP_URL) ?>/admin/resultados.php">Ir para lançar resultados</a></p>
    <div class="pending-list">
        <?php foreach ($pendingResults as $m): ?>
            <div class="pending-item">
                <strong><?= e(side_label($m, 'home')) ?> x <?= e(side_label($m, 'away')) ?></strong>
                <span class="muted"><?= e(fmt_datetime($m['kickoff_utc'])) ?> · <?= e(stage_label($m['fase'])) ?><?= $m['grupo'] ? ' ' . e($m['grupo']) : '' ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
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
