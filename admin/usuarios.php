<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $id = (int)($_POST['user_id'] ?? 0);
    $acao = $_POST['acao'] ?? '';
    if ($id && $id !== (int)$u['id']) {
        if ($acao === 'promover') {
            db()->prepare('UPDATE users SET is_admin=1 WHERE id=?')->execute([$id]);
            flash('Usuário promovido a admin.', 'success');
        } elseif ($acao === 'rebaixar') {
            db()->prepare('UPDATE users SET is_admin=0 WHERE id=?')->execute([$id]);
            flash('Admin removido.', 'info');
        }
    }
    redirect('usuarios.php');
}

$users = db()->query('SELECT id, nome, email, is_admin, criado_em FROM users ORDER BY criado_em')->fetchAll();
$page_title = 'Usuários';
require __DIR__ . '/../includes/header.php';
?>
<h1>Usuários</h1>
<p><a href="<?= e(APP_URL) ?>/admin/index.php">← Admin</a></p>
<div class="card">
    <table>
        <thead><tr><th>Nome</th><th>E-mail</th><th>Admin</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $usr): ?>
            <tr>
                <td><?= e($usr['nome']) ?></td>
                <td class="muted"><?= e($usr['email']) ?></td>
                <td><?= (int)$usr['is_admin'] === 1 ? 'Sim' : '—' ?></td>
                <td style="text-align:right">
                    <?php if ((int)$usr['id'] !== (int)$u['id']): ?>
                        <form method="post" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="user_id" value="<?= (int)$usr['id'] ?>">
                            <input type="hidden" name="acao" value="<?= (int)$usr['is_admin'] === 1 ? 'rebaixar' : 'promover' ?>">
                            <button class="btn btn-sm btn-secondary" type="submit"><?= (int)$usr['is_admin'] === 1 ? 'Remover admin' : 'Tornar admin' ?></button>
                        </form>
                    <?php else: ?><span class="muted">você</span><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
