<?php
/**
 * Cabeçalho/layout. Defina $page_title antes de incluir, se quiser.
 * Use require __DIR__ . '/includes/footer.php'; ao final da página.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$u = current_user();
$title = isset($page_title) ? ($page_title . ' · ' . APP_NAME) : APP_NAME;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="csrf" content="<?= e(csrf_token()) ?>">
    <script>window.APP_URL = <?= json_encode(APP_URL) ?>; window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;</script>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/style.css?v=1">
</head>
<body>
<header class="topbar">
    <div class="container topbar-inner">
        <a class="brand" href="<?= e(APP_URL) ?>/dashboard.php">⚽ <?= e(APP_NAME) ?></a>
        <nav class="nav">
            <?php if ($u): ?>
                <a href="<?= e(APP_URL) ?>/dashboard.php">Meus bolões</a>
                <?php if (is_admin()): ?>
                    <a href="<?= e(APP_URL) ?>/admin/index.php">Admin</a>
                <?php endif; ?>
                <span class="nav-user"><?= e($u['nome']) ?></span>
                <a class="btn btn-ghost" href="<?= e(APP_URL) ?>/logout.php">Sair</a>
            <?php else: ?>
                <a href="<?= e(APP_URL) ?>/login.php">Entrar</a>
                <a class="btn" href="<?= e(APP_URL) ?>/registro.php">Criar conta</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
    <?php foreach (get_flashes() as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>
