<?php
/**
 * Helpers de bolões (pools), membros, palpites e ranking.
 */

/** Busca um pool pelo id. */
function get_pool(int $poolId): ?array
{
    $stmt = db()->prepare('SELECT * FROM pools WHERE id = ?');
    $stmt->execute([$poolId]);
    return $stmt->fetch() ?: null;
}

/** Busca um pool pelo token de convite. */
function get_pool_by_token(string $token): ?array
{
    $stmt = db()->prepare('SELECT * FROM pools WHERE invite_token = ?');
    $stmt->execute([$token]);
    return $stmt->fetch() ?: null;
}

/** Retorna o registro de membro (ou null) de um usuário em um pool. */
function pool_member(int $poolId, int $userId): ?array
{
    $stmt = db()->prepare('SELECT * FROM pool_members WHERE pool_id = ? AND user_id = ?');
    $stmt->execute([$poolId, $userId]);
    return $stmt->fetch() ?: null;
}

/** Garante que o usuário é membro do pool; senão, bloqueia. */
function require_pool_member(int $poolId, int $userId): array
{
    $m = pool_member($poolId, $userId);
    if (!$m) {
        http_response_code(403);
        die('Você não participa deste bolão.');
    }
    ensure_member_predictions_projected($poolId, $userId);
    return $m;
}

