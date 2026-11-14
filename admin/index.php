<?php
require_once dirname(__DIR__) . '/includes/init.php';

require_admin(); // validação de sessão + perfil no servidor

$resumo  = admin_resumo();
$ultimos = array_slice(pedidos_todos(), 0, 5);

$pageTitle = 'Admin · Visão geral';
$adminTab  = 'dashboard';
require BASE_PATH . '/includes/layout/header.php';
require __DIR__ . '/_nav.php';
?>

  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="card shadow-sm h-100"><div class="card-body">
        <small class="text-secondary">Produtos ativos</small>
        <div class="fs-2 fw-bold"><?= $resumo['produtos'] ?></div>
        <small class="text-danger"><?= $resumo['sem_estoque'] ?> sem estoque</small>
      </div></div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card shadow-sm h-100"><div class="card-body">
        <small class="text-secondary">Pedidos</small>
        <div class="fs-2 fw-bold"><?= $resumo['pedidos'] ?></div>
      </div></div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card shadow-sm h-100"><div class="card-body">
        <small class="text-secondary">Faturamento (pago)</small>
        <div class="fs-4 fw-bold"><?= money($resumo['faturamento']) ?></div>
      </div></div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="card shadow-sm h-100"><div class="card-body">
        <small class="text-secondary">Clientes cadastrados</small>
        <div class="fs-2 fw-bold"><?= $resumo['clientes'] ?></div>
      </div></div>
    </div>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h5 mb-0">Últimos pedidos</h2>
    <a href="<?= e(url('admin/pedidos.php')) ?>" class="small">Ver todos</a>
  </div>
  <div class="table-responsive bg-white border rounded">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr><th>#</th><th>Cliente</th><th>Data</th><th>Pagamento</th><th>Status</th><th class="text-end">Total</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($ultimos)): ?>
          <tr><td colspan="7" class="text-center text-secondary py-4">Nenhum pedido ainda.</td></tr>
        <?php endif; ?>
        <?php foreach ($ultimos as $p): ?>
          <tr>
            <td class="fw-semibold">#<?= (int) $p['id'] ?></td>
            <td><?= e($p['cliente_nome']) ?></td>
            <td><?= e(format_datetime($p['data_pedido'])) ?></td>
            <td><?= e(payment_label($p['forma_pagamento'])) ?></td>
            <td><span class="badge bg-<?= status_badge($p['status']) ?>"><?= e(ucfirst($p['status'])) ?></span></td>
            <td class="text-end"><?= money($p['total']) ?></td>
            <td class="text-end"><a href="<?= e(url('admin/pedido.php?id=' . (int) $p['id'])) ?>" class="btn btn-sm btn-outline-dark">Ver</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require BASE_PATH . '/includes/layout/footer.php'; ?>
