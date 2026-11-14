<?php
require_once __DIR__ . '/includes/init.php';

// Logout apenas via POST (com token CSRF) para evitar logout forçado por link externo
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('index.php'));
}

verify_csrf();
logout_user();

// Nova sessão apenas para carregar a mensagem flash
session_start();
flash('info', 'Você saiu da sua conta.');
redirect(url('index.php'));
