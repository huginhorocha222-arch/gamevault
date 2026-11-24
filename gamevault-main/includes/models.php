<?php
declare(strict_types=1);

/**
 * Camada de acesso a dados (CRUD) usando exclusivamente prepared statements.
 */

/* ============================== CATEGORIAS ============================== */

function categorias_todas(): array
{
    return db()->query('SELECT id, nome, slug FROM categorias ORDER BY nome')->fetchAll();
}

function categoria_buscar_por_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT id, nome, slug FROM categorias WHERE slug = :slug LIMIT 1');
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/* =============================== PRODUTOS =============================== */

/**
 * Lista produtos com filtro opcional por categoria e busca textual.
 */
function produtos_listar(?int $categoriaId = null, ?string $busca = null, bool $somenteAtivos = true): array
{
    $sql    = 'SELECT p.*, c.nome AS categoria, c.slug AS categoria_slug
                 FROM produtos p
                 JOIN categorias c ON c.id = p.categoria_id
                WHERE 1 = 1';
    $params = [];

    if ($somenteAtivos) {
        $sql .= ' AND p.ativo = 1';
    }
    if ($categoriaId) {
        $sql .= ' AND p.categoria_id = :categoria_id';
        $params[':categoria_id'] = $categoriaId;
    }
    if ($busca !== null && trim($busca) !== '') {
        $sql .= ' AND (p.nome LIKE :busca OR p.descricao LIKE :busca2)';
        $like = '%' . trim($busca) . '%';
        $params[':busca']  = $like;
        $params[':busca2'] = $like;
    }

    $sql .= ' ORDER BY p.ativo DESC, p.nome ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function produto_buscar(int $id, bool $somenteAtivo = false): ?array
{
    $sql = 'SELECT p.*, c.nome AS categoria, c.slug AS categoria_slug
              FROM produtos p
              JOIN categorias c ON c.id = p.categoria_id
             WHERE p.id = :id';
    if ($somenteAtivo) {
        $sql .= ' AND p.ativo = 1';
    }
    $stmt = db()->prepare($sql . ' LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function produto_criar(array $d): int
{
    $stmt = db()->prepare(
        'INSERT INTO produtos (categoria_id, nome, descricao, preco, estoque, imagem_url, ativo)
         VALUES (:categoria_id, :nome, :descricao, :preco, :estoque, :imagem_url, :ativo)'
    );
    $stmt->execute([
        ':categoria_id' => $d['categoria_id'],
        ':nome'         => $d['nome'],
        ':descricao'    => $d['descricao'],
        ':preco'        => $d['preco'],
        ':estoque'      => $d['estoque'],
        ':imagem_url'   => $d['imagem_url'],
        ':ativo'        => $d['ativo'],
    ]);
    return (int) db()->lastInsertId();
}

function produto_atualizar(int $id, array $d): void
{
    $stmt = db()->prepare(
        'UPDATE produtos
            SET categoria_id = :categoria_id,
                nome         = :nome,
                descricao    = :descricao,
                preco        = :preco,
                estoque      = :estoque,
                imagem_url   = :imagem_url,
                ativo        = :ativo
          WHERE id = :id'
    );
    $stmt->execute([
        ':categoria_id' => $d['categoria_id'],
        ':nome'         => $d['nome'],
        ':descricao'    => $d['descricao'],
        ':preco'        => $d['preco'],
        ':estoque'      => $d['estoque'],
        ':imagem_url'   => $d['imagem_url'],
        ':ativo'        => $d['ativo'],
        ':id'           => $id,
    ]);
}

function produto_definir_ativo(int $id, bool $ativo): void
{
    $stmt = db()->prepare('UPDATE produtos SET ativo = :ativo WHERE id = :id');
    $stmt->execute([':ativo' => $ativo ? 1 : 0, ':id' => $id]);
}

/**
 * Exclui o produto definitivamente.
 * Se ele já aparece em algum pedido, apenas desativa (preserva o histórico).
 * Retorna 'excluido' ou 'desativado'.
 */
function produto_excluir(int $id): string
{
    $check = db()->prepare('SELECT COUNT(*) FROM pedido_itens WHERE produto_id = :id');
    $check->execute([':id' => $id]);

    if ((int) $check->fetchColumn() > 0) {
        produto_definir_ativo($id, false);
        return 'desativado';
    }

    $stmt = db()->prepare('DELETE FROM produtos WHERE id = :id');
    $stmt->execute([':id' => $id]);
    return 'excluido';
}

/* ================================ PEDIDOS ================================ */

/**
 * Finaliza a compra em uma transação:
 *  1. bloqueia e valida o estoque de cada produto (SELECT ... FOR UPDATE)
 *  2. cria o registro do pedido
 *  3. grava cada item com o preço unitário congelado
 *  4. decrementa o estoque
 * Lança RuntimeException em caso de estoque insuficiente.
 */
function pedido_criar(int $usuarioId, string $formaPagamento, array $cart): int
{
    if (!in_array($formaPagamento, ['pix', 'boleto', 'cartao'], true)) {
        throw new InvalidArgumentException('Forma de pagamento inválida.');
    }
    if (empty($cart)) {
        throw new RuntimeException('O carrinho está vazio.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $lock = $pdo->prepare(
            'SELECT id, nome, preco, estoque, ativo FROM produtos WHERE id = :id FOR UPDATE'
        );
        $itens = [];
        $total = 0.0;

        foreach ($cart as $produtoId => $qtd) {
            $produtoId = (int) $produtoId;
            $qtd       = (int) $qtd;

            $lock->execute([':id' => $produtoId]);
            $p = $lock->fetch();

            if (!$p || !(int) $p['ativo']) {
                throw new RuntimeException('Um dos produtos do carrinho não está mais disponível.');
            }
            if ($qtd <= 0 || $qtd > (int) $p['estoque']) {
                throw new RuntimeException(sprintf(
                    'Estoque insuficiente para "%s" (disponível: %d).',
                    $p['nome'],
                    (int) $p['estoque']
                ));
            }

            $itens[] = [
                'produto_id'     => $produtoId,
                'quantidade'     => $qtd,
                'preco_unitario' => (float) $p['preco'],
            ];
            $total += (float) $p['preco'] * $qtd;
        }

        $total = round($total, 2);

        $insPedido = $pdo->prepare(
            'INSERT INTO pedidos (usuario_id, forma_pagamento, status, total)
             VALUES (:usuario_id, :forma_pagamento, :status, :total)'
        );
        $insPedido->execute([
            ':usuario_id'      => $usuarioId,
            ':forma_pagamento' => $formaPagamento,
            ':status'          => 'pago', // pagamento simulado com confirmação imediata
            ':total'           => $total,
        ]);
        $pedidoId = (int) $pdo->lastInsertId();

        $insItem = $pdo->prepare(
            'INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario)
             VALUES (:pedido_id, :produto_id, :quantidade, :preco_unitario)'
        );
        $baixa = $pdo->prepare(
            'UPDATE produtos SET estoque = estoque - :qtd WHERE id = :id AND estoque >= :qtd2'
        );

        foreach ($itens as $item) {
            $insItem->execute([
                ':pedido_id'      => $pedidoId,
                ':produto_id'     => $item['produto_id'],
                ':quantidade'     => $item['quantidade'],
                ':preco_unitario' => $item['preco_unitario'],
            ]);

            $baixa->execute([
                ':qtd'  => $item['quantidade'],
                ':id'   => $item['produto_id'],
                ':qtd2' => $item['quantidade'],
            ]);
            if ($baixa->rowCount() !== 1) {
                throw new RuntimeException('Falha ao baixar o estoque. Tente novamente.');
            }
        }

        $pdo->commit();
        return $pedidoId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function pedidos_do_usuario(int $usuarioId): array
{
    $stmt = db()->prepare(
        'SELECT p.*, (SELECT SUM(quantidade) FROM pedido_itens i WHERE i.pedido_id = p.id) AS qtd_itens
           FROM pedidos p
          WHERE p.usuario_id = :uid
          ORDER BY p.data_pedido DESC, p.id DESC'
    );
    $stmt->execute([':uid' => $usuarioId]);
    return $stmt->fetchAll();
}

/** Busca um pedido; se $usuarioId for informado, garante que pertence a ele. */
function pedido_buscar(int $id, ?int $usuarioId = null): ?array
{
    $sql    = 'SELECT p.*, u.nome AS cliente_nome, u.email AS cliente_email
                 FROM pedidos p
                 JOIN usuarios u ON u.id = p.usuario_id
                WHERE p.id = :id';
    $params = [':id' => $id];
    if ($usuarioId !== null) {
        $sql .= ' AND p.usuario_id = :uid';
        $params[':uid'] = $usuarioId;
    }
    $stmt = db()->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function pedido_itens(int $pedidoId): array
{
    $stmt = db()->prepare(
        'SELECT i.*, pr.nome AS produto_nome, pr.imagem_url,
                (i.quantidade * i.preco_unitario) AS subtotal
           FROM pedido_itens i
           JOIN produtos pr ON pr.id = i.produto_id
          WHERE i.pedido_id = :id
          ORDER BY i.id'
    );
    $stmt->execute([':id' => $pedidoId]);
    return $stmt->fetchAll();
}

function pedidos_todos(): array
{
    return db()->query(
        'SELECT p.*, u.nome AS cliente_nome, u.email AS cliente_email,
                (SELECT SUM(quantidade) FROM pedido_itens i WHERE i.pedido_id = p.id) AS qtd_itens
           FROM pedidos p
           JOIN usuarios u ON u.id = p.usuario_id
          ORDER BY p.data_pedido DESC, p.id DESC'
    )->fetchAll();
}

/** Números resumidos para o dashboard do admin. */
function admin_resumo(): array
{
    $pdo = db();
    return [
        'produtos'      => (int) $pdo->query('SELECT COUNT(*) FROM produtos WHERE ativo = 1')->fetchColumn(),
        'sem_estoque'   => (int) $pdo->query('SELECT COUNT(*) FROM produtos WHERE ativo = 1 AND estoque = 0')->fetchColumn(),
        'pedidos'       => (int) $pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn(),
        'faturamento'   => (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE status = 'pago'")->fetchColumn(),
        'clientes'      => (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE perfil = 'cliente'")->fetchColumn(),
    ];
}
