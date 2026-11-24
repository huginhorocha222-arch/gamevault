<?php
require_once __DIR__ . '/includes/init.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('index.php'));
}

verify_csrf();
logout_user();


session_start();
flash('info', 'Você saiu da sua conta.');
redirect(url('index.php'));
