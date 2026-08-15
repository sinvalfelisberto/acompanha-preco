<?php
require_once __DIR__ . '/includes/auth.php';

exigir_login();

$erros = [];
$sucesso = false;
$editado = false;

$categoriaEditando = null;
if (!empty($_GET['editar'])) {
    $categoriaEditando = obter_categoria((int) $_GET['editar']);
}

$valores = [
    'nome' => $categoriaEditando['nome'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir_categoria') {
    excluir_categoria((int) ($_POST['categoria_id'] ?? 0));

    header('Location: categorias.php?excluido=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'criar_categoria') {
    $valores['nome'] = trim($_POST['nome'] ?? '');

    if ($valores['nome'] === '') {
        $erros[] = 'Informe o nome da categoria.';
    } elseif (categoria_existe($valores['nome'])) {
        $erros[] = 'Já existe uma categoria cadastrada com esse nome.';
    }

    if (!$erros) {
        inserir_categoria($valores['nome']);
        $sucesso = true;
        $valores = ['nome' => ''];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'editar_categoria') {
    $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
    $categoriaEditando = obter_categoria($categoriaId);

    $valores['nome'] = trim($_POST['nome'] ?? '');

    if (!$categoriaEditando) {
        $erros[] = 'Categoria não encontrada.';
    } elseif ($valores['nome'] === '') {
        $erros[] = 'Informe o nome da categoria.';
    } elseif (categoria_existe($valores['nome'], $categoriaId)) {
        $erros[] = 'Já existe outra categoria cadastrada com esse nome.';
    }

    if (!$erros) {
        atualizar_categoria($categoriaId, $valores['nome']);
        header('Location: categorias.php?editado=1');
        exit;
    }
}

$editado = !empty($_GET['editado']);

$categorias = listar_categorias_com_uso();

$tituloPagina = 'Categorias';
$paginaAtiva = 'categorias';
require __DIR__ . '/includes/header.php';
?>

<section class="cabecalho-secao">
    <h1>📂 Categorias</h1>
    <p class="subtitulo">Categorias usadas para organizar os produtos.</p>
</section>

<?php if ($sucesso): ?>
    <div class="alerta alerta--sucesso" role="status">
        <span aria-hidden="true">✅</span>
        <div>
            <strong>Categoria cadastrada com sucesso!</strong>
            <p>Agora já pode usá-la ao cadastrar um produto.</p>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['excluido'])): ?>
    <div class="alerta alerta--sucesso" role="status">
        <span aria-hidden="true">✅</span>
        <div>
            <strong>Categoria excluída com sucesso!</strong>
        </div>
    </div>
<?php endif; ?>

<?php if ($editado): ?>
    <div class="alerta alerta--sucesso" role="status">
        <span aria-hidden="true">✅</span>
        <div>
            <strong>Categoria atualizada com sucesso!</strong>
        </div>
    </div>
<?php endif; ?>

<?php if ($erros): ?>
    <div class="alerta alerta--erro" role="alert">
        <span aria-hidden="true">⚠️</span>
        <div>
            <strong>Corrija os campos abaixo:</strong>
            <ul>
                <?php foreach ($erros as $erro): ?>
                    <li><?= htmlspecialchars($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<form method="post" class="form-cadastro" id="form-cadastro">
        <input type="hidden" name="acao" value="<?= $categoriaEditando ? 'editar_categoria' : 'criar_categoria' ?>">
        <?php if ($categoriaEditando): ?>
            <input type="hidden" name="categoria_id" value="<?= (int) $categoriaEditando['id'] ?>">
        <?php endif; ?>
        <fieldset class="grupo-campos">
            <legend><?= $categoriaEditando ? '✏️ Editar categoria' : '📂 Nova categoria' ?></legend>

            <label class="campo">
                <span>Nome da categoria</span>
                <input
                    type="text"
                    name="nome"
                    id="nome-categoria"
                    placeholder="Ex: Mercearia"
                    value="<?= htmlspecialchars($valores['nome']) ?>"
                    required
                >
            </label>

            <button type="submit" class="botao botao--primario botao--bloco">
                <?= $categoriaEditando ? '💾 Salvar alterações' : '💾 Salvar categoria' ?>
            </button>

            <?php if ($categoriaEditando): ?>
                <a href="categorias.php" class="botao botao--secundario botao--bloco">Cancelar edição</a>
            <?php endif; ?>
        </fieldset>
    </form>

<?php if (!$categorias): ?>
    <div class="estado-vazio">
        <span class="estado-vazio__icone" aria-hidden="true">📂</span>
        <p>Nenhuma categoria cadastrada ainda.</p>
    </div>
<?php else: ?>
    <h2 class="titulo-secao">Categorias cadastradas</h2>
    <section class="grade-mercados">
        <?php foreach ($categorias as $categoria): ?>
            <article class="cartao-mercado">
                <h2><?= htmlspecialchars($categoria['nome']) ?></h2>
                <div class="cartao-mercado__rodape">
                    <span><?= (int) $categoria['total_produtos'] ?> produto<?= (int) $categoria['total_produtos'] === 1 ? '' : 's' ?> nessa categoria</span>
                </div>

                <div class="cartao-mercado__acoes">
                    <a href="categorias.php?editar=<?= (int) $categoria['id'] ?>#form-cadastro" class="botao-editar">✏️ Editar</a>

                    <?php
                        $totalProdutos = (int) $categoria['total_produtos'];
                        $avisoExclusao = $totalProdutos > 0
                            ? "Excluir \"{$categoria['nome']}\"? Os {$totalProdutos} produto(s) que usam essa categoria não serão apagados, apenas ela sai da lista de seleção."
                            : "Excluir \"{$categoria['nome']}\"? Essa ação não pode ser desfeita.";
                    ?>
                    <form method="post" class="form-excluir" data-confirm="<?= htmlspecialchars($avisoExclusao) ?>">
                        <input type="hidden" name="acao" value="excluir_categoria">
                        <input type="hidden" name="categoria_id" value="<?= (int) $categoria['id'] ?>">
                        <button type="submit" class="botao-excluir">🗑️ Excluir</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
