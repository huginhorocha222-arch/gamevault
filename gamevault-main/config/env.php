<?php
declare(strict_types=1);

/**
 * Carregador simples de variáveis de ambiente (.env).
 *
 * Lê o arquivo linha a linha no formato CHAVE=VALOR, ignora comentários (#)
 * e disponibiliza os valores via $_ENV, getenv() e a função env().
 * Nenhuma credencial fica fixada no código-fonte.
 */
function loadEnv(string $path): void
{
    if (!is_file($path)) {
        throw new RuntimeException(
            "Arquivo .env não encontrado em: {$path}. " .
            "Copie o arquivo .env.example para .env e preencha as credenciais do banco."
        );
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        throw new RuntimeException("Não foi possível ler o arquivo .env em: {$path}");
    }

    foreach ($lines as $line) {
        $line = trim($line);

        // Ignora linhas vazias, comentários e linhas sem "="
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Remove aspas simples ou duplas ao redor do valor
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last  = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}

/**
 * Retorna o valor de uma variável de ambiente ou o padrão informado.
 */
function env(string $key, ?string $default = null): ?string
{
    if (array_key_exists($key, $_ENV) && $_ENV[$key] !== '') {
        return (string) $_ENV[$key];
    }

    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}
