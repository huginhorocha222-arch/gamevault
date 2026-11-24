<?php
declare(strict_types=1);

/**
 * Funções utilitárias usadas em toda a aplicação.
 */

/** Escapa texto para saída segura em HTML (prevenção de XSS). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Formata um valor monetário no padrão brasileiro: R$ 1.234,56 */
function money(float|int|string $value): string
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

/** Formata data/hora vinda do banco (Y-m-d H:i:s) para dd/mm/aaaa HH:MM */
function format_datetime(?string $value): string
{
    if (!$value) {
        return '-';
    }
    $ts = strtotime($value);
    return $ts ? date('d/m/Y H:i', $ts) : $value;
}

/**
 * Calcula a URL base da aplicação.
 * Usa APP_URL do .env quando informado; caso contrário detecta a pasta
 * do projeto dentro do htdocs automaticamente (ex.: http://localhost/gamevault).
 */
function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $configured = env('APP_URL');
    if ($configured) {
        return $base = rtrim($configured, '/');
    }

    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $docRoot = $docRoot ? str_replace('\\', '/', $docRoot) : '';
    $root    = str_replace('\\', '/', BASE_PATH);

    $path = '';
    if ($docRoot !== '' && str_starts_with($root, $docRoot)) {
        $path = substr($root, strlen($docRoot));
    }

    $https  = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $base = $scheme . '://' . $host . rtrim($path, '/');
}

/** Monta uma URL absoluta a partir de um caminho relativo ao projeto. */
function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

/** Redireciona e encerra a execução. */
function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

/** Caminho atual (para "voltar depois do login"). */
function current_path(): string
{
    return $_SERVER['REQUEST_URI'] ?? url('index.php');
}

/** Grava uma mensagem flash (exibida uma única vez na próxima página). */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Lê e limpa as mensagens flash pendentes. */
function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/** Campo oculto com o token CSRF para formulários POST. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token'] ?? '') . '">';
}

/** Valida o token CSRF enviado no POST. Aborta a requisição se inválido. */
function verify_csrf(): void
{
    $sent  = $_POST['csrf_token'] ?? '';
    $saved = $_SESSION['csrf_token'] ?? '';
    if ($sent === '' || $saved === '' || !hash_equals($saved, $sent)) {
        http_response_code(419);
        exit('Token de segurança inválido. Recarregue a página e tente novamente.');
    }
}

/** Retorna um valor antigo do formulário (para repopular campos após erro). */
function old(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? (string) $_POST[$key] : $default;
}

/** Converte um valor de entrada para inteiro positivo (ou 0 se inválido). */
function to_int(mixed $value): int
{
    return max(0, (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT));
}

/** Converte "1.234,56" ou "1234.56" para float. */
function to_decimal(mixed $value): float
{
    $value = trim((string) $value);
    if (str_contains($value, ',')) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    }
    return round((float) $value, 2);
}

/** Rótulo amigável da forma de pagamento. */
function payment_label(string $method): string
{
    return match ($method) {
        'pix'    => 'PIX',
        'boleto' => 'Boleto bancário',
        'cartao' => 'Cartão de crédito',
        default  => ucfirst($method),
    };
}

/** Classe de badge do Bootstrap de acordo com o status do pedido. */
function status_badge(string $status): string
{
    return match ($status) {
        'pago'      => 'success',
        'pendente'  => 'warning',
        'cancelado' => 'danger',
        default     => 'secondary',
    };
}
