<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect(url('index.php'));
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $nome    = (string) ($_POST['nome'] ?? '');
    $email   = (string) ($_POST['email'] ?? '');
    $senha   = (string) ($_POST['senha'] ?? '');
    $confirm = (string) ($_POST['senha_confirmacao'] ?? '');

    if ($senha !== $confirm) {
        $erro = 'As senhas não conferem.';
    } else {
        try {
            register_user($nome, $email, $senha);
            attempt_login($email, $senha);
            $destino = $_SESSION['redirect_after_login'] ?? url('index.php');
            unset($_SESSION['redirect_after_login']);
            flash('success', 'Conta criada com sucesso! Boas compras.');
            redirect($destino);
        } catch (InvalidArgumentException $e) {
            $erro = $e->getMessage();
        }
    }
}

$pageTitle = 'Criar conta';
require BASE_PATH . '/includes/layout/header.php';
?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h1 class="h3 mb-4 text-center"><i class="bi bi-person-plus me-2"></i>Criar conta</h1>

          <?php if ($erro): ?>
            <div class="alert alert-danger"><?= e($erro) ?></div>
          <?php endif; ?>

          <form method="post" action="<?= e(url('cadastro.php')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
              <label for="nome" class="form-label">Nome completo</label>
              <input type="text" class="form-control" id="nome" name="nome" value="<?= e(old('nome')) ?>" required minlength="3" autofocus>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">E-mail</label>
              <input type="email" class="form-control" id="email" name="email" value="<?= e(old('email')) ?>" required>
            </div>
            <div class="mb-3">
              <label for="senha" class="form-label">Senha <span class="text-secondary small">(mínimo 6 caracteres)</span></label>
              <input type="password" class="form-control" id="senha" name="senha" required minlength="6">
            </div>
            <div class="mb-4">
              <label for="senha_confirmacao" class="form-label">Confirmar senha</label>
              <input type="password" class="form-control" id="senha_confirmacao" name="senha_confirmacao" required minlength="6">
            </div>
            <button type="submit" class="btn btn-dark w-100">Cadastrar</button>
          </form>

          <p class="text-center small mt-4 mb-0">
            Já tem conta? <a href="<?= e(url('login.php')) ?>">Entrar</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require BASE_PATH . '/includes/layout/footer.php'; ?>
