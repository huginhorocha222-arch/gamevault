<?php
require_once dirname(__DIR__) . '/includes/init.php';

require_admin();

$id      = to_int($_GET['id'] ?? ($_POST['id'] ?? 0));
$produto = $id > 0 ? produto_buscar($id, false) : null;
$editando = $produto !== null;

if ($id > 0 && !$editando) {
    flash('warning', 'Produto não encontrado.');
    redirect(url('admin/produtos.php'));
}

$categorias = categorias_todas();
$erros      = [];

// Valores iniciais do formulário
$form = [
    'categoria_id' => (string) ($produto['categoria_id'] ?? ''),
    'nome'         => $produto['nome'] ?? '',
    'descricao'    => $produto['descricao'] ?? '',
    'preco'        => isset($produto['preco']) ? number_format((float) $produto['preco'], 2, ',', '') : '',
    'estoque'      => (string) ($produto['estoque'] ?? '0'),
    'imagem_url'   => $produto['imagem_url'] ?? '',
    'ativo'        => (string) ($produto['ativo'] ?? '1'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    foreach ($form as $k => $_) {
        $form[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $form['ativo'] = isset($_POST['ativo']) ? '1' : '0';

    $categoriaId = to_int($form['categoria_id']);
    $preco       = to_decimal($form['preco']);
    $estoque     = to_int($form['estoque']);

    if (mb_strlen($form['nome']) < 3) {
        $erros[] = 'O nome deve ter pelo menos 3 caracteres.';
    }
    if ($categoriaId <= 0 || !array_filter($categorias, fn($c) => (int) $c['id'] === $categoriaId)) {
        $erros[] = 'Selecione uma categoria válida.';
    }
    if ($preco <= 0) {
        $erros[] = 'Informe um preço maior que zero.';
    }
    if ($form['imagem_url'] !== '' && !filter_var($form['imagem_url'], FILTER_VALIDATE_URL)) {
        $erros[] = 'A URL da imagem é inválida.';
    }

    if (!$erros) {
        $dados = [
            'categoria_id' => $categoriaId,
            'nome'         => $form['nome'],
            'descricao'    => $form['descricao'] !== '' ? $form['descricao'] : null,
            'preco'        => $preco,
            'estoque'      => $estoque,
            'imagem_url'   => $form['imagem_url'] !== '' ? $form['imagem_url'] : null,
            'ativo'        => (int) $form['ativo'],
        ];

        if ($editando) {
            produto_atualizar($id, $dados);
            flash('success', 'Produto atualizado.');
        } else {
            produto_criar($dados);
            flash('success', 'Produto cadastrado.');
        }
        redirect(url('admin/produtos.php'));
    }
}

$pageTitle = $editando ? 'Admin · Editar produto' : 'Admin · Novo produto';
$adminTab  = 'produtos';
require BASE_PATH . '/includes/layout/header.php';
require __DIR__ . '/_nav.php';
?>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h2 class="h5 mb-4"><?= $editando ? 'Editar produto #' . $id : 'Novo produto' ?></h2>

          <?php if ($erros): ?>
            <div class="alert alert-danger">
              <ul class="mb-0"><?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
            </div>
          <?php endif; ?>

          <form method="post" action="<?= e(url('admin/produto_form.php' . ($editando ? '?id=' . $id : ''))) ?>">
            <?= csrf_field() ?>
            <?php if ($editando): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label" for="nome">Nome</label>
                <input type="text" class="form-control" id="nome" name="nome" value="<?= e($form['nome']) ?>" required minlength="3" maxlength="150">
              </div>
              <div class="col-md-4">
                <label class="form-label" for="categoria_id">Categoria</label>
                <select class="form-select" id="categoria_id" name="categoria_id" required>
                  <option value="">Selecione...</option>
                  <?php foreach ($categorias as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $form['categoria_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label" for="descricao">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="4"><?= e($form['descricao']) ?></textarea>
              </div>
              <div class="col-md-4">
                <label class="form-label" for="preco">Preço (R$)</label>
                <input type="text" class="form-control" id="preco" name="preco" value="<?= e($form['preco']) ?>" placeholder="0,00" required inputmode="decimal">
              </div>
              <div class="col-md-4">
                <label class="form-label" for="estoque">Estoque</label>
                <input type="number" class="form-control" id="estoque" name="estoque" value="<?= e($form['estoque']) ?>" min="0" required>
              </div>
              <div class="col-md-4 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                  <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" <?= $form['ativo'] === '1' ? 'checked' : '' ?>>
                  <label class="form-check-label" for="ativo">Produto ativo (visível na loja)</label>
                </div>
              </div>
              <div class="col-12">
                <label class="form-label" for="imagem_url">URL da imagem</label>
                <input type="url" class="form-control" id="imagem_url" name="imagem_url" value="<?= e($form['imagem_url']) ?>" placeholder="https://...">
                <div class="form-text">Dica: use https://placehold.co/600x400?text=Nome para uma imagem de teste.</div>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
              <a href="<?= e(url('admin/produtos.php')) ?>" class="btn btn-outline-secondary">Cancelar</a>
              <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg me-1"></i><?= $editando ? 'Salvar alterações' : 'Cadastrar' ?></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require BASE_PATH . '/includes/layout/footer.php'; ?>
