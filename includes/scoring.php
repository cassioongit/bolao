<?php
/**
 * Regras de pontuação dos palpites.
 *
 * Cada bolão (tabela pools) guarda os valores de pontos, então passamos a
 * própria linha do pool como $cfg para calcular.
 */

/** Sinal do placar: 1 casa vence, -1 visitante vence, 0 empate. */
function result_sign(int $home, int $away): int
{
    return $home <=> $away;
}

/**
 * Calcula os pontos de um palpite contra o resultado real.
 *
 * @param int   $ph,$pa  palpite (home, away)
 * @param int   $rh,$ra  resultado real (home, away)
 * @param array $cfg     linha do pool com pts_exato, pts_saldo, pts_gols_um, pts_vencedor
 */
function score_prediction(int $ph, int $pa, int $rh, int $ra, array $cfg): int
{
    // 1) Placar exato
    if ($ph === $rh && $pa === $ra) {
        return (int)$cfg['pts_exato'];
    }

    $mesmoResultado = result_sign($ph, $pa) === result_sign($rh, $ra);

    // 2) Acertou o vencedor/empate E o saldo de gols
    if ($mesmoResultado && ($ph - $pa) === ($rh - $ra)) {
        return (int)$cfg['pts_saldo'];
    }

    // 3) Acertou o vencedor/empate E os gols de um dos times
    if ($mesmoResultado && ($ph === $rh || $pa === $ra)) {
        return (int)$cfg['pts_gols_um'];
    }

    // 4) Acertou só o vencedor/empate
    if ($mesmoResultado) {
        return (int)$cfg['pts_vencedor'];
    }

    // 5) Errou
    return 0;
}

/**
 * Recalcula os pontos de TODAS as predictions de um jogo, em todos os bolões.
 * Chamado quando o admin registra/edita o placar real do jogo.
 */
function recalc_match_points(int $matchId): void
{
    $pdo = db();
    $m = $pdo->prepare('SELECT * FROM matches WHERE id = ?');
    $m->execute([$matchId]);
    $match = $m->fetch();
    if (!$match) {
        return;
    }

    // Sem resultado definido → zera os pontos calculados (volta a NULL).
    if ($match['status'] !== 'encerrado' || $match['home_score'] === null || $match['away_score'] === null) {
        $pdo->prepare('UPDATE predictions SET pontos = NULL WHERE match_id = ?')->execute([$matchId]);
        return;
    }

    $rh = (int)$match['home_score'];
    $ra = (int)$match['away_score'];

    // Junta cada palpite com a config de pontuação do seu bolão.
    $sql = 'SELECT pr.id, pr.home_pred, pr.away_pred,
                   p.pts_exato, p.pts_saldo, p.pts_gols_um, p.pts_vencedor
            FROM predictions pr
            JOIN pools p ON p.id = pr.pool_id
            WHERE pr.match_id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$matchId]);

    $upd = $pdo->prepare('UPDATE predictions SET pontos = ? WHERE id = ?');
    foreach ($stmt as $row) {
        $pts = score_prediction(
            (int)$row['home_pred'],
            (int)$row['away_pred'],
            $rh,
            $ra,
            $row
        );
        $upd->execute([$pts, $row['id']]);
    }
}

/**
 * Recalcula os pontos de bônus (campeão, vice, etc.) em todos os bolões,
 * com base na tabela tournament_results (gabarito).
 */
function recalc_bonus_points(): void
{
    $pdo = db();
    $gab = $pdo->query('SELECT * FROM tournament_results WHERE id = 1')->fetch();
    if (!$gab) {
        return;
    }

    $map = [
        'campeao'    => ['team' => $gab['campeao_team_id'],    'col' => 'pts_campeao'],
        'vice'       => ['team' => $gab['vice_team_id'],       'col' => 'pts_vice'],
        'terceiro'   => ['team' => $gab['terceiro_team_id'],   'col' => 'pts_terceiro'],
        'quarto'     => ['team' => $gab['quarto_team_id'],     'col' => 'pts_quarto'],
        'artilheiro' => ['team' => $gab['artilheiro_team_id'], 'col' => 'pts_artilheiro'],
    ];

    $stmt = $pdo->prepare(
        'SELECT bp.id, bp.chave, bp.team_id, p.pts_campeao, p.pts_vice,
                p.pts_terceiro, p.pts_quarto, p.pts_artilheiro
         FROM bonus_predictions bp JOIN pools p ON p.id = bp.pool_id'
    );
    $stmt->execute();
    $upd = $pdo->prepare('UPDATE bonus_predictions SET pontos = ? WHERE id = ?');

    foreach ($stmt as $row) {
        $def = $map[$row['chave']] ?? null;
        $pts = 0;
        if ($def && $def['team'] !== null && (int)$def['team'] === (int)$row['team_id']) {
            $pts = (int)$row[$def['col']];
        } elseif ($def && $def['team'] === null) {
            $pts = 0; // gabarito ainda não definido para essa chave
        }
        $upd->execute([$pts, $row['id']]);
    }
}
