<?php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_admin();

$campos = [
    'campeao_team_id'    => 'Campeão',
    'vice_team_id'       => 'Vice-campeão',
    'terceiro_team_id'   => '3º lugar',
    'quarto_team_id'     => '4º lugar',
    'artilheiro_team_id' => 'Time do artilheiro',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $vals = [];
    foreach (array_keys($campos) as $c) {
        $v = (int)($_POST[$c] ?? 0);
        $vals[$c] = $v > 0 ? $v : null;
    }
    $sql = 'UPDATE tournament_results SET campeao_team_id=?, vice_team_id=?, terceiro_team_id=?,
               quarto_team_id=?, artilheiro_team_id=? WHERE id=1';
    db()->prepare($sql)->execute(array_values($vals));
    recalc_bonus_points();
    flash('Gabarito salvo e pontos de bônus recalculados.', 'success');
    redirect('torneio.php');
}

$gab = db()->query('SELECT * FROM tournament_results WHERE id=1')->fetch() ?: [];
$teams = db()->query('SELECT id, nome, bandeira, grupo FROM teams ORDER BY grupo, nome')->fetchAll();

$page_title = 'Gabarito dos bônus';
require __DIR__ . '/../includes/header.php';
?>
<h1>Gabarito dos bônus</h1>
<p><a href="<?= e(APP_URL) ?>/admin/index.php">← Admin</a></p>
<div class="card">
    <p class="muted">Defina ao fim da Copa. Ao salvar, os pontos de bônus de todos os bolões são recalculados.</p>
    <form method="post">
        <?= csrf_field() ?>
        <?php foreach ($campos as $col => $label): ?>
            <label><?= e($label) ?></label>
            <select name="<?= e($col) ?>">
                <option value="0">— não definido —</option>
                <?php foreach ($teams as $t): ?>
                    <option value="<?= (int)$t['id'] ?>" <?= (int)($gab[$col] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>>
                        <?= e(($t['bandeira'] ? $t['bandeira'] . ' ' : '') . $t['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endforeach; ?>
        <p style="margin-top:16px"><button class="btn" type="submit">Salvar gabarito</button></p>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
