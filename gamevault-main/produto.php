<?php
require_once __DIR__ . '/includes/init.php';

$id      = to_int($_GET['id'] ?? 0);
$produto = $id > 0 ? produto_buscar($id, true) : null;

if (!$produto) {
    http_response_code(404);
    flash('warning', 'Produto não encontrado.');
    redirect(url('index.php'));
}

$estoque    = (int) $produto['estoque'];
$semEstoque = $estoque <= 0;
$noCarrinho = cart()[$id] ?? 0;


$relacionados = array_values(array_filter(
    produtos_listar((int) $produto['categoria_id'], null, true),
    fn($p) => (int) $p['id'] !== $id
));
$relacionados = array_slice($relacionados, 0, 4);

$pageTitle = $produto['nome'];
require BASE_PATH . '/includes/layout/header.php';
?>

<div class="container my-4">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb small">
      <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Início</a></li>
      <li class="breadcrumb-item">
        <a href="<?= e(url('index.php?categoria=' . urlencode($produto['categoria_slug']))) ?>"><?= e($produto['categoria']) ?></a>
      </li>
      <li class="breadcrumb-item active" aria-current="page"><?= e($produto['nome']) ?></li>
    </ol>
  </nav>

  <div class="row g-4">
    <div class="col-md-6">
      <div class="gv-product-img border rounded bg-white p-2">
        <img src="<?= e($produto['imagem_url'] ?: 'https://placehold.co/600x400?text=Sem+imagem') ?>"
             class="img-fluid rounded w-100" alt="<?= e($produto['nome']) ?>">
      </div>
    </div>

    <div class="col-md-6">
      <span class="badge bg-secondary mb-2"><?= e($produto['categoria']) ?></span>
      <h1 class="h2"><?= e($produto['nome']) ?></h1>
      <p class="text-secondary small mb-3">Código do produto: #<?= str_pad((string) $id, 5, '0', STR_PAD_LEFT) ?></p>

      <p class="display-6 fw-bold text-dark mb-3"><?= money($produto['preco']) ?></p>

      <?php if ($semEstoque): ?>
        <p class="mb-3"><span class="badge bg-danger fs-6"><i class="bi bi-x-circle me-1"></i>Esgotado</span></p>
      <?php elseif ($estoque <= 5): ?>
        <p class="mb-3"><span class="badge bg-warning text-dark fs-6"><i class="bi bi-exclamation-triangle me-1"></i>Últimas <?= $estoque ?> unidade(s)!</span></p>
      <?php else: ?>
        <p class="mb-3"><span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i>Em estoque (<?= $estoque ?> disponíveis)</span></p>
      <?php endif; ?>

      <p class="lead fs-6"><?= nl2br(e($produto['descricao'] ?? '')) ?></p>

      <form action="<?= e(url('carrinho.php')) ?>" method="post" class="mt-4">
        <?= csrf_field() ?>
        <input type="hidden" name="acao" value="adicionar">
        <input type="hidden" name="produto_id" value="<?= $id ?>">

        <div class="row g-2 align-items-end">
          <div class="col-auto">
            <label for="quantidade" class="form-label small mb-1">Quantidade</label>
            <div class="input-group gv-qty-group" style="width: 140px">
              <button class="btn btn-outline-secondary" type="button" data-qty="dec" aria-label="Diminuir">−</button>
              <input type="number" class="form-control text-center" id="quantidade" name="quantidade"
                     value="1" min="1" max="<?= max(1, $estoque - $noCarrinho) ?>" <?= $semEstoque ? 'disabled' : '' ?>>
              <button class="btn btn-outline-secondary" type="button" data-qty="inc" aria-label="Aumentar">+</button>
            </div>
          </div>
          <div class="col">
            <button type="submit" class="btn btn-dark btn-lg w-100" <?= $semEstoque ? 'disabled' : '' ?>>
              <i class="bi bi-cart-plus me-1"></i> Adicionar ao carrinho
            </button>
          </div>
        </div>
        <?php if ($noCarrinho > 0): ?>
          <p class="small text-secondary mt-2 mb-0">
            Você já tem <?= $noCarrinho ?> unidade(s) deste item no <a href="<?= e(url('carrinho.php')) ?>">carrinho</a>.
          </p>
        <?php endif; ?>
      </form>

      <ul class="list-unstyled small text-secondary mt-4">
        <li><i class="bi bi-truck me-2"></i>Entrega simulada: imediata</li>
        <li><i class="bi bi-shield-check me-2"></i>Pagamento simulado via PIX, Boleto ou Cartão</li>
      </ul>
    </div>
  </div>

  <?php if ($relacionados): ?>
    <hr class="my-5">
    <h2 class="h5 mb-3">Você também pode gostar</h2>
    <div class="row row-cols-2 row-cols-md-4 g-3">
      <?php foreach ($relacionados as $r): ?>
        <div class="col">
          <a href="<?= e(url('produto.php?id=' . (int) $r['id'])) ?>" class="card h-100 text-decoration-none gv-card shadow-sm">
            <img src="<?= e($r['imagem_url'] ?: 'https://placehold.co/600x400?text=Sem+imagem') ?>" class="card-img-top" alt="<?= e($r['nome']) ?>" loading="lazy">
            <div class="card-body">
              <p class="small mb-1 text-dark"><?= e($r['nome']) ?></p>
              <strong class="text-dark"><?= money($r['preco']) ?></strong>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require BASE_PATH . '/includes/layout/footer.php'; ?>
