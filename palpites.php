<?php
require __DIR__ . '/includes/bootstrap.php';
$u = require_login();

$poolId = (int)($_GET['id'] ?? 0);
$pool = get_pool($poolId);
if (!$pool) { http_response_code(404); die('Bolão não encontrado.'); }
$member = require_pool_member($poolId, (int)$u['id']);

// Salvar palpite padrão (e aplicar aos jogos abertos)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'default') {
    csrf_require();
    $h = (int)($_POST['default_home'] ?? 0);
    $a = (int)($_POST['default_away'] ?? 0);
    if ($h >= 0 && $a >= 0 && $h <= 99 && $a <= 99) {
        set_member_default($poolId, (int)$u['id'], $h, $a);
        flash("Palpite padrão $h x $a aplicado aos jogos abertos. Ajuste o que quiser antes de cada partida.", 'success');
    }
    redirect('palpites.php?id=' . $poolId);
}

// Carrega jogos com seleções
$sql = 'SELECT m.*,
               th.nome AS home_nome, th.bandeira AS home_bandeira,
               ta.nome AS away_nome, ta.bandeira AS away_bandeira
        FROM matches m
        LEFT JOIN teams th ON th.id = m.home_team_id
        LEFT JOIN teams ta ON ta.id = m.away_team_id
        ORDER BY m.kickoff_utc, m.id';
$matches = db()->query($sql)->fetchAll();

// Palpites do usuário
$preds = user_predictions_map($poolId, (int)$u['id'], $member);
$defH = $member['palpite_padrao_home'];
$defA = $member['palpite_padrao_away'];

// Agrupa por data (no fuso de Brasília)
$grupos = [];
foreach ($matches as $m) {
    $dia = fmt_datetime($m['kickoff_utc'], 'D, d/m/Y');
    $grupos[$dia][] = $m;
}

$page_title = 'Palpites — ' . $pool['nome'];
require __DIR__ . '/includes/header.php';
?>
<h1><?= e($pool['nome']) ?></h1>
<?php render_pool_tabs($pool, 'palpites'); ?>

<?php if (!$matches): ?>
    <div class="card"><p class="muted">Os jogos ainda não foram cadastrados. Fale com o administrador.</p></div>
<?php else: ?>

<div class="card">
    <h3 style="margin-top:0">Palpite padrão</h3>
    <p class="muted">Defina um placar e aplique a todos os jogos abertos de uma vez. Depois é só ajustar o que quiser — vale até 5 minutos antes de cada jogo.</p>
    <form method="post" class="default-bar">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="default">
        <div class="score">
            <input type="number" name="default_home" id="default-home" min="0" max="20" value="<?= $defH !== null ? (int)$defH : '' ?>" placeholder="0">
            <strong>x</strong>
            <input type="number" name="default_away" id="default-away" min="0" max="20" value="<?= $defA !== null ? (int)$defA : '' ?>" placeholder="0">
        </div>
        <button class="btn" type="submit">Salvar padrão e aplicar</button>
        <button class="btn btn-secondary" type="button" id="fill-all">Só preencher esta tela</button>
    </form>
    <p class="muted" style="font-size:.8rem;margin-bottom:0">“Salvar padrão e aplicar” grava no banco os jogos abertos sem palpite. “Só preencher esta tela” preenche os campos visíveis (são salvos automaticamente ao mudar).</p>
</div>

<div id="palpites" data-pool="<?= $poolId ?>">
<?php foreach ($grupos as $dia => $jogos): ?>
    <div class="date-group">
        <h3><?= e(ucfirst($dia)) ?></h3>
        <div class="card" style="padding-top:6px;padding-bottom:6px">
        <?php foreach ($jogos as $m):
            $locked = match_is_locked($m['kickoff_utc']);
            $pred = $preds[(int)$m['id']] ?? null;
            $ph = $pred ? $pred['home'] : '';
            $pa = $pred ? $pred['away'] : '';
            $hora = fmt_datetime($m['kickoff_utc'], 'H:i');
            $encerrado = $m['status'] === 'encerrado';
        ?>
            <div class="match <?= $locked ? 'locked' : '' ?>" data-match="<?= (int)$m['id'] ?>">
                <div class="team home"><span><?= e(side_label($m, 'home')) ?></span></div>
                <div class="score">
                    <input type="number" class="score-input home" min="0" max="20" value="<?= $ph === '' ? '' : (int)$ph ?>" <?= $locked ? 'disabled' : '' ?>>
                    <strong>x</strong>
                    <input type="number" class="score-input away" min="0" max="20" value="<?= $pa === '' ? '' : (int)$pa ?>" <?= $locked ? 'disabled' : '' ?>>
                </div>
                <div class="team away"><span><?= e(side_label($m, 'away')) ?></span></div>
                <div class="match-meta" style="grid-column:1 / -1; display:flex; justify-content:space-between">
                    <span><?= e($hora) ?> · <?= e($m['sede'] ?? '') ?> · <?= e(stage_label($m['fase'])) ?><?= $m['grupo'] ? ' ' . e($m['grupo']) : '' ?></span>
                    <span>
                        <?php if ($encerrado): ?>
                            <span class="muted">Final: <strong><?= (int)$m['home_score'] ?> x <?= (int)$m['away_score'] ?></strong></span>
                            <?php if ($pred && $pred['pontos'] !== null): ?>
                                <span class="saved-badge">+<?= (int)$pred['pontos'] ?> pts</span>
                            <?php endif; ?>
                        <?php elseif ($locked): ?>
                            <span class="lock-badge">🔒 fechado</span>
                        <?php else: ?>
                            <span class="row-status muted"></span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
