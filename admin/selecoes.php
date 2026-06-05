<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $id = (int)($_POST['team_id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $sigla = strtoupper(trim($_POST['sigla'] ?? ''));
    $bandeira = trim($_POST['bandeira'] ?? '');
    $grupo = strtoupper(trim($_POST['grupo'] ?? '')) ?: null;
    if ($id && $nome !== '') {
        db()->prepare('UPDATE teams SET nome=?, sigla=?, bandeira=?, grupo=? WHERE id=?')
            ->execute([$nome, substr($sigla,0,3), substr($bandeira,0,8) ?: null, $grupo, $id]);
        flash('Seleção atualizada.', 'success');
    }
    redirect('selecoes.php');
}

$teams = db()->query('SELECT * FROM teams ORDER BY grupo, nome')->fetchAll();
$page_title = 'Seleções';
require __DIR__ . '/../includes/header.php';
?>
<h1>Seleções</h1>
<p><a href="<?= e(APP_URL) ?>/admin/index.php">← Admin</a></p>
<div class="card">
    <table>
        <thead><tr><th>Grupo</th><th>Bandeira</th><th>Nome</th><th>Sigla</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($teams as $t): ?>
            <tr>
                <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="team_id" value="<?= (int)$t['id'] ?>">
                <td><input type="text" name="grupo" value="<?= e($t['grupo']) ?>" maxlength="1" style="width:42px"></td>
                <td><input type="text" name="bandeira" value="<?= e($t['bandeira']) ?>" style="width:60px"></td>
                <td><input type="text" name="nome" value="<?= e($t['nome']) ?>"></td>
                <td><input type="text" name="sigla" value="<?= e($t['sigla']) ?>" maxlength="3" style="width:64px"></td>
                <td><button class="btn btn-sm" type="submit">Salvar</button></td>
                </form>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!$teams): ?><p class="muted">Nenhuma seleção cadastrada (importe os seeds SQL).</p><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