/** Lista os bolões em que o usuário participa, com contagem de membros. */
function my_pools(int $userId): array
{
    $sql = 'SELECT p.*, pm.papel,
                   (SELECT COUNT(*) FROM pool_members x WHERE x.pool_id = p.id) AS total_membros
            FROM pools p
            JOIN pool_members pm ON pm.pool_id = p.id AND pm.user_id = ?
            ORDER BY p.criado_em DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/** Cria um novo bolão e adiciona o dono como membro. Retorna o id. */
function create_pool(string $nome, int $ownerId): int
{
    $pdo = db();
    $token = random_token(12);
    $stmt = $pdo->prepare(
        'INSERT INTO pools (nome, owner_user_id, invite_token, criado_em)
         VALUES (?, ?, ?, UTC_TIMESTAMP())'
    );
    $stmt->execute([$nome, $ownerId, $token]);
    $poolId = (int)$pdo->lastInsertId();
    add_pool_member($poolId, $ownerId, 'owner');
    return $poolId;
}

/** Adiciona um membro ao pool (ignora se já existe). */
function add_pool_member(int $poolId, int $userId, string $papel = 'membro'): void
{
    $stmt = db()->prepare(
        'INSERT IGNORE INTO pool_members (pool_id, user_id, papel, entrou_em)
         VALUES (?, ?, ?, UTC_TIMESTAMP())'
    );
    $stmt->execute([$poolId, $userId, $papel]);

    if ($stmt->rowCount() > 0) {
        sync_member_predictions_to_pool($poolId, $userId);
    }
}

/** URL completa do convite. */
function invite_url(array $pool): string
{
    return APP_URL . '/convite.php?t=' . urlencode($pool['invite_token']);
}

/**
 * Se ainda não existir linha canônica para um usuário/match,
 * reaproveita o palpite mais recente encontrado na projeção por bolão.
 */
function backfill_canonical_predictions(int $userId): void
{
    $sql = 'SELECT match_id, home_pred, away_pred, atualizado_em
            FROM predictions
            WHERE user_id = ?
            ORDER BY match_id ASC, atualizado_em DESC, id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute([$userId]);

    $seen = [];
    $ins = db()->prepare(
        'INSERT IGNORE INTO user_match_predictions (user_id, match_id, home_pred, away_pred, atualizado_em)
         VALUES (?, ?, ?, ?, ?)'
    );

    foreach ($stmt as $row) {
        $matchId = (int)$row['match_id'];
        if (isset($seen[$matchId])) {
            continue;
        }
        $seen[$matchId] = true;
        $ins->execute([
            $userId,
            $matchId,
            (int)$row['home_pred'],
            (int)$row['away_pred'],
            $row['atualizado_em'],
        ]);
    }
}

/**
 * Garante que os palpites canônicos do usuário existam na nova fonte de verdade.
 * O backfill roda no máximo uma vez por usuário em cada request.
 */
function ensure_canonical_predictions(int $userId): void
{
    static $hydratedUsers = [];
    if (isset($hydratedUsers[$userId])) {
        return;
    }
    backfill_canonical_predictions($userId);
    $hydratedUsers[$userId] = true;
}

/** Sincroniza todos os palpites canônicos do usuário para um bolão específico. */
function sync_canonical_predictions_to_pool(int $poolId, int $userId): void
{
    $sql = 'INSERT INTO predictions (pool_id, user_id, match_id, home_pred, away_pred, pontos, atualizado_em)
            SELECT ?, ?, ump.match_id, ump.home_pred, ump.away_pred, NULL, ump.atualizado_em
            FROM user_match_predictions ump
            WHERE ump.user_id = ?
            ON DUPLICATE KEY UPDATE home_pred = VALUES(home_pred),
                                    away_pred = VALUES(away_pred),
                                    pontos = NULL,
                                    atualizado_em = VALUES(atualizado_em)';
    db()->prepare($sql)->execute([$poolId, $userId, $userId]);
}

/**
 * Recalcula a projeção do bolão para jogos já encerrados, evitando rankings
 * incompletos quando o usuário entra em um bolão depois de já ter palpitado.
 */
function recalc_closed_pool_prediction_points(int $poolId): void
{
    $stmt = db()->prepare(
        'SELECT DISTINCT pr.match_id
         FROM predictions pr
         JOIN matches m ON m.id = pr.match_id
         WHERE pr.pool_id = ?
           AND m.status = \'encerrado\'
           AND m.home_score IS NOT NULL
           AND m.away_score IS NOT NULL'
    );
    $stmt->execute([$poolId]);
    foreach ($stmt as $row) {
        recalc_match_points((int)$row['match_id']);
    }
}

/** Hidrata a fonte canônica e projeta os palpites do membro no bolão. */
function sync_member_predictions_to_pool(int $poolId, int $userId): void
{
    ensure_canonical_predictions($userId);
    sync_canonical_predictions_to_pool($poolId, $userId);
    recalc_closed_pool_prediction_points($poolId);
}

/**
 * Auto-recupera memberships antigos criados antes do patch, garantindo que o
 * bolão tenha a projeção compatível dos palpites canônicos daquele membro.
 * Roda no máximo uma vez por par pool/usuário em cada request.
 */
function ensure_member_predictions_projected(int $poolId, int $userId): void
{
    static $projectedMembers = [];
    $key = $poolId . ':' . $userId;
    if (isset($projectedMembers[$key])) {
        return;
    }

    sync_member_predictions_to_pool($poolId, $userId);
    $projectedMembers[$key] = true;
}

/** Sincroniza um palpite canônico para todos os bolões do usuário. */
function sync_canonical_prediction_to_pools(int $userId, int $matchId, int $home, int $away): void
{
    $sql = 'INSERT INTO predictions (pool_id, user_id, match_id, home_pred, away_pred, pontos, atualizado_em)
            SELECT pm.pool_id, pm.user_id, ?, ?, ?, NULL, UTC_TIMESTAMP()
            FROM pool_members pm
            WHERE pm.user_id = ?
            ON DUPLICATE KEY UPDATE home_pred = VALUES(home_pred),
                                    away_pred = VALUES(away_pred),
                                    pontos = NULL,
                                    atualizado_em = UTC_TIMESTAMP()';
    db()->prepare($sql)->execute([$matchId, $home, $away, $userId]);
}

/** Sincroniza todos os palpites canônicos abertos do usuário para seus bolões. */
function sync_open_canonical_predictions_to_pools(int $userId): void
{
    $sql = 'INSERT INTO predictions (pool_id, user_id, match_id, home_pred, away_pred, pontos, atualizado_em)
            SELECT pm.pool_id, pm.user_id, ump.match_id, ump.home_pred, ump.away_pred, NULL, ump.atualizado_em
            FROM pool_members pm
            JOIN user_match_predictions ump ON ump.user_id = pm.user_id
            JOIN matches m ON m.id = ump.match_id
            WHERE pm.user_id = ?
              AND m.kickoff_utc > DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? MINUTE)
            ON DUPLICATE KEY UPDATE home_pred = VALUES(home_pred),
                                    away_pred = VALUES(away_pred),
                                    pontos = NULL,
                                    atualizado_em = VALUES(atualizado_em)';
    db()->prepare($sql)->execute([$userId, LOCK_MINUTES]);
}

