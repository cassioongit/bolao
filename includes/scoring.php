<?php
/**
 * Regras oficiais de pontuação Classic para palpites de jogos.
 *
 * O placar oficial sempre considera o resultado ao fim do tempo normal +
 * prorrogação. Disputa por pênaltis não altera a classificação do palpite.
 */

const CLASSIC_SCENARIO_EXACT = 'exact_score';
const CLASSIC_SCENARIO_WINNER_AND_ONE_TEAM_SCORE = 'winner_plus_one_team_score';
const CLASSIC_SCENARIO_WINNER_ONLY = 'winner_only';
const CLASSIC_SCENARIO_DRAW_NON_EXACT = 'draw_without_exact_score';
const CLASSIC_SCENARIO_ONE_TEAM_SCORE_ONLY = 'one_team_score_only';
const CLASSIC_SCENARIO_MISS = 'total_miss';

function classic_scenario_labels(): array
{
    return [
        CLASSIC_SCENARIO_EXACT => 'Placar exato',
        CLASSIC_SCENARIO_WINNER_AND_ONE_TEAM_SCORE => 'Vencedor + gols de um time',
        CLASSIC_SCENARIO_WINNER_ONLY => 'Vencedor',
        CLASSIC_SCENARIO_DRAW_NON_EXACT => 'Empate sem placar exato',
        CLASSIC_SCENARIO_ONE_TEAM_SCORE_ONLY => 'Gols de um time',
        CLASSIC_SCENARIO_MISS => 'Erro total/sem palpite',
    ];
}

function classic_scenario_precedence(): array
{
    return [
        CLASSIC_SCENARIO_EXACT,
        CLASSIC_SCENARIO_WINNER_AND_ONE_TEAM_SCORE,
        CLASSIC_SCENARIO_WINNER_ONLY,
        CLASSIC_SCENARIO_DRAW_NON_EXACT,
        CLASSIC_SCENARIO_ONE_TEAM_SCORE_ONLY,
        CLASSIC_SCENARIO_MISS,
    ];
}

/** Tabela fixa de pontuação Classic. */
function classic_score_points(): array
{
    return [
        CLASSIC_SCENARIO_EXACT => 10,
        CLASSIC_SCENARIO_WINNER_AND_ONE_TEAM_SCORE => 7,
        CLASSIC_SCENARIO_WINNER_ONLY => 5,
        CLASSIC_SCENARIO_DRAW_NON_EXACT => 5,
        CLASSIC_SCENARIO_ONE_TEAM_SCORE_ONLY => 2,
        CLASSIC_SCENARIO_MISS => 0,
    ];
}

function classic_exact_points(): int
{
    return classic_score_points()[CLASSIC_SCENARIO_EXACT];
}

/** Multiplicadores oficiais por fase para o ranking Classic. */
function classic_stage_multipliers(): array
{
    return [
        'grupos' => 1,
        'r16' => 2,
        'qf' => 3,
        'sf' => 4,
        'terceiro' => 4,
        'final' => 5,
    ];
}

function classic_stage_multiplier(string $stage): int
{
    return classic_stage_multipliers()[$stage] ?? 1;
}

/** Expressão SQL para aplicar multiplicador por fase ao total de pontos. */
function classic_multiplier_case_sql(string $stageColumn = 'm.fase'): string
{
    return "CASE
        WHEN {$stageColumn} = 'grupos' THEN 1
        WHEN {$stageColumn} = 'r16' THEN 2
        WHEN {$stageColumn} = 'qf' THEN 3
        WHEN {$stageColumn} = 'sf' THEN 4
        WHEN {$stageColumn} = 'terceiro' THEN 4
        WHEN {$stageColumn} = 'final' THEN 5
        ELSE 1
    END";
}

function classic_sql_result_sign(string $homeExpr, string $awayExpr): string
{
    return "(CASE
        WHEN {$homeExpr} > {$awayExpr} THEN 1
        WHEN {$homeExpr} < {$awayExpr} THEN -1
        ELSE 0
    END)";
}

/** Expressão SQL que classifica o cenário Classic de um palpite já lançado. */
function classic_scenario_case_sql(
    string $predictionIdColumn = 'pr.id',
    string $predHomeColumn = 'pr.home_pred',
    string $predAwayColumn = 'pr.away_pred',
    string $resultHomeColumn = 'm.home_score',
    string $resultAwayColumn = 'm.away_score',
    string $matchStatusColumn = 'm.status'
): string {
    $predictedSign = classic_sql_result_sign($predHomeColumn, $predAwayColumn);
    $resultSign = classic_sql_result_sign($resultHomeColumn, $resultAwayColumn);

    return "CASE
        WHEN {$matchStatusColumn} IS NULL
             OR {$matchStatusColumn} <> 'encerrado'
             OR {$resultHomeColumn} IS NULL
             OR {$resultAwayColumn} IS NULL THEN NULL
        WHEN {$predictionIdColumn} IS NULL THEN '" . CLASSIC_SCENARIO_MISS . "'
        WHEN {$predHomeColumn} = {$resultHomeColumn}
             AND {$predAwayColumn} = {$resultAwayColumn} THEN '" . CLASSIC_SCENARIO_EXACT . "'
        WHEN {$predictedSign} = {$resultSign}
             AND {$resultSign} = 0 THEN '" . CLASSIC_SCENARIO_DRAW_NON_EXACT . "'
        WHEN {$predictedSign} = {$resultSign}
             AND ({$predHomeColumn} = {$resultHomeColumn}
                  OR {$predAwayColumn} = {$resultAwayColumn}) THEN '" . CLASSIC_SCENARIO_WINNER_AND_ONE_TEAM_SCORE . "'
        WHEN {$predictedSign} = {$resultSign} THEN '" . CLASSIC_SCENARIO_WINNER_ONLY . "'
        WHEN {$predHomeColumn} = {$resultHomeColumn}
             OR {$predAwayColumn} = {$resultAwayColumn} THEN '" . CLASSIC_SCENARIO_ONE_TEAM_SCORE_ONLY . "'
        ELSE '" . CLASSIC_SCENARIO_MISS . "'
    END";
}

