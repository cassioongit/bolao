<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $matchId = (int)($_POST['match_id'] ?? 0);
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {
        $h = (int)($_POST['home_score'] ?? 0);
        $a = (int)($_POST['away_score'] ?? 0);
        db()->prepare("UPDATE matches SET home_score=?, away_score=?, status='encerrado' WHERE id=?")
            ->execute([$h, $a, $matchId]);
        recalc_match_points($matchId);
        flash('Resultado salvo e pontos calculados.', 'success');
    } elseif ($acao === 'reabrir') {
        db()->prepare("UPDATE matches SET home_score=NULL, away_score=NULL, status='agendado' WHERE id=?")
            ->execute([$matchId]);
        recalc_match_points($matchId);
        flash('Jogo reaberto (pontos zerados).', 'info');
    }
    redirect('resultados.php' . (isset($_GET['fase']) ? '?fase=' . urlencode($_GET['fase']) : ''));
}

$fase = $_GET['fase'] ?? 'todos';
$where = '';
$params = [];
if ($fase !== 'todos') { $where = 'WHERE m.fase = ?'; $params[] = $fase; }

$sql = "SELECT m.*, th.nome home_nome, th.bandeira home_bandeira,
               ta.nome away_nome, ta.bandeira away_bandeira
        FROM matches m
        LEFT JOIN teams th ON th.id = m.home_team_id
        LEFT JOIN teams ta ON ta.id = m.away_team_id
        $where
        ORDER BY m.kickoff_utc, m.id";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$matches = $stmt->fetchAll();

$page_title = 'Lançar resultados';
require __DIR__ . '/../includes/header.php';
?>
<h1>Lançar resultados</h1>
<p><a href="<?= e(APP_URL) ?>/admin/index.php">← Admin</a></p>

<div class="card">
    <form method="get" style="margin-bottom:10px">
        <label>Fase</label>
        <select name="fase" onchange="this.form.submit()">
            <option value="todos" <?= $fase === 'todos' ? 'selected' : '' ?>>Todas</option>
            <?php foreach (['grupos','r32','r16','qf','sf','terceiro','final'] as $f): ?>
                <option value="<?= e($f) ?>" <?= $fase === $f ? 'selected' : '' ?>><?= e(stage_label($f)) ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php foreach ($matches as $m): ?>
        <div class="match" style="border-bottom:1px solid var(--border)">
            <div class="team home"><?= e(side_label($m, 'home')) ?></div>
            <form method="post" class="score" style="margin:0" action="resultados.php?fase=<?= e($fase) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="match_id" value="<?= (int)$m['id'] ?>">
                <input type="hidden" name="acao" value="salvar">
                <input type="number" name="home_score" min="0" max="99" style="width:48px" value="<?= $m['home_score'] !== null ? (int)$m['home_score'] : '' ?>">
                <strong>x</strong>
                <input type="number" name="away_score" min="0" max="99" style="width:48px" value="<?= $m['away_score'] !== null ? (int)$m['away_score'] : '' ?>">
                <button class="btn btn-sm" type="submit">Salvar</button>
            </form>
            <div class="team away"><?= e(side_label($m, 'away')) ?></div>
            <div class="match-meta" style="grid-column:1 / -1; display:flex; justify-content:space-between">
                <span><?= e(fmt_datetime($m['kickoff_utc'])) ?> · <?= e(stage_label($m['fase'])) ?><?= $m['grupo'] ? ' ' . e($m['grupo']) : '' ?></span>
                <span>
                    <?php if ($m['status'] === 'encerrado'): ?>
                        <span class="saved-badge">✓ encerrado</span>
                        <form method="post" style="display:inline" action="resultados.php?fase=<?= e($fase) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="match_id" value="<?= (int)$m['id'] ?>">
                            <input type="hidden" name="acao" value="reabrir">
                            <button class="btn btn-sm btn-secondary" type="submit">Reabrir</button>
                        </form>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$matches): ?><p class="muted">Nenhum jogo cadastrado nesta fase.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
