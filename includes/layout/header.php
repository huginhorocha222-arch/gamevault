<?php
/**
 * Cabeçalho padrão (navbar + mensagens flash).
 * Espera a variável opcional $pageTitle definida pela página.
 */
$pageTitle   = $pageTitle ?? 'GameVault';
$user        = current_user();
$cartQty     = cart_count();
$navCats     = categorias_todas();
$currentSlug = $_GET['categoria'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> · GameVault</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark gv-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= e(url('index.php')) ?>">
      <i class="bi bi-controller fs-4 text-info"></i> GameVault
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain"
            aria-controls="navMain" aria-expanded="false" aria-label="Alternar navegação">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="<?= e(url('index.php')) ?>">Início</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Categorias
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= e(url('index.php')) ?>">Todas</a></li>
            <li><hr class="dropdown-divider"></li>
            <?php foreach ($navCats as $cat): ?>
              <li>
                <a class="dropdown-item <?= $currentSlug === $cat['slug'] ? 'active' : '' ?>"
                   href="<?= e(url('index.php?categoria=' . urlencode($cat['slug']))) ?>"><?= e($cat['nome']) ?></a>
              </li>
            <?php endforeach; ?>
          </ul>
        </li>
      </ul>

      <form class="d-flex me-lg-3 my-2 my-lg-0" role="search" action="<?= e(url('index.php')) ?>" method="get">
        <div class="input-group input-group-sm">
          <input class="form-control" type="search" name="busca" placeholder="Buscar produtos..."
                 aria-label="Buscar" value="<?= e($_GET['busca'] ?? '') ?>">
          <button class="btn btn-info" type="submit" aria-label="Buscar"><i class="bi bi-search"></i></button>
        </div>
      </form>

      <ul class="navbar-nav align-items-lg-center gap-lg-2">
        <li class="nav-item">
          <a class="nav-link position-relative" href="<?= e(url('carrinho.php')) ?>">
            <i class="bi bi-cart3 fs-5"></i> Carrinho
            <?php if ($cartQty > 0): ?>
              <span class="badge rounded-pill bg-info text-dark ms-1" id="cart-badge"><?= $cartQty ?></span>
            <?php endif; ?>
          </a>
        </li>
        <?php if ($user): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-circle"></i> <?= e(explode(' ', $user['nome'])[0]) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?= e(url('meus_pedidos.php')) ?>"><i class="bi bi-bag-check me-2"></i>Meus pedidos</a></li>
              <?php if ($user['perfil'] === 'admin'): ?>
                <li><a class="dropdown-item" href="<?= e(url('admin/index.php')) ?>"><i class="bi bi-speedometer2 me-2"></i>Painel admin</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li>
                <form action="<?= e(url('logout.php')) ?>" method="post" class="px-3 py-1">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-link p-0 text-danger text-decoration-none">
                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                  </button>
                </form>
              </li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(url('login.php')) ?>">Entrar</a></li>
          <li class="nav-item">
            <a class="btn btn-outline-info btn-sm" href="<?= e(url('cadastro.php')) ?>">Criar conta</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="flex-grow-1">
  <div class="container mt-3" id="flash-container">
    <?php foreach (get_flashes() as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($f['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
      </div>
    <?php endforeach; ?>
  </div>
