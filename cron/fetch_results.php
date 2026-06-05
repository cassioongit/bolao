<?php
/**
 * STUB — Busca automática de resultados (para o futuro).
 *
 * Hoje os resultados são lançados manualmente em /admin/resultados.php.
 * Quando quiser automatizar, configure um "cron job" na hospedagem apontando
 * para este arquivo (ex.: a cada 10 min durante a Copa) e implemente a busca
 * em uma API de futebol (ex.: football-data.org, API-Football).
 *
 * Exemplo de agendamento no cPanel:
 *   /usr/bin/php /home/USUARIO/public_html/cron/fetch_results.php
 *
 * O fluxo deve:
 *   1. Buscar os jogos do dia na API.
 *   2. Casar com a tabela `matches` (por data/seleções ou por um id externo).
 *   3. Atualizar home_score/away_score e status='encerrado'.
 *   4. Chamar recalc_match_points($matchId) para cada jogo encerrado.
 */

require __DIR__ . '/../includes/bootstrap.php';

// Segurança simples: só roda via linha de comando (cron), não pela web.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('Este script só roda via cron (linha de comando).');
}

fwrite(STDOUT, "[" . gmdate('c') . "] fetch_results: nada a fazer (modo manual ativo).\n");

// TODO: integrar API de futebol aqui.
// Exemplo de finalização de um jogo:
//   db()->prepare("UPDATE matches SET home_score=?, away_score=?, status='encerrado' WHERE id=?")
//       ->execute([$h, $a, $matchId]);
//   recalc_match_points($matchId);
