<?php
require __DIR__ . '/includes/bootstrap.php';
$u = require_login();

$poolId = (int)($_GET['id'] ?? 0);
$pool = get_pool($poolId);
if (!$pool) { http_response_code(404); die('Bolão não encontrado.'); }
require_pool_member($poolId, (int)$u['id']);

$fase = $_GET['fase'] ?? 'geral';
$fasesValidas = ['grupos','r32','r16','qf','sf','terceiro','final'];

if ($fase !== 'geral' && in_array($fase, $fasesValidas, true)) {
    // Ranking só da fase escolhida (apenas pontos de placar)
    $sql = 'SELECT u.id, u.nome,
                   COALESCE(SUM(pr.pontos),0) AS pontos,
                   SUM(CASE WHEN pr.pontos = ? THEN 1 ELSE 0 END) AS exatos,
                   0 AS pontos_bonus
            FROM pool_members pm
            JOIN users u ON u.id = pm.user_id
            LEFT JOIN predictions pr ON pr.pool_id = pm.pool_id AND pr.user_id = u.id
            LEFT JOIN matches m ON m.id = pr.match_id
            WHERE pm.pool_id = ? AND (m.fase = ? OR m.fase IS NULL)
            GROUP BY u.id, u.nome
            ORDER BY pontos DESC, exatos DESC, u.nome ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute([(int)$pool['pts_exato'], $poolId, $fase]);
    $rows = $stmt->fetchAll();
} else {
    $fase = 'geral';
    $rows = pool_ranking($poolId, $pool);
}

$page_title = 'Ranking — ' . $pool['nome'];
require __DIR__ . '/includes/header.php';
?>
<h1><?= e($pool['nome']) ?></h1>
<?php render_pool_tabs($pool, 'ranking'); ?>

<div class="card">
    <form method="get" style="margin-bottom:14px">
        <input type="hidden" name="id" value="<?= $poolId ?>">
        <label>Filtrar por fase</label>
        <select name="fase" onchange="this.form.submit()">
            <option value="geral" <?= $fase === 'geral' ? 'selected' : '' ?>>Geral (tudo + bônus)</option>
            <?php foreach ($fasesValidas as $f): ?>
                <option value="<?= e($f) ?>" <?= $fase === $f ? 'selected' : '' ?>><?= e(stage_label($f)) ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <table>
        <thead>
            <tr><th class="rank-pos">#</th><th>Participante</th><th style="text-align:right">Bônus</th><th style="text-align:right">Exatos</th><th class="rank-pts">Pontos</th></tr>
        </thead>
        <tbody>
        <?php $pos = 0; foreach ($rows as $r):
            $pos++;
            $total = (int)$r['pontos'] + (int)$r['pontos_bonus'];
            $me = (int)$r['id'] === (int)$u['id'];
        ?>
            <tr class="<?= $me ? 'me' : '' ?>">
                <td class="rank-pos"><?= $pos ?>º</td>
                <td><?= e($r['nome']) ?><?= $me ? ' <span class="muted">(você)</span>' : '' ?></td>
                <td style="text-align:right"><?= (int)$r['pontos_bonus'] ?></td>
                <td style="text-align:right"><?= (int)$r['exatos'] ?></td>
                <td class="rank-pts"><?= $total ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="5" class="muted center">Sem participantes ainda.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <p class="muted" style="font-size:.8rem">Desempate: maior número de placares exatos. Os pontos aparecem assim que o admin lança o resultado de cada jogo.</p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
