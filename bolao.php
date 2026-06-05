<?php
require __DIR__ . '/includes/bootstrap.php';
$u = require_login();

$poolId = (int)($_GET['id'] ?? 0);
$pool = get_pool($poolId);
if (!$pool) { http_response_code(404); die('Bolão não encontrado.'); }
$member = require_pool_member($poolId, (int)$u['id']);
$isOwner = $member['papel'] === 'owner';

// Dono pode ajustar nome e pontuação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwner) {
    csrf_require();
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'config') {
        $nome = trim($_POST['nome'] ?? $pool['nome']);
        $campos = ['pts_exato','pts_saldo','pts_gols_um','pts_vencedor','pts_campeao','pts_vice','pts_terceiro','pts_quarto','pts_artilheiro'];
        $vals = [];
        foreach ($campos as $c) { $vals[$c] = max(0, (int)($_POST[$c] ?? $pool[$c])); }
        $sql = 'UPDATE pools SET nome=?, pts_exato=?, pts_saldo=?, pts_gols_um=?, pts_vencedor=?,
                   pts_campeao=?, pts_vice=?, pts_terceiro=?, pts_quarto=?, pts_artilheiro=? WHERE id=?';
        db()->prepare($sql)->execute([
            $nome, $vals['pts_exato'], $vals['pts_saldo'], $vals['pts_gols_um'], $vals['pts_vencedor'],
            $vals['pts_campeao'], $vals['pts_vice'], $vals['pts_terceiro'], $vals['pts_quarto'], $vals['pts_artilheiro'],
            $poolId,
        ]);
        // Recalcula pois a pontuação pode ter mudado
        foreach (db()->query('SELECT id FROM matches WHERE status = \'encerrado\'') as $row) {
            recalc_match_points((int)$row['id']);
        }
        recalc_bonus_points();
        flash('Configurações salvas.', 'success');
        redirect('bolao.php?id=' . $poolId);
    }
    if ($acao === 'sair_membro') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid && $uid !== (int)$pool['owner_user_id']) {
            db()->prepare('DELETE FROM pool_members WHERE pool_id=? AND user_id=?')->execute([$poolId, $uid]);
            flash('Participante removido.', 'info');
        }
        redirect('bolao.php?id=' . $poolId);
    }
}

// Lista de membros
$stmt = db()->prepare(
    'SELECT u.id, u.nome, pm.papel FROM pool_members pm JOIN users u ON u.id = pm.user_id
     WHERE pm.pool_id = ? ORDER BY pm.papel = \'owner\' DESC, u.nome'
);
$stmt->execute([$poolId]);
$membros = $stmt->fetchAll();

$page_title = $pool['nome'];
require __DIR__ . '/includes/header.php';
?>
<h1><?= e($pool['nome']) ?></h1>
<?php render_pool_tabs($pool, 'membros'); ?>

<div class="card">
    <h2>Convidar pessoas</h2>
    <p class="muted">Qualquer pessoa com este link pode entrar no bolão. Compartilhe no WhatsApp 😉</p>
    <div class="invite-box">
        <input type="text" id="invite-link" readonly value="<?= e(invite_url($pool)) ?>">
        <button class="btn btn-sm" type="button" data-copy="#invite-link">Copiar</button>
    </div>
</div>

<div class="card">
    <h2>Participantes (<?= count($membros) ?>)</h2>
    <table>
        <tbody>
        <?php foreach ($membros as $m): ?>
            <tr>
                <td><?= e($m['nome']) ?> <?= $m['papel'] === 'owner' ? '<span class="muted">· dono</span>' : '' ?></td>
                <td style="text-align:right">
                    <?php if ($isOwner && $m['papel'] !== 'owner'): ?>
                        <form method="post" style="display:inline" onsubmit="return confirm('Remover este participante?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="acao" value="sair_membro">
                            <input type="hidden" name="user_id" value="<?= (int)$m['id'] ?>">
                            <button class="btn btn-sm btn-secondary" type="submit">Remover</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($isOwner): ?>
<div class="card">
    <h2>Configurações do bolão</h2>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="config">
        <label>Nome do bolão</label>
        <input type="text" name="nome" value="<?= e($pool['nome']) ?>" maxlength="80">

        <h3 style="margin-top:18px">Pontos por placar</h3>
        <div class="default-bar">
            <div><label>Placar exato</label><input type="number" name="pts_exato" value="<?= (int)$pool['pts_exato'] ?>" min="0"></div>
            <div><label>Vencedor + saldo</label><input type="number" name="pts_saldo" value="<?= (int)$pool['pts_saldo'] ?>" min="0"></div>
            <div><label>Vencedor + gols de um time</label><input type="number" name="pts_gols_um" value="<?= (int)$pool['pts_gols_um'] ?>" min="0"></div>
            <div><label>Só o vencedor/empate</label><input type="number" name="pts_vencedor" value="<?= (int)$pool['pts_vencedor'] ?>" min="0"></div>
        </div>

        <h3 style="margin-top:18px">Pontos de bônus</h3>
        <div class="default-bar">
            <div><label>Campeão</label><input type="number" name="pts_campeao" value="<?= (int)$pool['pts_campeao'] ?>" min="0"></div>
            <div><label>Vice</label><input type="number" name="pts_vice" value="<?= (int)$pool['pts_vice'] ?>" min="0"></div>
            <div><label>3º lugar</label><input type="number" name="pts_terceiro" value="<?= (int)$pool['pts_terceiro'] ?>" min="0"></div>
            <div><label>4º lugar</label><input type="number" name="pts_quarto" value="<?= (int)$pool['pts_quarto'] ?>" min="0"></div>
            <div><label>Time do artilheiro</label><input type="number" name="pts_artilheiro" value="<?= (int)$pool['pts_artilheiro'] ?>" min="0"></div>
        </div>
        <p style="margin-top:16px"><button class="btn" type="submit">Salvar configurações</button></p>
    </form>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
