<?php
require __DIR__ . '/includes/bootstrap.php';
$u = require_login();

$poolId = (int)($_GET['id'] ?? 0);
$pool = get_pool($poolId);
if (!$pool) { http_response_code(404); die('Bolão não encontrado.'); }
require_pool_member($poolId, (int)$u['id']);

$chaves = [
    'campeao'    => ['Campeão',          (int)$pool['pts_campeao']],
    'vice'       => ['Vice-campeão',     (int)$pool['pts_vice']],
    'terceiro'   => ['3º lugar',         (int)$pool['pts_terceiro']],
    'quarto'     => ['4º lugar',         (int)$pool['pts_quarto']],
    'artilheiro' => ['Time do artilheiro',(int)$pool['pts_artilheiro']],
];

// Bônus trava quando a Copa começa (primeiro jogo)
$primeiro = db()->query('SELECT MIN(kickoff_utc) k FROM matches')->fetch();
$bonusLocked = $primeiro && $primeiro['k'] && match_is_locked($primeiro['k']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    if ($bonusLocked) {
        flash('Os palpites de bônus já estão fechados (a Copa começou).', 'error');
        redirect('bonus.php?id=' . $poolId);
    }
    $pdo = db();
    $ins = $pdo->prepare(
        'INSERT INTO bonus_predictions (pool_id, user_id, chave, team_id)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE team_id = VALUES(team_id), pontos = NULL'
    );
    foreach ($chaves as $chave => $_) {
        $teamId = (int)($_POST[$chave] ?? 0);
        if ($teamId > 0) {
            $ins->execute([$poolId, (int)$u['id'], $chave, $teamId]);
        }
    }
    flash('Palpites de bônus salvos!', 'success');
    redirect('bonus.php?id=' . $poolId);
}

// Seleções e palpites atuais
$teams = db()->query('SELECT id, nome, bandeira, grupo FROM teams ORDER BY grupo, nome')->fetchAll();
$stmt = db()->prepare('SELECT chave, team_id FROM bonus_predictions WHERE pool_id = ? AND user_id = ?');
$stmt->execute([$poolId, (int)$u['id']]);
$atual = [];
foreach ($stmt as $r) { $atual[$r['chave']] = (int)$r['team_id']; }

$page_title = 'Bônus — ' . $pool['nome'];
require __DIR__ . '/includes/header.php';
?>
<h1><?= e($pool['nome']) ?></h1>
<?php render_pool_tabs($pool, 'bonus'); ?>

<div class="card">
    <h2>Palpites de bônus</h2>
    <p class="muted">Escolha as seleções. Pontos só contam no final do torneio.
        <?= $bonusLocked ? '<strong>Fechado — a Copa já começou.</strong>' : 'Você pode alterar até o início da Copa.' ?>
    </p>
    <form method="post">
        <?= csrf_field() ?>
        <?php foreach ($chaves as $chave => [$label, $pts]): ?>
            <label><?= e($label) ?> <span class="muted">(<?= $pts ?> pts)</span></label>
            <select name="<?= e($chave) ?>" <?= $bonusLocked ? 'disabled' : '' ?>>
                <option value="0">— escolha —</option>
                <?php foreach ($teams as $t): ?>
                    <option value="<?= (int)$t['id'] ?>" <?= ($atual[$chave] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>>
                        <?= e(($t['bandeira'] ? $t['bandeira'] . ' ' : '') . $t['nome']) ?><?= $t['grupo'] ? ' (Grupo ' . e($t['grupo']) . ')' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endforeach; ?>
        <?php if (!$bonusLocked): ?>
            <p style="margin-top:16px"><button class="btn" type="submit">Salvar bônus</button></p>
        <?php endif; ?>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
