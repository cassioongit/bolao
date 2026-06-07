<?php
require __DIR__ . '/includes/bootstrap.php';

// Se vier ?id de um bolão do qual o usuário participa, usamos a pontuação dele.
$poolId = (int)($_GET['id'] ?? 0);
$pool = null;
if ($poolId && is_logged_in() && pool_member($poolId, (int)current_user()['id'])) {
    $pool = get_pool($poolId);
}

$classic = classic_score_points();
$pts = [
    'exato'      => $classic[CLASSIC_SCENARIO_EXACT],
    'winner_one' => $classic[CLASSIC_SCENARIO_WINNER_AND_ONE_TEAM_SCORE],
    'winner'     => $classic[CLASSIC_SCENARIO_WINNER_ONLY],
    'draw'       => $classic[CLASSIC_SCENARIO_DRAW_NON_EXACT],
    'team_only'  => $classic[CLASSIC_SCENARIO_ONE_TEAM_SCORE_ONLY],
    'campeao'    => $pool['pts_campeao']    ?? 30,
    'vice'       => $pool['pts_vice']       ?? 20,
    'terceiro'   => $pool['pts_terceiro']   ?? 15,
    'quarto'     => $pool['pts_quarto']     ?? 10,
    'artilheiro' => $pool['pts_artilheiro'] ?? 15,
];

$page_title = 'Regras';
require __DIR__ . '/includes/header.php';
?>
<?php if ($pool): ?>
    <h1><?= e($pool['nome']) ?></h1>
    <?php render_pool_tabs($pool, ''); ?>
<?php else: ?>
    <h1>Regras de pontuação</h1>
<?php endif; ?>

<div class="card">
    <h2>Como você pontua nos jogos</h2>
    <p>Para cada partida, você dá um palpite de placar. Quando o jogo acaba, usamos a regra Classic oficial com o placar válido ao fim do tempo normal + prorrogação. Disputa por pênaltis não muda essa pontuação.</p>
    <table>
        <thead><tr><th>Situação do seu palpite</th><th class="rank-pts">Pontos</th></tr></thead>
        <tbody>
            <tr>
                <td><strong>Placar exato</strong> — você acertou os gols dos dois times.<br>
                    <span class="muted">Ex.: você palpitou 2 x 1 e o jogo terminou 2 x 1.</span></td>
                <td class="rank-pts"><?= (int)$pts['exato'] ?></td>
            </tr>
            <tr>
                <td><strong>Acertou o vencedor e os gols de um dos times</strong>.<br>
                    <span class="muted">Ex.: você palpitou 2 x 1 e terminou 2 x 0.</span></td>
                <td class="rank-pts"><?= (int)$pts['winner_one'] ?></td>
            </tr>
            <tr>
                <td><strong>Acertou só o vencedor</strong>.<br>
                    <span class="muted">Ex.: você palpitou 2 x 1 e terminou 4 x 0.</span></td>
                <td class="rank-pts"><?= (int)$pts['winner'] ?></td>
            </tr>
            <tr>
                <td><strong>Acertou que foi empate, mas não o placar exato</strong>.<br>
                    <span class="muted">Ex.: você palpitou 1 x 1 e terminou 2 x 2.</span></td>
                <td class="rank-pts"><?= (int)$pts['draw'] ?></td>
            </tr>
            <tr>
                <td><strong>Errou o vencedor, mas acertou os gols de um dos times</strong>.<br>
                    <span class="muted">Ex.: você palpitou 1 x 0 e terminou 1 x 2.</span></td>
                <td class="rank-pts"><?= (int)$pts['team_only'] ?></td>
            </tr>
            <tr>
                <td><strong>Errou tudo</strong> — não acertou o vencedor/empate nem os gols de um dos times.</td>
                <td class="rank-pts">0</td>
            </tr>
        </tbody>
    </table>
    <p class="muted" style="font-size:.85rem">A regra é aplicada de cima para baixo: cada palpite entra em exatamente uma situação oficial.</p>
</div>

<div class="card">
    <h2>Multiplicadores do ranking por fase</h2>
    <p>Depois de definir os pontos base do jogo, o ranking aplica o peso da fase:</p>
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

<div class="card">
    <h2>Pontos de bônus (no fim da Copa)</h2>
    <p>Além dos jogos, você palpita quem será o campeão e outros prêmios. Esses pontos entram no fim do torneio:</p>
    <table>
        <tbody>
            <tr><td>Acertar o <strong>Campeão</strong></td><td class="rank-pts"><?= (int)$pts['campeao'] ?></td></tr>
            <tr><td>Acertar o <strong>Vice-campeão</strong></td><td class="rank-pts"><?= (int)$pts['vice'] ?></td></tr>
            <tr><td>Acertar o <strong>3º lugar</strong></td><td class="rank-pts"><?= (int)$pts['terceiro'] ?></td></tr>
            <tr><td>Acertar o <strong>4º lugar</strong></td><td class="rank-pts"><?= (int)$pts['quarto'] ?></td></tr>
            <tr><td>Acertar o <strong>time do artilheiro</strong></td><td class="rank-pts"><?= (int)$pts['artilheiro'] ?></td></tr>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Regras gerais</h2>
    <ul>
        <li>Você pode <strong>criar ou alterar</strong> um palpite até <strong><?= LOCK_MINUTES ?> minutos antes</strong> do início de cada jogo. Depois disso o palpite fica fechado 🔒.</li>
        <li>Dá para definir um <strong>palpite padrão</strong> (ex.: 1 x 1) e aplicar a todos os jogos de uma vez — depois é só ajustar o que quiser.</li>
        <li>Os palpites de <strong>bônus</strong> (campeão etc.) fecham quando a Copa começa.</li>
        <li>Em caso de empate na pontuação, o ranking compara nesta ordem: <strong>placares exatos</strong>, <strong>vencedor + gols de um time</strong>, <strong>vencedor</strong>, <strong>empate sem placar exato</strong>, <strong>gols de um time</strong> e, por último, <strong>menos erros/ausências</strong>.</li>
        <li>Cada bolão é <strong>privado</strong>: só entra quem tem o link de convite.</li>
    </ul>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