/** Sinal do placar: 1 casa vence, -1 visitante vence, 0 empate. */
function result_sign(int $home, int $away): int
{
    return $home <=> $away;
}

/** Classifica um palpite em exatamente um cenário oficial Classic. */
function classify_prediction(int $ph, int $pa, int $rh, int $ra): string
{
    if ($ph === $rh && $pa === $ra) {
        return CLASSIC_SCENARIO_EXACT;
    }

    $predictedSign = result_sign($ph, $pa);
    $resultSign = result_sign($rh, $ra);
    $sameOutcome = $predictedSign === $resultSign;

    if ($sameOutcome && $resultSign === 0) {
        return CLASSIC_SCENARIO_DRAW_NON_EXACT;
    }

    if ($sameOutcome && ($ph === $rh || $pa === $ra)) {
        return CLASSIC_SCENARIO_WINNER_AND_ONE_TEAM_SCORE;
    }

    if ($sameOutcome) {
        return CLASSIC_SCENARIO_WINNER_ONLY;
    }

    if ($ph === $rh || $pa === $ra) {
        return CLASSIC_SCENARIO_ONE_TEAM_SCORE_ONLY;
    }

    return CLASSIC_SCENARIO_MISS;
}

/** Calcula os pontos fixos do contrato Classic. */
function score_prediction(int $ph, int $pa, int $rh, int $ra): int
{
    $scenario = classify_prediction($ph, $pa, $rh, $ra);
    return classic_score_points()[$scenario] ?? 0;
}

function classic_stage_bucket(string $stage): string
{
    return in_array($stage, array_keys(classic_stage_multipliers()), true) ? $stage : 'grupos';
}

/**
 * Gera um breakdown estruturado e autoritativo de pontuação para um palpite.
 *
 * Requer no mínimo:
 * - status, home_score, away_score, fase
 * - home_pred, away_pred quando houver palpite
 */
function classic_prediction_breakdown(array $row, bool $predictionExists = true): ?array
{
    if (($row['status'] ?? null) !== 'encerrado'
        || !isset($row['home_score'], $row['away_score'], $row['fase'])) {
        return null;
    }

    $stageBucket = classic_stage_bucket((string)$row['fase']);
    $multiplier = classic_stage_multiplier($stageBucket);

    if (!$predictionExists) {
        $scenario = CLASSIC_SCENARIO_MISS;
        $basePoints = classic_score_points()[$scenario];
    } else {
        $scenario = classify_prediction(
            (int)$row['home_pred'],
            (int)$row['away_pred'],
            (int)$row['home_score'],
            (int)$row['away_score']
        );
        $basePoints = classic_score_points()[$scenario];
    }

    return [
        'scenario' => $scenario,
        'scenario_label' => classic_scenario_labels()[$scenario] ?? $scenario,
        'base_points' => $basePoints,
        'stage_bucket' => $stageBucket,
        'stage_label' => stage_label($stageBucket),
        'multiplier' => $multiplier,
        'weighted_points' => $basePoints * $multiplier,
        'result' => [
            'home_score' => (int)$row['home_score'],
            'away_score' => (int)$row['away_score'],
        ],
        'prediction' => $predictionExists ? [
            'home_pred' => (int)$row['home_pred'],
            'away_pred' => (int)$row['away_pred'],
        ] : null,
    ];
}

function classic_ranking_tiebreak_breakdown(array $row): array
{
    $counts = [
        CLASSIC_SCENARIO_EXACT => (int)($row['exatos'] ?? 0),
        CLASSIC_SCENARIO_WINNER_AND_ONE_TEAM_SCORE => (int)($row['winner_plus_one'] ?? 0),
        CLASSIC_SCENARIO_WINNER_ONLY => (int)($row['winner_only'] ?? 0),
        CLASSIC_SCENARIO_DRAW_NON_EXACT => (int)($row['draw_non_exact'] ?? 0),
        CLASSIC_SCENARIO_ONE_TEAM_SCORE_ONLY => (int)($row['one_team_only'] ?? 0),
        CLASSIC_SCENARIO_MISS => (int)($row['misses'] ?? 0),
    ];

    $criteria = [];
    foreach (classic_scenario_precedence() as $scenario) {
        $criteria[] = [
            'scenario' => $scenario,
            'label' => classic_scenario_labels()[$scenario] ?? $scenario,
            'count' => $counts[$scenario],
        ];
    }

    return [
        'counts' => $counts,
        'precedence' => classic_scenario_precedence(),
        'criteria' => $criteria,
    ];
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

    $sql = 'SELECT pr.id, pr.home_pred, pr.away_pred
            FROM predictions pr
            WHERE pr.match_id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$matchId]);

    $upd = $pdo->prepare('UPDATE predictions SET pontos = ? WHERE id = ?');
    foreach ($stmt as $row) {
        $pts = score_prediction((int)$row['home_pred'], (int)$row['away_pred'], $rh, $ra);
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
