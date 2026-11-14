<?php
require_once __DIR__ . '/includes/init.php';

// Compra restrita a usuários autenticados
require_login();

$dados = cart_items();
if (empty($dados['items'])) {
    flash('warning', 'Seu carrinho está vazio.');
    redirect(url('carrinho.php'));
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $forma = (string) ($_POST['forma_pagamento'] ?? '');

    if (!in_array($forma, ['pix', 'boleto', 'cartao'], true)) {
        $erro = 'Selecione uma forma de pagamento.';
    } elseif ($forma === 'cartao') {
        // Simulação: apenas valida o preenchimento básico dos campos do cartão
        $numero = preg_replace('/\D/', '', (string) ($_POST['cartao_numero'] ?? ''));
        $titular = trim((string) ($_POST['cartao_nome'] ?? ''));
        $validade = trim((string) ($_POST['cartao_validade'] ?? ''));
        $cvv = preg_replace('/\D/', '', (string) ($_POST['cartao_cvv'] ?? ''));

        if (strlen($numero) < 13 || $titular === '' || !preg_match('#^\d{2}/\d{2}$#', $validade) || strlen($cvv) < 3) {
            $erro = 'Preencha os dados do cartão (simulação: qualquer número com 13 a 19 dígitos é aceito).';
        }
    }

    if ($erro === null) {
        try {
            $pedidoId = pedido_criar((int) current_user()['id'], $forma, cart());
            cart_clear(); // limpeza do carrinho na sessão
            flash('success', 'Pagamento confirmado! Pedido #' . $pedidoId . ' realizado com sucesso.');
            redirect(url('pedido.php?id=' . $pedidoId));
        } catch (RuntimeException $e) {
            $erro = $e->getMessage();
            $dados = cart_items(); // recarrega quantidades ajustadas
        }
    }
}

$pageTitle = 'Finalizar compra';
require BASE_PATH . '/includes/layout/header.php';
?>

<div class="container my-4">
  <h1 class="h3 mb-4"><i class="bi bi-credit-card me-2"></i>Finalizar compra</h1>

  <?php if ($erro): ?>
    <div class="alert alert-danger"><?= e($erro) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(url('checkout.php')) ?>" id="checkout-form">
    <?= csrf_field() ?>
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="card shadow-sm mb-4">
          <div class="card-body">
            <h2 class="h5 mb-3">1. Dados do cliente</h2>
            <p class="mb-1"><strong><?= e(current_user()['nome']) ?></strong></p>
            <p class="text-secondary mb-0"><?= e(current_user()['email']) ?></p>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-body">
            <h2 class="h5 mb-3">2. Forma de pagamento <span class="badge bg-secondary">simulação</span></h2>

            <?php $formaSel = old('forma_pagamento', 'pix'); ?>
            <div class="list-group mb-3" id="payment-options">
              <label class="list-group-item d-flex align-items-center gap-3">
                <input class="form-check-input flex-shrink-0" type="radio" name="forma_pagamento" value="pix" <?= $formaSel === 'pix' ? 'checked' : '' ?>>
                <span><i class="bi bi-qr-code me-2"></i><strong>PIX</strong><br><small class="text-secondary">Aprovação imediata.</small></span>
              </label>
              <label class="list-group-item d-flex align-items-center gap-3">
                <input class="form-check-input flex-shrink-0" type="radio" name="forma_pagamento" value="boleto" <?= $formaSel === 'boleto' ? 'checked' : '' ?>>
                <span><i class="bi bi-upc-scan me-2"></i><strong>Boleto bancário</strong><br><small class="text-secondary">Compensação simulada na hora.</small></span>
              </label>
              <label class="list-group-item d-flex align-items-center gap-3">
                <input class="form-check-input flex-shrink-0" type="radio" name="forma_pagamento" value="cartao" <?= $formaSel === 'cartao' ? 'checked' : '' ?>>
                <span><i class="bi bi-credit-card-2-front me-2"></i><strong>Cartão de crédito</strong><br><small class="text-secondary">Use um número fictício (ex.: 4111 1111 1111 1111).</small></span>
              </label>
            </div>

            <div id="card-fields" class="border rounded p-3 bg-light <?= $formaSel === 'cartao' ? '' : 'd-none' ?>">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label" for="cartao_numero">Número do cartão</label>
                  <input type="text" class="form-control" id="cartao_numero" name="cartao_numero" inputmode="numeric"
                         placeholder="0000 0000 0000 0000" value="<?= e(old('cartao_numero')) ?>" autocomplete="off">
                </div>
                <div class="col-12">
                  <label class="form-label" for="cartao_nome">Nome impresso</label>
                  <input type="text" class="form-control" id="cartao_nome" name="cartao_nome" value="<?= e(old('cartao_nome')) ?>" autocomplete="off">
                </div>
                <div class="col-6">
                  <label class="form-label" for="cartao_validade">Validade (MM/AA)</label>
                  <input type="text" class="form-control" id="cartao_validade" name="cartao_validade" placeholder="12/30" value="<?= e(old('cartao_validade')) ?>" autocomplete="off">
                </div>
                <div class="col-6">
                  <label class="form-label" for="cartao_cvv">CVV</label>
                  <input type="text" class="form-control" id="cartao_cvv" name="cartao_cvv" inputmode="numeric" placeholder="123" autocomplete="off">
                </div>
              </div>
              <p class="small text-secondary mt-2 mb-0">Nenhum dado de cartão é armazenado. Pagamento meramente ilustrativo.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card shadow-sm">
          <div class="card-body">
            <h2 class="h5 mb-3">3. Resumo do pedido</h2>
            <ul class="list-group list-group-flush mb-3">
              <?php foreach ($dados['items'] as $item): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <span><?= e($item['nome']) ?> <span class="text-secondary">× <?= $item['quantidade'] ?></span></span>
                  <span><?= money($item['subtotal']) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
            <div class="d-flex justify-content-between fs-5 fw-bold">
              <span>Total</span><span><?= money($dados['total']) ?></span>
            </div>
            <button type="submit" class="btn btn-success w-100 btn-lg mt-3">
              <i class="bi bi-lock-fill me-1"></i> Confirmar pagamento
            </button>
            <a href="<?= e(url('carrinho.php')) ?>" class="btn btn-link w-100 text-decoration-none">Voltar ao carrinho</a>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<?php require BASE_PATH . '/includes/layout/footer.php'; ?>
