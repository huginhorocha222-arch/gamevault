<?php
declare(strict_types=1);

/**
 * Bootstrap da aplicação: deve ser o primeiro require de toda página.
 */

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/config/env.php';
loadEnv(BASE_PATH . '/.env');

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/helpers.php';
require_once BASE_PATH . '/includes/models.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/cart.php';

// Sessão com cookie HttpOnly e SameSite (mitiga XSS/CSRF)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_name('GAMEVAULT_SESSION');
    session_start();
}

// Token CSRF único por sessão
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

date_default_timezone_set('America/Sao_Paulo');
