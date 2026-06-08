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
    $multiplierCase = classic_multiplier_case_sql('m.fase');
    $scenarioCase = classic_scenario_case_sql();
    $sql = "SELECT u.id, u.nome,
                   COALESCE(SUM(COALESCE(pr.pontos, 0) * {$multiplierCase}),0) AS pontos,
                   SUM(CASE WHEN {$scenarioCase} = '" . CLASSIC_SCENARIO_EXACT . "' THEN 1 ELSE 0 END) AS exatos,
                   SUM(CASE WHEN {$scenarioCase} = '" . CLASSIC_SCENARIO_WINNER_AND_ONE_TEAM_SCORE . "' THEN 1 ELSE 0 END) AS winner_plus_one,
                   SUM(CASE WHEN {$scenarioCase} = '" . CLASSIC_SCENARIO_WINNER_ONLY . "' THEN 1 ELSE 0 END) AS winner_only,
                   SUM(CASE WHEN {$scenarioCase} = '" . CLASSIC_SCENARIO_DRAW_NON_EXACT . "' THEN 1 ELSE 0 END) AS draw_non_exact,
                   SUM(CASE WHEN {$scenarioCase} = '" . CLASSIC_SCENARIO_ONE_TEAM_SCORE_ONLY . "' THEN 1 ELSE 0 END) AS one_team_only,
                   SUM(CASE WHEN {$scenarioCase} = '" . CLASSIC_SCENARIO_MISS . "' THEN 1 ELSE 0 END) AS misses,
                   0 AS pontos_bonus
            FROM pool_members pm
            JOIN users u ON u.id = pm.user_id
            LEFT JOIN matches m ON m.status = 'encerrado' AND m.fase = ?
            LEFT JOIN predictions pr
                   ON pr.pool_id = pm.pool_id
                  AND pr.user_id = u.id
                  AND pr.match_id = m.id
            WHERE pm.pool_id = ?
            GROUP BY u.id, u.nome
            ORDER BY pontos DESC,
                     exatos DESC,
                     winner_plus_one DESC,
                     winner_only DESC,
                     draw_non_exact DESC,
                     one_team_only DESC,
                     misses ASC,
                     u.nome ASC";
    $stmt = db()->prepare($sql);
    $stmt->execute([$fase, $poolId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['tiebreak'] = classic_ranking_tiebreak_breakdown($row);
    }
    unset($row);
} else {
    $fase = 'geral';
    $rows = pool_ranking($poolId, $pool);
}

$totals = array_map(static fn(array $row): int => (int)$row['pontos'] + (int)$row['pontos_bonus'], $rows);

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

    <table class="responsive-table">
        <thead>
            <tr><th class="rank-pos">#</th><th>Participante</th><th style="text-align:right">Bônus</th><th style="text-align:right">Exatos</th><th class="rank-pts">Pontos</th></tr>
        </thead>
        <tbody>
        <?php $pos = 0; foreach ($rows as $r):
            $pos++;
            $total = (int)$r['pontos'] + (int)$r['pontos_bonus'];
            $me = (int)$r['id'] === (int)$u['id'];
            $rowIndex = $pos - 1;
            $tieRelevant = ($rowIndex > 0 && $totals[$rowIndex - 1] === $total) ||
                          ($rowIndex < count($totals) - 1 && $totals[$rowIndex + 1] === $total);
        ?>
            <tr class="<?= $me ? 'me' : '' ?>">
                <td data-label="#" class="rank-pos"><?= $pos ?>º</td>
                <td data-label="Participante"><?= e(user_display_name($r)) ?><?= $me ? ' <span class="muted">(você)</span>' : '' ?></td>
                <td data-label="Bônus" style="text-align:right"><?= (int)$r['pontos_bonus'] ?></td>
                <td data-label="Exatos" style="text-align:right"><?= (int)$r['exatos'] ?></td>
                <td data-label="Pontos" class="rank-pts"><?= $total ?></td>
            </tr>
            <?php if ($tieRelevant): ?>
            <tr class="tie-row<?= $me ? ' me' : '' ?>">
                <td></td>
                <td colspan="4">
                    <details class="tie-explainer">
                        <summary>Ver desempate</summary>
                        <div class="explanation-grid">
                            <?php foreach (($r['tiebreak']['criteria'] ?? []) as $criterion): ?>
                                <div>
                                    <span class="muted"><?= e($criterion['label']) ?></span>
                                    <strong><?= (int)$criterion['count'] ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </td>
            </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="5" class="muted center">Sem participantes ainda.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <p class="muted" style="font-size:.8rem">Desempate: placar exato, vencedor + gols de um time, vencedor, empate sem placar exato, um time correto e, por último, menos erros/ausências.</p>
</div>

<div class="card">
    <h2>Multiplicadores por fase</h2>
    <table>
        <thead><tr><th>Fase</th><th class="rank-pts">Multiplicador</th></tr></thead>
        <tbody>
        <?php foreach (classic_stage_multipliers() as $stageKey => $multiplier): ?>
            <tr>
                <td><?= e(stage_label($stageKey)) ?></td>
                <td class="rank-pts">x<?= (int)$multiplier ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
