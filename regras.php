<?php
require __DIR__ . '/includes/bootstrap.php';

// Se vier ?id de um bolão do qual o usuário participa, usamos a pontuação dele.
$poolId = (int)($_GET['id'] ?? 0);
$pool = null;
if ($poolId && is_logged_in() && pool_member($poolId, (int)current_user()['id'])) {
    $pool = get_pool($poolId);
}

// Valores de pontuação (do bolão ou padrão)
$pts = [
    'exato'      => $pool['pts_exato']      ?? 10,
    'saldo'      => $pool['pts_saldo']      ?? 7,
    'gols_um'    => $pool['pts_gols_um']    ?? 5,
    'vencedor'   => $pool['pts_vencedor']   ?? 3,
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
    <p>Para cada partida, você dá um palpite de placar. Quando o jogo acaba, comparamos o seu palpite com o resultado real e você ganha pontos assim:</p>
    <table>
        <thead><tr><th>Situação do seu palpite</th><th class="rank-pts">Pontos</th></tr></thead>
        <tbody>
            <tr>
                <td><strong>Placar exato</strong> — você acertou os gols dos dois times.<br>
                    <span class="muted">Ex.: você palpitou 2 x 1 e o jogo terminou 2 x 1.</span></td>
                <td class="rank-pts"><?= (int)$pts['exato'] ?></td>
            </tr>
            <tr>
                <td><strong>Acertou o vencedor (ou o empate) e o saldo de gols</strong>.<br>
                    <span class="muted">Ex.: você palpitou 2 x 1 e terminou 3 x 2 (mesmo vencedor, diferença de 1 gol). No empate: palpitou 1 x 1 e foi 2 x 2.</span></td>
                <td class="rank-pts"><?= (int)$pts['saldo'] ?></td>
            </tr>
            <tr>
                <td><strong>Acertou o vencedor (ou o empate) e os gols de um dos times</strong>.<br>
                    <span class="muted">Ex.: você palpitou 2 x 1 e terminou 2 x 0 (acertou o vencedor e os gols do time da casa).</span></td>
                <td class="rank-pts"><?= (int)$pts['gols_um'] ?></td>
            </tr>
            <tr>
                <td><strong>Acertou só quem venceu</strong> (ou que foi empate), mas errou os placares.<br>
                    <span class="muted">Ex.: você palpitou 2 x 1 e terminou 4 x 0 — acertou que o time da casa ganhou.</span></td>
                <td class="rank-pts"><?= (int)$pts['vencedor'] ?></td>
            </tr>
            <tr>
                <td><strong>Errou o resultado</strong> — quem você achou que ganharia não ganhou.</td>
                <td class="rank-pts">0</td>
            </tr>
        </tbody>
    </table>
    <p class="muted" style="font-size:.85rem">A regra é aplicada de cima para baixo: vale sempre a melhor situação que o seu palpite atingir.</p>
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
        <li>Em caso de empate na pontuação, fica à frente quem tiver <strong>mais placares exatos</strong>.</li>
        <li>Cada bolão é <strong>privado</strong>: só entra quem tem o link de convite.</li>
    </ul>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
