<?php
require_once __DIR__ . '/includes/init.php';

$slug  = trim((string) ($_GET['categoria'] ?? ''));
$busca = trim((string) ($_GET['busca'] ?? ''));

$categorias     = categorias_todas();
$categoriaAtual = $slug !== '' ? categoria_buscar_por_slug($slug) : null;
$produtos       = produtos_listar($categoriaAtual['id'] ?? null, $busca !== '' ? $busca : null, true);

$pageTitle = $categoriaAtual ? $categoriaAtual['nome'] : ($busca !== '' ? "Busca: {$busca}" : 'Vitrine');
require BASE_PATH . '/includes/layout/header.php';
?>

<?php if (!$categoriaAtual && $busca === ''): ?>
<section class="gv-hero text-white py-5 mb-4">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <h1 class="display-5 fw-bold">Seu próximo jogo começa aqui.</h1>
        <p class="lead mb-4">Consoles, jogos, acessórios e peças para PC gamer com entrega simulada em segundos.</p>
        <a href="#vitrine" class="btn btn-info btn-lg text-dark fw-semibold">Ver produtos</a>
      </div>
      <div class="col-lg-5 text-center d-none d-lg-block">
        <i class="bi bi-joystick gv-hero-icon"></i>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<div class="container" id="vitrine">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="h4 mb-0">
      <?php if ($busca !== ''): ?>
        Resultados para "<?= e($busca) ?>"
      <?php elseif ($categoriaAtual): ?>
        <?= e($categoriaAtual['nome']) ?>
      <?php else: ?>
        Todos os produtos
      <?php endif; ?>
      <span class="text-secondary fs-6">(<?= count($produtos) ?>)</span>
    </h2>

    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-sm <?= !$categoriaAtual ? 'btn-dark' : 'btn-outline-dark' ?>"
         href="<?= e(url('index.php' . ($busca !== '' ? '?busca=' . urlencode($busca) : ''))) ?>">Todas</a>
      <?php foreach ($categorias as $cat): ?>
        <a class="btn btn-sm <?= ($categoriaAtual['id'] ?? 0) == $cat['id'] ? 'btn-dark' : 'btn-outline-dark' ?>"
           href="<?= e(url('index.php?categoria=' . urlencode($cat['slug']) . ($busca !== '' ? '&busca=' . urlencode($busca) : ''))) ?>">
          <?= e($cat['nome']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if (empty($produtos)): ?>
    <div class="alert alert-light border text-center py-5">
      <i class="bi bi-emoji-frown fs-1 d-block mb-2"></i>
      Nenhum produto encontrado. <a href="<?= e(url('index.php')) ?>">Limpar filtros</a>
    </div>
  <?php else: ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-4 mb-5">
      <?php foreach ($produtos as $p): ?>
        <?php $semEstoque = (int) $p['estoque'] <= 0; ?>
        <div class="col">
          <div class="card h-100 gv-card shadow-sm">
            <a href="<?= e(url('produto.php?id=' . (int) $p['id'])) ?>" class="gv-card-img-wrap">
              <img src="<?= e($p['imagem_url'] ?: 'https://placehold.co/600x400?text=Sem+imagem') ?>"
                   class="card-img-top" alt="<?= e($p['nome']) ?>" loading="lazy">
            </a>
            <div class="card-body d-flex flex-column">
              <span class="badge bg-secondary-subtle text-secondary-emphasis align-self-start mb-2"><?= e($p['categoria']) ?></span>
              <h3 class="h6 card-title">
                <a href="<?= e(url('produto.php?id=' . (int) $p['id'])) ?>" class="text-decoration-none text-dark stretched-link-none">
                  <?= e($p['nome']) ?>
                </a>
              </h3>
              <div class="mt-auto">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fs-5 fw-bold text-dark"><?= money($p['preco']) ?></span>
                  <?php if ($semEstoque): ?>
                    <span class="badge bg-danger">Esgotado</span>
                  <?php elseif ((int) $p['estoque'] <= 5): ?>
                    <span class="badge bg-warning text-dark">Últimas <?= (int) $p['estoque'] ?></span>
                  <?php else: ?>
                    <span class="badge bg-success">Em estoque</span>
                  <?php endif; ?>
                </div>
                <form action="<?= e(url('carrinho.php')) ?>" method="post" class="d-grid">
                  <?= csrf_field() ?>
                  <input type="hidden" name="acao" value="adicionar">
                  <input type="hidden" name="produto_id" value="<?= (int) $p['id'] ?>">
                  <input type="hidden" name="quantidade" value="1">
                  <button type="submit" class="btn btn-dark btn-sm" <?= $semEstoque ? 'disabled' : '' ?>>
                    <i class="bi bi-cart-plus me-1"></i> Adicionar ao carrinho
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require BASE_PATH . '/includes/layout/footer.php'; ?>
