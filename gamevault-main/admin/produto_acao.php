<?php
require_once dirname(__DIR__) . '/includes/init.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/produtos.php'));
}
verify_csrf();

$id      = to_int($_POST['id'] ?? 0);
$acao    = $_POST['acao'] ?? '';
$produto = $id > 0 ? produto_buscar($id, false) : null;

if (!$produto) {
    flash('warning', 'Produto não encontrado.');
    redirect(url('admin/produtos.php'));
}

switch ($acao) {
    case 'alternar':
        $novo = !(int) $produto['ativo'];
        produto_definir_ativo($id, $novo);
        flash('success', $novo ? 'Produto ativado.' : 'Produto desativado.');
        break;

    case 'excluir':
        $resultado = produto_excluir($id);
        if ($resultado === 'excluido') {
            flash('success', 'Produto excluído definitivamente.');
        } else {
            flash('info', 'O produto já possui vendas registradas, por isso foi apenas desativado.');
        }
        break;

    default:
        flash('danger', 'Ação inválida.');
}

redirect(url('admin/produtos.php'));
