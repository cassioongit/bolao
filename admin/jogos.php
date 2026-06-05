<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $id = (int)($_POST['match_id'] ?? 0);
    $home = (int)($_POST['home_team_id'] ?? 0) ?: null;
    $away = (int)($_POST['away_team_id'] ?? 0) ?: null;
    // datetime-local chega no fuso de Brasília; convertemos para UTC
    $kick = trim($_POST['kickoff_local'] ?? '');
    $params = [$home, $away];
    $set = 'home_team_id=?, away_team_id=?';
    if ($kick !== '') {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $kick, new DateTimeZone(DISPLAY_TZ));
        if ($dt) {
            $dt->setTimezone(new DateTimeZone('UTC'));
            $set .= ', kickoff_utc=?';
            $params[] = $dt->format('Y-m-d H:i:s');
        }
    }
    $params[] = $id;
    db()->prepare("UPDATE matches SET $set WHERE id=?")->execute($params);
    flash('Jogo atualizado.', 'success');
    redirect('jogos.php' . (isset($_GET['fase']) ? '?fase=' . urlencode($_GET['fase']) : ''));
}

$fase = $_GET['fase'] ?? 'r16';
$where = '';
$params = [];
if ($fase !== 'todos') { $where = 'WHERE m.fase = ?'; $params[] = $fase; }
$sql = "SELECT m.* FROM matches m $where ORDER BY m.kickoff_utc, m.id";
$stmt = db()->prepare($sql); $stmt->execute($params);
$matches = $stmt->fetchAll();
$teams = db()->query('SELECT id, nome, bandeira FROM teams ORDER BY nome')->fetchAll();

function team_options(array $teams, ?int $sel): string {
    $out = '<option value="0">— a definir —</option>';
    foreach ($teams as $t) {
        $s = ((int)$t['id'] === (int)$sel) ? ' selected' : '';
        $out .= '<option value="' . (int)$t['id'] . '"' . $s . '>' . e(($t['bandeira'] ? $t['bandeira'] . ' ' : '') . $t['nome']) . '</option>';
    }
    return $out;
}

$page_title = 'Jogos & mata-mata';
require __DIR__ . '/../includes/header.php';
?>
<h1>Jogos & mata-mata</h1>
<p><a href="<?= e(APP_URL) ?>/admin/index.php">← Admin</a></p>
<div class="card">
    <form method="get" style="margin-bottom:10px">
        <label>Fase</label>
        <select name="fase" onchange="this.form.submit()">
            <?php foreach (['todos'=>'Todas','grupos'=>'Grupos','r32'=>'32 avos','r16'=>'Oitavas','qf'=>'Quartas','sf'=>'Semis','terceiro'=>'3º lugar','final'=>'Final'] as $k=>$lbl): ?>
                <option value="<?= e($k) ?>" <?= $fase === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <p class="muted" style="font-size:.85rem">Defina as seleções das fases eliminatórias conforme os classificados. O horário está no fuso de Brasília.</p>

    <?php foreach ($matches as $m):
        $localKick = fmt_datetime($m['kickoff_utc'], 'Y-m-d\TH:i');
    ?>
        <form method="post" action="jogos.php?fase=<?= e($fase) ?>" class="card" style="margin:10px 0; padding:12px">
            <?= csrf_field() ?>
            <input type="hidden" name="match_id" value="<?= (int)$m['id'] ?>">
            <div class="muted" style="font-size:.8rem"><?= e(stage_label($m['fase'])) ?><?= $m['grupo'] ? ' ' . e($m['grupo']) : '' ?> ·
                <?= e($m['home_placeholder'] ?? '') ?> x <?= e($m['away_placeholder'] ?? '') ?></div>
            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:end; margin-top:6px">
                <div style="flex:1; min-width:140px"><label>Mandante</label><select name="home_team_id"><?= team_options($teams, $m['home_team_id'] ? (int)$m['home_team_id'] : null) ?></select></div>
                <div style="flex:1; min-width:140px"><label>Visitante</label><select name="away_team_id"><?= team_options($teams, $m['away_team_id'] ? (int)$m['away_team_id'] : null) ?></select></div>
                <div><label>Início (Brasília)</label><input type="datetime-local" name="kickoff_local" value="<?= e($localKick) ?>"></div>
                <div><button class="btn btn-sm" type="submit">Salvar</button></div>
            </div>
        </form>
    <?php endforeach; ?>
    <?php if (!$matches): ?><p class="muted">Nenhum jogo nesta fase.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
