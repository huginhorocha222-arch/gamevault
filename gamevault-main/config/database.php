<?php
declare(strict_types=1);

/**
 * Conexão com o banco de dados via PDO (MySQL/MariaDB).
 *
 * As credenciais vêm exclusivamente do arquivo .env (ver config/env.php).
 * A conexão é criada uma única vez por requisição (padrão singleton simples).
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = env('DB_HOST', 'localhost');
    $port = env('DB_PORT', '3306');
    $name = env('DB_NAME');
    $user = env('DB_USER');
    $pass = env('DB_PASS', '');

    if ($name === null || $user === null) {
        throw new RuntimeException(
            'Variáveis DB_NAME e DB_USER precisam estar definidas no arquivo .env.'
        );
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // lança exceções em erros SQL
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // arrays associativos
        PDO::ATTR_EMULATE_PREPARES   => false,                  // prepared statements reais no servidor
    ];

    $pdo = new PDO($dsn, $user, (string) $pass, $options);

    return $pdo;
}
