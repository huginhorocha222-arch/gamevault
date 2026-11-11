<?php
declare(strict_types=1);

/**
 * Carrinho de compras gerenciado em $_SESSION['cart'].
 * Estrutura: [ produto_id => quantidade ]
 *
 * Os dados do produto (preço, estoque, nome) são sempre lidos do banco
 * no momento de exibir/finalizar, garantindo valores atualizados.
 */

function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(cart());
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
}

/**
 * Adiciona um produto ao carrinho.
 * Retorna null em sucesso ou uma mensagem de erro.
 */
function cart_add(int $produtoId, int $quantidade = 1): ?string
{
    $produto = produto_buscar($produtoId, true);
    if (!$produto) {
        return 'Produto não encontrado ou indisponível.';
    }

    $quantidade = max(1, $quantidade);
    $atual      = $_SESSION['cart'][$produtoId] ?? 0;
    $nova       = $atual + $quantidade;

    if ((int) $produto['estoque'] <= 0) {
        return 'Este produto está esgotado.';
    }
    if ($nova > (int) $produto['estoque']) {
        return sprintf(
            'Estoque insuficiente para "%s". Disponível: %d unidade(s).',
            $produto['nome'],
            (int) $produto['estoque']
        );
    }

    $_SESSION['cart'][$produtoId] = $nova;
    return null;
}

/**
 * Define a quantidade de um item (0 remove).
 * Retorna null em sucesso ou mensagem de erro.
 */
function cart_update(int $produtoId, int $quantidade): ?string
{
    if (!isset($_SESSION['cart'][$produtoId])) {
        return 'Item não está no carrinho.';
    }
    if ($quantidade <= 0) {
        cart_remove($produtoId);
        return null;
    }

    $produto = produto_buscar($produtoId, true);
    if (!$produto) {
        cart_remove($produtoId);
        return 'Produto não está mais disponível e foi removido do carrinho.';
    }
    if ($quantidade > (int) $produto['estoque']) {
        $_SESSION['cart'][$produtoId] = max(1, (int) $produto['estoque']);
        return sprintf(
            'Quantidade ajustada: "%s" possui apenas %d unidade(s) em estoque.',
            $produto['nome'],
            (int) $produto['estoque']
        );
    }

    $_SESSION['cart'][$produtoId] = $quantidade;
    return null;
}

function cart_remove(int $produtoId): void
{
    unset($_SESSION['cart'][$produtoId]);
    if (empty($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }
}

/**
 * Retorna os itens do carrinho com os dados atuais do banco e o total.
 * Itens inativos/inexistentes são removidos automaticamente.
 *
 * @return array{items: array, total: float, count: int}
 */
function cart_items(): array
{
    $cart = cart();
    if (empty($cart)) {
        return ['items' => [], 'total' => 0.0, 'count' => 0];
    }

    $ids          = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = db()->prepare(
        "SELECT p.id, p.nome, p.preco, p.estoque, p.imagem_url, p.ativo, c.nome AS categoria
           FROM produtos p
           JOIN categorias c ON c.id = p.categoria_id
          WHERE p.id IN ($placeholders)"
    );
    $stmt->execute($ids);
    $produtos = [];
    foreach ($stmt->fetchAll() as $row) {
        $produtos[(int) $row['id']] = $row;
    }

    $items = [];
    $total = 0.0;
    $count = 0;

    foreach ($cart as $id => $qtd) {
        $id = (int) $id;
        if (!isset($produtos[$id]) || !(int) $produtos[$id]['ativo']) {
            cart_remove($id);
            continue;
        }
        $p        = $produtos[$id];
        $qtd      = (int) $qtd;
        $subtotal = round((float) $p['preco'] * $qtd, 2);

        $items[] = [
            'id'         => $id,
            'nome'       => $p['nome'],
            'categoria'  => $p['categoria'],
            'imagem_url' => $p['imagem_url'],
            'preco'      => (float) $p['preco'],
            'estoque'    => (int) $p['estoque'],
            'quantidade' => $qtd,
            'subtotal'   => $subtotal,
        ];
        $total += $subtotal;
        $count += $qtd;
    }

    return ['items' => $items, 'total' => round($total, 2), 'count' => $count];
}
