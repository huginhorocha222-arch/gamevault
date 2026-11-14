<?php
require_once dirname(__DIR__) . '/includes/init.php';

require_admin();

$pedidos = pedidos_todos();

$pageTitle = 'Admin · Pedidos';
$adminTab  = 'pedidos';
require BASE_PATH . '/includes/layout/header.php';
require __DIR__ . '/_nav.php';
?>

  <div class="table-responsive bg-white border rounded">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>#</th><th>Cliente</th><th>Data</th><th>Pagamento</th>
          <th class="text-center">Itens</th><th>Status</th><th class="text-end">Total</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($pedidos)): ?>
          <tr><td colspan="8" class="text-center text-secondary py-4">Nenhum pedido realizado.</td></tr>
        <?php endif; ?>
        <?php foreach ($pedidos as $p): ?>
          <tr>
            <td class="fw-semibold">#<?= (int) $p['id'] ?></td>
            <td><?= e($p['cliente_nome']) ?><br><small class="text-secondary"><?= e($p['cliente_email']) ?></small></td>
            <td><?= e(format_datetime($p['data_pedido'])) ?></td>
            <td><?= e(payment_label($p['forma_pagamento'])) ?></td>
            <td class="text-center"><?= (int) $p['qtd_itens'] ?></td>
            <td><span class="badge bg-<?= status_badge($p['status']) ?>"><?= e(ucfirst($p['status'])) ?></span></td>
            <td class="text-end fw-semibold"><?= money($p['total']) ?></td>
            <td class="text-end"><a href="<?= e(url('admin/pedido.php?id=' . (int) $p['id'])) ?>" class="btn btn-sm btn-outline-dark">Itens</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require BASE_PATH . '/includes/layout/footer.php'; ?>
