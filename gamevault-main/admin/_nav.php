<?php
/** Sub-navegação do painel administrativo. Espera $adminTab definido. */
$adminTab = $adminTab ?? '';
$tabs = [
    'dashboard' => ['Visão geral', 'speedometer2', 'admin/index.php'],
    'produtos'  => ['Produtos',    'box-seam',     'admin/produtos.php'],
    'pedidos'   => ['Pedidos',     'receipt',      'admin/pedidos.php'],
];
?>
<div class="container mt-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0"><i class="bi bi-shield-lock me-2"></i>Painel administrativo</h1>
    <a href="<?= e(url('index.php')) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-shop me-1"></i>Ver loja</a>
  </div>
  <ul class="nav nav-tabs mb-4">
    <?php foreach ($tabs as $key => [$label, $icon, $href]): ?>
      <li class="nav-item">
        <a class="nav-link <?= $adminTab === $key ? 'active' : '' ?>" href="<?= e(url($href)) ?>">
          <i class="bi bi-<?= $icon ?> me-1"></i><?= $label ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
