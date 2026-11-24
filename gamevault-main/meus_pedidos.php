<?php
require_once __DIR__ . '/includes/init.php';

require_login();

$pedidos = pedidos_do_usuario((int) current_user()['id']);

$pageTitle = 'Meus pedidos';
require BASE_PATH . '/includes/layout/header.php';
?>

<div class="container my-4">
  <h1 class="h3 mb-4"><i class="bi bi-bag-check me-2"></i>Meus pedidos</h1>

  <?php if (empty($pedidos)): ?>
    <div class="text-center py-5 bg-white border rounded">
      <i class="bi bi-bag display-4 text-secondary d-block mb-3"></i>
      <p class="lead">Você ainda não fez nenhum pedido.</p>
      <a href="<?= e(url('index.php')) ?>" class="btn btn-dark">Ver produtos</a>
    </div>
  <?php else: ?>
    <div class="table-responsive bg-white border rounded">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Pedido</th>
            <th>Data</th>
            <th>Pagamento</th>
            <th class="text-center">Itens</th>
            <th>Status</th>
            <th class="text-end">Total</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pedidos as $p): ?>
            <tr>
              <td class="fw-semibold">#<?= (int) $p['id'] ?></td>
              <td><?= e(format_datetime($p['data_pedido'])) ?></td>
              <td><?= e(payment_label($p['forma_pagamento'])) ?></td>
              <td class="text-center"><?= (int) $p['qtd_itens'] ?></td>
              <td><span class="badge bg-<?= status_badge($p['status']) ?>"><?= e(ucfirst($p['status'])) ?></span></td>
              <td class="text-end fw-semibold"><?= money($p['total']) ?></td>
              <td class="text-end">
                <a href="<?= e(url('pedido.php?id=' . (int) $p['id'])) ?>" class="btn btn-sm btn-outline-dark">Detalhes</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require BASE_PATH . '/includes/layout/footer.php'; ?>