/**
 * Mapa match_id => ['home'=>, 'away'=>] com os palpites do usuário no pool.
 * Aplica o palpite padrão do membro para jogos sem registro.
 */
function user_predictions_map(int $poolId, int $userId, array $member): array
{
    ensure_canonical_predictions($userId);

    $stmt = db()->prepare(
        'SELECT ump.match_id, ump.home_pred, ump.away_pred, pr.pontos,
                m.fase, m.status, m.home_score, m.away_score
         FROM user_match_predictions ump
         JOIN matches m ON m.id = ump.match_id
         LEFT JOIN predictions pr
           ON pr.pool_id = ? AND pr.user_id = ump.user_id AND pr.match_id = ump.match_id
         WHERE ump.user_id = ?'
    );
    $stmt->execute([$poolId, $userId]);
    $map = [];
    foreach ($stmt as $r) {
        $map[(int)$r['match_id']] = [
            'home'   => (int)$r['home_pred'],
            'away'   => (int)$r['away_pred'],
            'pontos' => $r['pontos'] === null ? null : (int)$r['pontos'],
            'score_breakdown' => classic_prediction_breakdown($r, true),
        ];
    }
    return $map; // o padrão é aplicado na exibição quando o jogo não está no mapa
}

/** Salva (cria ou atualiza) o palpite de um jogo, respeitando o travamento. */
function save_prediction(int $poolId, int $userId, int $matchId, int $home, int $away): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT kickoff_utc FROM matches WHERE id = ?');
    $stmt->execute([$matchId]);
    $match = $stmt->fetch();
    if (!$match) {
        return ['ok' => false, 'error' => 'Jogo não encontrado.'];
    }
    if (match_is_locked($match['kickoff_utc'])) {
        return ['ok' => false, 'error' => 'Palpite travado (menos de ' . LOCK_MINUTES . ' min para o jogo).'];
    }
    if ($home < 0 || $away < 0 || $home > 99 || $away > 99) {
        return ['ok' => false, 'error' => 'Placar inválido.'];
    }

    $sql = 'INSERT INTO user_match_predictions (user_id, match_id, home_pred, away_pred, atualizado_em)
            VALUES (?, ?, ?, ?, UTC_TIMESTAMP())
            ON DUPLICATE KEY UPDATE home_pred = VALUES(home_pred),
                                    away_pred = VALUES(away_pred),
                                    atualizado_em = UTC_TIMESTAMP()';
    $pdo->prepare($sql)->execute([$userId, $matchId, $home, $away]);

    // Mantém a tabela pool-centric como projeção compatível temporária.
    sync_canonical_prediction_to_pools($userId, $matchId, $home, $away);
    return ['ok' => true];
}

/**
 * Define o palpite padrão do membro e o aplica a todos os jogos AINDA ABERTOS
 * que ele ainda não palpitou (não sobrescreve palpites já feitos).
 */
function set_member_default(int $poolId, int $userId, int $home, int $away): void
{
    $pdo = db();
    ensure_canonical_predictions($userId);
    $pdo->prepare(
        'UPDATE pool_members SET palpite_padrao_home = ?, palpite_padrao_away = ? WHERE pool_id = ? AND user_id = ?'
    )->execute([$home, $away, $poolId, $userId]);

    // Materializa o padrão na fonte canônica apenas para jogos ainda sem palpite.
    $sql = 'INSERT INTO user_match_predictions (user_id, match_id, home_pred, away_pred, atualizado_em)
            SELECT ?, m.id, ?, ?, UTC_TIMESTAMP()
            FROM matches m
            WHERE m.kickoff_utc > DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? MINUTE)
              AND NOT EXISTS (
                  SELECT 1 FROM user_match_predictions ump
                  WHERE ump.user_id = ? AND ump.match_id = m.id
              )';
    $pdo->prepare($sql)->execute([$userId, $home, $away, LOCK_MINUTES, $userId]);

    // Sincroniza a projeção por bolão para manter as telas atuais funcionando.
    sync_open_canonical_predictions_to_pools($userId);
}

/** Rótulo de um lado do confronto: seleção real (bandeira + nome) ou placeholder. */
function side_label(array $match, string $side): string
{
    $nome = $match[$side . '_nome'] ?? null;
    $band = $match[$side . '_bandeira'] ?? null;
    if ($nome) {
        return trim(($band ? $band . ' ' : '') . $nome);
    }
    $ph = $match[$side . '_placeholder'] ?? null;
    return $ph ? '🏳️ ' . $ph : 'A definir';
}

