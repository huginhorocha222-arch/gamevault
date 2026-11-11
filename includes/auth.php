<?php
declare(strict_types=1);

/**
 * Autenticação e controle de acesso baseado em sessão.
 *
 * - Senhas nunca são armazenadas em texto puro: password_hash() / password_verify().
 * - Todas as consultas usam prepared statements.
 * - O perfil de administrador é revalidado no banco a cada acesso ao /admin.
 */

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']['id']);
}

function is_admin(): bool
{
    return (current_user()['perfil'] ?? null) === 'admin';
}

/** Exige usuário autenticado; caso contrário envia para o login e volta depois. */
function require_login(): void
{
    if (is_logged_in()) {
        return;
    }
    $_SESSION['redirect_after_login'] = current_path();
    flash('warning', 'Faça login para continuar.');
    redirect(url('login.php'));
}

/**
 * Exige perfil administrador.
 * Além de checar a sessão, confirma no banco que o usuário ainda é admin.
 */
function require_admin(): void
{
    require_login();

    $stmt = db()->prepare('SELECT perfil FROM usuarios WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => current_user()['id']]);
    $perfil = $stmt->fetchColumn();

    if ($perfil !== 'admin') {
        // Mantém a sessão coerente com o banco
        if ($perfil === false) {
            logout_user();
        } else {
            $_SESSION['user']['perfil'] = $perfil;
        }
        http_response_code(403);
        flash('danger', 'Acesso restrito ao painel administrativo.');
        redirect(url('index.php'));
    }
}

/** Tenta autenticar. Retorna true em sucesso. */
function attempt_login(string $email, string $senha): bool
{
    $stmt = db()->prepare(
        'SELECT id, nome, email, senha_hash, perfil FROM usuarios WHERE email = :email LIMIT 1'
    );
    $stmt->execute([':email' => mb_strtolower(trim($email))]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($senha, $user['senha_hash'])) {
        return false;
    }

    // Se o algoritmo padrão mudou, atualiza o hash de forma transparente
    if (password_needs_rehash($user['senha_hash'], PASSWORD_DEFAULT)) {
        $upd = db()->prepare('UPDATE usuarios SET senha_hash = :hash WHERE id = :id');
        $upd->execute([':hash' => password_hash($senha, PASSWORD_DEFAULT), ':id' => $user['id']]);
    }

    // Evita fixação de sessão
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'     => (int) $user['id'],
        'nome'   => $user['nome'],
        'email'  => $user['email'],
        'perfil' => $user['perfil'],
    ];

    return true;
}

/**
 * Cadastra um novo cliente.
 * Retorna o id criado ou lança InvalidArgumentException com a mensagem de erro.
 */
function register_user(string $nome, string $email, string $senha): int
{
    $nome  = trim($nome);
    $email = mb_strtolower(trim($email));

    if (mb_strlen($nome) < 3) {
        throw new InvalidArgumentException('Informe seu nome completo (mínimo 3 caracteres).');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Informe um e-mail válido.');
    }
    if (strlen($senha) < 6) {
        throw new InvalidArgumentException('A senha deve ter pelo menos 6 caracteres.');
    }

    $check = db()->prepare('SELECT 1 FROM usuarios WHERE email = :email LIMIT 1');
    $check->execute([':email' => $email]);
    if ($check->fetchColumn()) {
        throw new InvalidArgumentException('Este e-mail já está cadastrado.');
    }

    $stmt = db()->prepare(
        'INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (:nome, :email, :hash, :perfil)'
    );
    $stmt->execute([
        ':nome'   => $nome,
        ':email'  => $email,
        ':hash'   => password_hash($senha, PASSWORD_DEFAULT),
        ':perfil' => 'cliente',
    ]);

    return (int) db()->lastInsertId();
}

/** Encerra a sessão do usuário mantendo o carrinho limpo. */
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
