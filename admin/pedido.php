<?php
require_once dirname(__DIR__) . '/includes/init.php';

require_admin();

$id     = to_int($_GET['id'] ?? 0);
$pedido = $id > 0 ? pedido_buscar($id) : null;

if (!$pedido) {
    flash('warning', 'Pedido não encontrado.');
    redirect(url('admin/pedidos.php'));
}

$itens = pedido_itens($id);

$pageTitle = 'Admin · Pedido #' . $id;
$adminTab  = 'pedidos';
require BASE_PATH . '/includes/layout/header.php';
require __DIR__ . '/_nav.php';
?>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
          <h2 class="h5 mb-1">Pedido #<?= $id ?></h2>
          <div class="text-secondary small"><?= e(format_datetime($pedido['data_pedido'])) ?></div>
        </div>
        <span class="badge bg-<?= status_badge($pedido['status']) ?> fs-6"><?= e(ucfirst($pedido['status'])) ?></span>
      </div>

      <div class="row mb-3">
        <div class="col-md-4"><small class="text-secondary d-block">Cliente</small><?= e($pedido['cliente_nome']) ?></div>
        <div class="col-md-4"><small class="text-secondary d-block">E-mail</small><?= e($pedido['cliente_email']) ?></div>
        <div class="col-md-4"><small class="text-secondary d-block">Pagamento</small><?= e(payment_label($pedido['forma_pagamento'])) ?></div>
      </div>

      <table class="table align-middle">
        <thead class="table-light">
          <tr><th>Produto</th><th class="text-center">Qtd</th><th class="text-end">Preço unit. (na compra)</th><th class="text-end">Subtotal</th></tr>
        </thead>
        <tbody>
          <?php foreach ($itens as $i): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <img src="<?= e($i['imagem_url'] ?: 'https://placehold.co/600x400?text=Sem+imagem') ?>" class="rounded gv-cart-thumb" alt="">
                  <span><?= e($i['produto_nome']) ?> <small class="text-secondary">(#<?= (int) $i['produto_id'] ?>)</small></span>
                </div>
              </td>
              <td class="text-center"><?= (int) $i['quantidade'] ?></td>
              <td class="text-end"><?= money($i['preco_unitario']) ?></td>
              <td class="text-end"><?= money($i['subtotal']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="fw-bold fs-5"><td colspan="3" class="text-end">Total</td><td class="text-end"><?= money($pedido['total']) ?></td></tr>
        </tfoot>
      </table>

      <a href="<?= e(url('admin/pedidos.php')) ?>" class="btn btn-outline-dark"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    </div>
  </div>
</div>

<?php require BASE_PATH . '/includes/layout/footer.php'; ?>