/** Renderiza a barra de abas de um bolão. $active: palpites|ranking|bonus|membros */
function render_pool_tabs(array $pool, string $active): void
{
    $id = (int)$pool['id'];
    $base = APP_URL;
    $tabs = [
        'palpites' => ['Palpites', "$base/palpites.php?id=$id"],
        'ranking'  => ['Ranking',  "$base/ranking.php?id=$id"],
        'bonus'    => ['Bônus',     "$base/bonus.php?id=$id"],
        'membros'  => ['Participantes', "$base/bolao.php?id=$id"],
    ];
    echo '<nav class="tabs">';
    foreach ($tabs as $key => [$label, $url]) {
        $cls = $key === $active ? ' class="active"' : '';
        echo '<a' . $cls . ' href="' . e($url) . '">' . e($label) . '</a>';
    }
    echo '<a href="' . e($base) . '/regras.php?id=' . $id . '">Regras</a>';
    echo '</nav>';
}

/** Ranking de um pool: soma de pontos por usuário, desempate por placares exatos. */
function pool_ranking(int $poolId, array $pool): array
{
    $memberIds = db()->prepare('SELECT user_id FROM pool_members WHERE pool_id = ?');
    $memberIds->execute([$poolId]);
    foreach ($memberIds->fetchAll(PDO::FETCH_COLUMN) as $memberId) {
        ensure_member_predictions_projected($poolId, (int)$memberId);
    }

    $multiplierCase = classic_multiplier_case_sql('m.fase');
    $scenarioCase = classic_scenario_case_sql();

    $sql = "SELECT u.id, u.nome,
                   COALESCE(SUM(COALESCE(pr.pontos, 0) * {$multiplierCase}), 0) AS pontos,
                   SUM(CASE WHEN {$scenarioCase} = '" . CLASSIC_SCENARIO_EXACT . "' THEN 1 ELSE 0 END) AS exatos,
                   SUM(CASE WHEN {$scenarioCase} = '" . CLASSIC_SCENARIO_WINNER_AND_ONE_TEAM_SCORE . "' THEN 1 ELSE 0 END) AS winner_plus_one,
                   SUM(CASE WHEN {$scenarioCase} = '" . CLASSIC_SCENARIO_WINNER_ONLY . "' THEN 1 ELSE 0 END) AS winner_only,
                   SUM(CASE WHEN {$scenarioCase} = '" . CLASSIC_SCENARIO_DRAW_NON_EXACT . "' THEN 1 ELSE 0 END) AS draw_non_exact,
                   SUM(CASE WHEN {$scenarioCase} = '" . CLASSIC_SCENARIO_ONE_TEAM_SCORE_ONLY . "' THEN 1 ELSE 0 END) AS one_team_only,
                   SUM(CASE WHEN {$scenarioCase} = '" . CLASSIC_SCENARIO_MISS . "' THEN 1 ELSE 0 END) AS misses,
                   (SELECT COALESCE(SUM(bp.pontos),0) FROM bonus_predictions bp
                      WHERE bp.pool_id = pm.pool_id AND bp.user_id = u.id) AS pontos_bonus
            FROM pool_members pm
            JOIN users u ON u.id = pm.user_id
            LEFT JOIN matches m ON m.status = 'encerrado'
            LEFT JOIN predictions pr
                   ON pr.pool_id = pm.pool_id
                  AND pr.user_id = u.id
                  AND pr.match_id = m.id
            WHERE pm.pool_id = ?
            GROUP BY u.id, u.nome, pm.pool_id
            ORDER BY (COALESCE(SUM(COALESCE(pr.pontos, 0) * {$multiplierCase}),0) +
                      (SELECT COALESCE(SUM(bp.pontos),0) FROM bonus_predictions bp
                         WHERE bp.pool_id = pm.pool_id AND bp.user_id = u.id)) DESC,
                     exatos DESC,
                     winner_plus_one DESC,
                     winner_only DESC,
                     draw_non_exact DESC,
                     one_team_only DESC,
                     misses ASC,
                     u.nome ASC";
    $stmt = db()->prepare($sql);
    $stmt->execute([$poolId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['tiebreak'] = classic_ranking_tiebreak_breakdown($row);
    }
    unset($row);
    return $rows;
}
