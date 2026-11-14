<?php
require_once dirname(__DIR__) . '/includes/init.php';

require_admin();

$busca    = trim((string) ($_GET['busca'] ?? ''));
$produtos = produtos_listar(null, $busca !== '' ? $busca : null, false); // inclui inativos

$pageTitle = 'Admin · Produtos';
$adminTab  = 'produtos';
require BASE_PATH . '/includes/layout/header.php';
require __DIR__ . '/_nav.php';
?>

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form class="d-flex" method="get" action="<?= e(url('admin/produtos.php')) ?>">
      <div class="input-group input-group-sm">
        <input type="search" class="form-control" name="busca" placeholder="Buscar produto..." value="<?= e($busca) ?>">
        <button class="btn btn-outline-dark" type="submit"><i class="bi bi-search"></i></button>
      </div>
    </form>
    <a href="<?= e(url('admin/produto_form.php')) ?>" class="btn btn-dark btn-sm">
      <i class="bi bi-plus-lg me-1"></i>Novo produto
    </a>
  </div>

  <div class="table-responsive bg-white border rounded">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>#</th><th colspan="2">Produto</th><th>Categoria</th>
          <th class="text-end">Preço</th><th class="text-center">Estoque</th><th>Status</th><th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($produtos)): ?>
          <tr><td colspan="8" class="text-center text-secondary py-4">Nenhum produto encontrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($produtos as $p): ?>
          <tr class="<?= (int) $p['ativo'] ? '' : 'table-secondary' ?>">
            <td><?= (int) $p['id'] ?></td>
            <td style="width:70px"><img src="<?= e($p['imagem_url'] ?: 'https://placehold.co/600x400?text=Sem+imagem') ?>" class="rounded gv-cart-thumb" alt=""></td>
            <td class="fw-semibold"><?= e($p['nome']) ?></td>
            <td><?= e($p['categoria']) ?></td>
            <td class="text-end"><?= money($p['preco']) ?></td>
            <td class="text-center">
              <?php if ((int) $p['estoque'] === 0): ?>
                <span class="badge bg-danger">0</span>
              <?php elseif ((int) $p['estoque'] <= 5): ?>
                <span class="badge bg-warning text-dark"><?= (int) $p['estoque'] ?></span>
              <?php else: ?>
                <?= (int) $p['estoque'] ?>
              <?php endif; ?>
            </td>
            <td>
              <?php if ((int) $p['ativo']): ?>
                <span class="badge bg-success">Ativo</span>
              <?php else: ?>
                <span class="badge bg-secondary">Inativo</span>
              <?php endif; ?>
            </td>
            <td class="text-end text-nowrap">
              <a href="<?= e(url('admin/produto_form.php?id=' . (int) $p['id'])) ?>" class="btn btn-sm btn-outline-dark" title="Editar"><i class="bi bi-pencil"></i></a>
              <form action="<?= e(url('admin/produto_acao.php')) ?>" method="post" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <input type="hidden" name="acao" value="alternar">
                <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?= (int) $p['ativo'] ? 'Desativar' : 'Ativar' ?>">
                  <i class="bi bi-<?= (int) $p['ativo'] ? 'eye-slash' : 'eye' ?>"></i>
                </button>
              </form>
              <form action="<?= e(url('admin/produto_acao.php')) ?>" method="post" class="d-inline"
                    data-confirm="Excluir o produto &quot;<?= e($p['nome']) ?>&quot;? Se já houver vendas, ele será apenas desativado.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <input type="hidden" name="acao" value="excluir">
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require BASE_PATH . '/includes/layout/footer.php'; ?>
