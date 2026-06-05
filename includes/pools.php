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
}

/** URL completa do convite. */
function invite_url(array $pool): string
{
    return APP_URL . '/convite.php?t=' . urlencode($pool['invite_token']);
}

/**
 * Mapa match_id => ['home'=>, 'away'=>] com os palpites do usuário no pool.
 * Aplica o palpite padrão do membro para jogos sem registro.
 */
function user_predictions_map(int $poolId, int $userId, array $member): array
{
    $stmt = db()->prepare(
        'SELECT match_id, home_pred, away_pred, pontos FROM predictions WHERE pool_id = ? AND user_id = ?'
    );
    $stmt->execute([$poolId, $userId]);
    $map = [];
    foreach ($stmt as $r) {
        $map[(int)$r['match_id']] = [
            'home'   => (int)$r['home_pred'],
            'away'   => (int)$r['away_pred'],
            'pontos' => $r['pontos'] === null ? null : (int)$r['pontos'],
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

    $sql = 'INSERT INTO predictions (pool_id, user_id, match_id, home_pred, away_pred, atualizado_em)
            VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP())
            ON DUPLICATE KEY UPDATE home_pred = VALUES(home_pred),
                                    away_pred = VALUES(away_pred),
                                    atualizado_em = UTC_TIMESTAMP()';
    $pdo->prepare($sql)->execute([$poolId, $userId, $matchId, $home, $away]);
    return ['ok' => true];
}

/**
 * Define o palpite padrão do membro e o aplica a todos os jogos AINDA ABERTOS
 * que ele ainda não palpitou (não sobrescreve palpites já feitos).
 */
function set_member_default(int $poolId, int $userId, int $home, int $away): void
{
    $pdo = db();
    $pdo->prepare(
        'UPDATE pool_members SET palpite_padrao_home = ?, palpite_padrao_away = ? WHERE pool_id = ? AND user_id = ?'
    )->execute([$home, $away, $poolId, $userId]);

    // Materializa o padrão nos jogos abertos sem palpite
    $sql = 'INSERT INTO predictions (pool_id, user_id, match_id, home_pred, away_pred, atualizado_em)
            SELECT ?, ?, m.id, ?, ?, UTC_TIMESTAMP()
            FROM matches m
            WHERE m.kickoff_utc > DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? MINUTE)
              AND NOT EXISTS (
                  SELECT 1 FROM predictions p
                  WHERE p.pool_id = ? AND p.user_id = ? AND p.match_id = m.id
              )';
    $pdo->prepare($sql)->execute([$poolId, $userId, $home, $away, LOCK_MINUTES, $poolId, $userId]);
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
    $sql = 'SELECT u.id, u.nome,
                   COALESCE(SUM(pr.pontos), 0) AS pontos,
                   SUM(CASE WHEN pr.pontos = ? THEN 1 ELSE 0 END) AS exatos,
                   (SELECT COALESCE(SUM(bp.pontos),0) FROM bonus_predictions bp
                      WHERE bp.pool_id = pm.pool_id AND bp.user_id = u.id) AS pontos_bonus
            FROM pool_members pm
            JOIN users u ON u.id = pm.user_id
            LEFT JOIN predictions pr ON pr.pool_id = pm.pool_id AND pr.user_id = u.id
            WHERE pm.pool_id = ?
            GROUP BY u.id, u.nome, pm.pool_id
            ORDER BY (COALESCE(SUM(pr.pontos),0) +
                      (SELECT COALESCE(SUM(bp.pontos),0) FROM bonus_predictions bp
                         WHERE bp.pool_id = pm.pool_id AND bp.user_id = u.id)) DESC,
                     exatos DESC, u.nome ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute([(int)$pool['pts_exato'], $poolId]);
    return $stmt->fetchAll();
}
