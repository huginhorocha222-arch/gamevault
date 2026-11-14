<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect(url('index.php'));
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim((string) ($_POST['email'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');

    if ($email === '' || $senha === '') {
        $erro = 'Informe e-mail e senha.';
    } elseif (attempt_login($email, $senha)) {
        $destino = $_SESSION['redirect_after_login'] ?? url('index.php');
        unset($_SESSION['redirect_after_login']);
        flash('success', 'Bem-vindo(a) de volta, ' . explode(' ', current_user()['nome'])[0] . '!');
        redirect($destino);
    } else {
        $erro = 'E-mail ou senha inválidos.';
    }
}

$pageTitle = 'Entrar';
require BASE_PATH . '/includes/layout/header.php';
?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h1 class="h3 mb-4 text-center"><i class="bi bi-person-circle me-2"></i>Entrar</h1>

          <?php if ($erro): ?>
            <div class="alert alert-danger"><?= e($erro) ?></div>
          <?php endif; ?>

          <form method="post" action="<?= e(url('login.php')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
              <label for="email" class="form-label">E-mail</label>
              <input type="email" class="form-control" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus>
            </div>
            <div class="mb-4">
              <label for="senha" class="form-label">Senha</label>
              <input type="password" class="form-control" id="senha" name="senha" required>
            </div>
            <button type="submit" class="btn btn-dark w-100">Entrar</button>
          </form>

          <p class="text-center small mt-4 mb-0">
            Ainda não tem conta? <a href="<?= e(url('cadastro.php')) ?>">Cadastre-se</a>
          </p>
        </div>
      </div>

      <div class="alert alert-light border small mt-3">
        <strong>Contas de teste:</strong><br>
        Admin: admin@gamevault.com / Admin@123<br>
        Cliente: cliente@gamevault.com / Cliente@123
      </div>
    </div>
  </div>
</div>

<?php require BASE_PATH . '/includes/layout/footer.php'; ?>
