<?php
require __DIR__ . '/includes/bootstrap.php';
logout_user();
session_start();
flash('Você saiu da sua conta.', 'info');
redirect('login.php');
