<?php
require_once __DIR__ . '/includes/init.php';

require_login();

$id     = to_int($_GET['id'] ?? 0);
$pedido = $id > 0 ? pedido_buscar($id, (int) current_user()['id']) : null;

if (!$pedido) {
    flash('warning', 'Pedido não encontrado.');
    redirect(url('meus_pedidos.php'));
}

$itens = pedido_itens($id);

$pageTitle = 'Pedido #' . $id;
require BASE_PATH . '/includes/layout/header.php';
?>

<div class="container my-4">
  <div class="text-center mb-4">
    <i class="bi bi-check-circle-fill text-success display-3"></i>
    <h1 class="h3 mt-2">Pedido #<?= $id ?> confirmado</h1>
    <p class="text-secondary">Realizado em <?= e(format_datetime($pedido['data_pedido'])) ?></p>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-sm-4">
              <small class="text-secondary d-block">Status</small>
              <span class="badge bg-<?= status_badge($pedido['status']) ?>"><?= e(ucfirst($pedido['status'])) ?></span>
            </div>
            <div class="col-sm-4">
              <small class="text-secondary d-block">Pagamento</small>
              <?= e(payment_label($pedido['forma_pagamento'])) ?>
            </div>
            <div class="col-sm-4">
              <small class="text-secondary d-block">Cliente</small>
              <?= e($pedido['cliente_nome']) ?>
            </div>
          </div>

          <table class="table align-middle">
            <thead class="table-light">
              <tr>
                <th>Produto</th>
                <th class="text-center">Qtd</th>
                <th class="text-end">Preço unit.</th>
                <th class="text-end">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($itens as $i): ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <img src="<?= e($i['imagem_url'] ?: 'https://placehold.co/600x400?text=Sem+imagem') ?>" class="rounded gv-cart-thumb" alt="">
                      <span><?= e($i['produto_nome']) ?></span>
                    </div>
                  </td>
                  <td class="text-center"><?= (int) $i['quantidade'] ?></td>
                  <td class="text-end"><?= money($i['preco_unitario']) ?></td>
                  <td class="text-end"><?= money($i['subtotal']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="fw-bold fs-5">
                <td colspan="3" class="text-end">Total</td>
                <td class="text-end"><?= money($pedido['total']) ?></td>
              </tr>
            </tfoot>
          </table>

          <div class="d-flex gap-2 justify-content-end">
            <a href="<?= e(url('meus_pedidos.php')) ?>" class="btn btn-outline-dark">Meus pedidos</a>
            <a href="<?= e(url('index.php')) ?>" class="btn btn-dark">Continuar comprando</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require BASE_PATH . '/includes/layout/footer.php'; ?>
