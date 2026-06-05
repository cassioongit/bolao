<?php
require __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Método inválido.'], 405);
}
csrf_require();

$u = current_user();
if (!$u) {
    json_response(['ok' => false, 'error' => 'Faça login novamente.'], 401);
}

$in = json_input();
$poolId  = (int)($in['pool_id'] ?? 0);
$matchId = (int)($in['match_id'] ?? 0);
$home    = (int)($in['home'] ?? -1);
$away    = (int)($in['away'] ?? -1);

if (!$poolId || !$matchId) {
    json_response(['ok' => false, 'error' => 'Dados incompletos.'], 400);
}
if (!pool_member($poolId, (int)$u['id'])) {
    json_response(['ok' => false, 'error' => 'Você não participa deste bolão.'], 403);
}

$res = save_prediction($poolId, (int)$u['id'], $matchId, $home, $away);
json_response($res, $res['ok'] ? 200 : 422);
