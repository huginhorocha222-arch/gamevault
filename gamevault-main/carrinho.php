<?php
require_once __DIR__ . '/includes/init.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $acao      = $_POST['acao'] ?? '';
    $produtoId = to_int($_POST['produto_id'] ?? 0);
    $qtd       = to_int($_POST['quantidade'] ?? 1);
    $voltar    = $_POST['voltar'] ?? url('carrinho.php');

    switch ($acao) {
        case 'adicionar':
            $erro = cart_add($produtoId, max(1, $qtd));
            if ($erro) {
                flash('danger', $erro);
            } else {
                flash('success', 'Produto adicionado ao carrinho.');
            }
            redirect(url('carrinho.php'));

        case 'atualizar':
            $erro = cart_update($produtoId, $qtd);
            if ($erro) {
                flash('warning', $erro);
            } else {
                flash('success', 'Quantidade atualizada.');
            }
            redirect(url('carrinho.php'));

        case 'remover':
            cart_remove($produtoId);
            flash('info', 'Item removido do carrinho.');
            redirect(url('carrinho.php'));

        case 'limpar':
            cart_clear();
            flash('info', 'Carrinho esvaziado.');
            redirect(url('carrinho.php'));

        default:
            flash('danger', 'Ação inválida.');
            redirect(url('carrinho.php'));
    }
}


$dados = cart_items();
$items = $dados['items'];
$total = $dados['total'];

$pageTitle = 'Carrinho';
require BASE_PATH . '/includes/layout/header.php';
?>

<div class="container my-4">
  <h1 class="h3 mb-4"><i class="bi bi-cart3 me-2"></i>Meu carrinho</h1>

  <?php if (empty($items)): ?>
    <div class="text-center py-5 bg-white border rounded">
      <i class="bi bi-cart-x display-4 text-secondary d-block mb-3"></i>
      <p class="lead">Seu carrinho está vazio.</p>
      <a href="<?= e(url('index.php')) ?>" class="btn btn-dark">Ver produtos</a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="table-responsive bg-white border rounded">
          <table class="table align-middle mb-0" id="cart-table">
            <thead class="table-light">
              <tr>
                <th colspan="2">Produto</th>
                <th class="text-end">Preço</th>
                <th class="text-center" style="width: 190px">Quantidade</th>
                <th class="text-end">Subtotal</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
                <tr data-price="<?= $item['preco'] ?>">
                  <td style="width: 90px">
                    <img src="<?= e($item['imagem_url'] ?: 'https://placehold.co/600x400?text=Sem+imagem') ?>"
                         class="rounded gv-cart-thumb" alt="<?= e($item['nome']) ?>">
                  </td>
                  <td>
                    <a href="<?= e(url('produto.php?id=' . $item['id'])) ?>" class="text-decoration-none text-dark fw-semibold">
                      <?= e($item['nome']) ?>
                    </a>
                    <div class="small text-secondary"><?= e($item['categoria']) ?> · <?= $item['estoque'] ?> em estoque</div>
                  </td>
                  <td class="text-end"><?= money($item['preco']) ?></td>
                  <td>
                    <form action="<?= e(url('carrinho.php')) ?>" method="post" class="d-flex gap-1 justify-content-center">
                      <?= csrf_field() ?>
                      <input type="hidden" name="acao" value="atualizar">
                      <input type="hidden" name="produto_id" value="<?= $item['id'] ?>">
                      <div class="input-group input-group-sm gv-qty-group" style="width: 120px">
                        <button class="btn btn-outline-secondary" type="button" data-qty="dec" aria-label="Diminuir">−</button>
                        <input type="number" class="form-control text-center cart-qty" name="quantidade"
                               value="<?= $item['quantidade'] ?>" min="1" max="<?= $item['estoque'] ?>" aria-label="Quantidade">
                        <button class="btn btn-outline-secondary" type="button" data-qty="inc" aria-label="Aumentar">+</button>
                      </div>
                      <button type="submit" class="btn btn-sm btn-outline-dark" title="Atualizar quantidade">
                        <i class="bi bi-arrow-repeat"></i>
                      </button>
                    </form>
                  </td>
                  <td class="text-end fw-semibold cart-subtotal"><?= money($item['subtotal']) ?></td>
                  <td class="text-end">
                    <form action="<?= e(url('carrinho.php')) ?>" method="post">
                      <?= csrf_field() ?>
                      <input type="hidden" name="acao" value="remover">
                      <input type="hidden" name="produto_id" value="<?= $item['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Remover item">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between mt-3">
          <a href="<?= e(url('index.php')) ?>" class="btn btn-outline-dark">
            <i class="bi bi-arrow-left me-1"></i> Continuar comprando
          </a>
          <form action="<?= e(url('carrinho.php')) ?>" method="post" data-confirm="Esvaziar todo o carrinho?">
            <?= csrf_field() ?>
            <input type="hidden" name="acao" value="limpar">
            <button type="submit" class="btn btn-link text-danger text-decoration-none">Esvaziar carrinho</button>
          </form>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <h2 class="h5 card-title mb-3">Resumo</h2>
            <div class="d-flex justify-content-between mb-2">
              <span>Itens</span><span><?= $dados['count'] ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>Subtotal</span><span id="cart-total-preview"><?= money($total) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-3">
              <span>Frete</span><span class="text-success">Grátis</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between fs-5 fw-bold mb-3">
              <span>Total</span><span><?= money($total) ?></span>
            </div>
            <a href="<?= e(url('checkout.php')) ?>" class="btn btn-dark w-100 btn-lg">
              Finalizar compra <i class="bi bi-arrow-right ms-1"></i>
            </a>
            <?php if (!is_logged_in()): ?>
              <p class="small text-secondary mt-2 mb-0 text-center">Você precisará entrar ou criar uma conta.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require BASE_PATH . '/includes/layout/footer.php'; ?>
