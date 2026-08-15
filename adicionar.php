<?php
require_once __DIR__ . '/includes/auth.php';

exigir_login();

$erros = [];
$sucesso = false;
$produtoSalvoId = null;

$precoEditando = null;
if (!empty($_GET['preco_id'])) {
    $precoEditando = obter_preco((int) $_GET['preco_id']);
}

$produtoPreSelecionado = null;
if (!$precoEditando && !empty($_GET['produto_id'])) {
    $produtoPreSelecionado = obter_produto((int) $_GET['produto_id']);
}

$valores = [
    'produto_nome' => $produtoPreSelecionado['nome'] ?? '',
    'marca' => $produtoPreSelecionado['marca'] ?? '',
    'categoria' => $produtoPreSelecionado['categoria'] ?? '',
    'unidade' => $produtoPreSelecionado['unidade'] ?? 'un',
    'mercado_id' => $precoEditando['mercado_id'] ?? '',
    'preco' => $precoEditando ? number_format((float) $precoEditando['preco'], 2, ',', '') : '',
    'data_registro' => $precoEditando['data_registro'] ?? date('Y-m-d'),
    'observacao' => $precoEditando['observacao'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'editar_preco') {
    $precoId = (int) ($_POST['preco_id'] ?? 0);
    $precoEditando = obter_preco($precoId);

    $valores['mercado_id'] = trim($_POST['mercado_id'] ?? '');
    $valores['preco'] = trim($_POST['preco'] ?? '');
    $valores['data_registro'] = trim($_POST['data_registro'] ?? date('Y-m-d'));
    $valores['observacao'] = trim($_POST['observacao'] ?? '');

    if (!$precoEditando) {
        $erros[] = 'Preço não encontrado.';
    }

    $mercadoSelecionado = $valores['mercado_id'] !== '' ? obter_mercado((int) $valores['mercado_id']) : null;
    if (!$mercadoSelecionado) {
        $erros[] = 'Selecione um mercado.';
    }

    $precoNormalizado = str_replace(',', '.', $valores['preco']);
    if ($valores['preco'] === '' || !is_numeric($precoNormalizado) || (float) $precoNormalizado <= 0) {
        $erros[] = 'Informe um preço válido, maior que zero.';
    }

    if (!$erros) {
        try {
            atualizar_preco(
                $precoId,
                (int) $valores['mercado_id'],
                (float) $precoNormalizado,
                $valores['data_registro'],
                $valores['observacao'] ?: null
            );

            header('Location: produto.php?id=' . $precoEditando['produto_id'] . '&editado=1');
            exit;
        } catch (PDOException $e) {
            $erros[] = 'Já existe um preço igual registrado para esse mercado e essa data.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'criar_preco') {
    $valores['produto_nome'] = trim($_POST['produto_nome'] ?? '');
    $valores['marca'] = trim($_POST['marca'] ?? '');
    $valores['categoria'] = trim($_POST['categoria'] ?? '');
    $valores['unidade'] = trim($_POST['unidade'] ?? 'un');
    $valores['mercado_id'] = trim($_POST['mercado_id'] ?? '');
    $valores['preco'] = trim($_POST['preco'] ?? '');
    $valores['data_registro'] = trim($_POST['data_registro'] ?? date('Y-m-d'));
    $valores['observacao'] = trim($_POST['observacao'] ?? '');

    if ($valores['produto_nome'] === '') {
        $erros[] = 'Informe o nome do produto.';
    }

    if ($valores['categoria'] === '' || !in_array($valores['categoria'], listar_categorias(), true)) {
        $erros[] = 'Selecione uma categoria.';
    }

    $mercadoSelecionado = $valores['mercado_id'] !== '' ? obter_mercado((int) $valores['mercado_id']) : null;
    if (!$mercadoSelecionado) {
        $erros[] = 'Selecione um mercado.';
    }

    $precoNormalizado = str_replace(',', '.', $valores['preco']);
    if ($valores['preco'] === '' || !is_numeric($precoNormalizado) || (float) $precoNormalizado <= 0) {
        $erros[] = 'Informe um preço válido, maior que zero.';
    }

    if (!$erros) {
        $produtoId = criar_ou_obter_produto(
            $valores['produto_nome'],
            $valores['marca'] ?: null,
            $valores['categoria'],
            $valores['unidade'] ?: 'un'
        );

        inserir_preco(
            $produtoId,
            (int) $valores['mercado_id'],
            (float) $precoNormalizado,
            $valores['data_registro'],
            $valores['observacao'] ?: null
        );

        $sucesso = true;
        $produtoSalvoId = $produtoId;

        $valores = [
            'produto_nome' => '',
            'marca' => '',
            'categoria' => '',
            'unidade' => 'un',
            'mercado_id' => '',
            'preco' => '',
            'data_registro' => date('Y-m-d'),
            'observacao' => '',
        ];
    }
}

$produtosExistentes = listar_produtos();
$mercadosExistentes = listar_mercados();
$categorias = listar_categorias();

$tituloPagina = $precoEditando ? 'Editar preço' : 'Adicionar preço';
$paginaAtiva = 'adicionar';
require __DIR__ . '/includes/header.php';
?>

<section class="cabecalho-secao">
    <h1><?= $precoEditando ? '✏️ Editar preço' : '➕ Adicionar preço' ?></h1>
    <p class="subtitulo">
        <?= $precoEditando
            ? 'Atualize o preço, o mercado, a data ou a observação deste registro.'
            : 'Cadastre um produto e o preço encontrado em um mercado para comparar depois.' ?>
    </p>
</section>

<?php if ($sucesso): ?>
    <div class="alerta alerta--sucesso" role="status">
        <span aria-hidden="true">✅</span>
        <div>
            <strong>Preço salvo com sucesso!</strong>
            <p>Obrigada por ajudar a manter os preços atualizados.</p>
        </div>
        <?php if ($produtoSalvoId): ?>
            <a href="produto.php?id=<?= (int) $produtoSalvoId ?>" class="botao botao--pequeno">Ver comparação</a>
        <?php endif; ?>
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

<form method="post" class="form-cadastro" id="form-cadastro" novalidate>
    <input type="hidden" name="acao" value="<?= $precoEditando ? 'editar_preco' : 'criar_preco' ?>">
    <?php if ($precoEditando): ?>
        <input type="hidden" name="preco_id" value="<?= (int) $precoEditando['id'] ?>">
    <?php endif; ?>

    <?php if ($precoEditando): ?>
        <fieldset class="grupo-campos">
            <legend>🛒 Produto</legend>
            <p class="dica-campo">Editando preço de <strong><?= htmlspecialchars($precoEditando['produto_nome']) ?></strong>. Para alterar o produto, exclua este preço e cadastre um novo.</p>
        </fieldset>
    <?php else: ?>
        <fieldset class="grupo-campos">
            <legend>🛒 Produto</legend>

            <label class="campo">
                <span>Nome do produto</span>
                <input
                    type="text"
                    name="produto_nome"
                    id="produto_nome"
                    list="lista-produtos"
                    placeholder="Ex: Arroz branco 5kg"
                    value="<?= htmlspecialchars($valores['produto_nome']) ?>"
                    required
                >
            </label>
            <datalist id="lista-produtos">
                <?php foreach ($produtosExistentes as $p): ?>
                    <option value="<?= htmlspecialchars($p['nome']) ?>"></option>
                <?php endforeach; ?>
            </datalist>
            <p class="dica-campo">Se o produto já existir, escolha da lista para manter tudo organizado.</p>

            <div class="campo-linha">
                <label class="campo">
                    <span>Marca (opcional)</span>
                    <input type="text" name="marca" id="marca" placeholder="Ex: Tio João"
                           value="<?= htmlspecialchars($valores['marca']) ?>">
                </label>

                <label class="campo">
                    <span>Categoria</span>
                    <select name="categoria" id="categoria" required <?= !$categorias ? 'disabled' : '' ?>>
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $valores['categoria'] === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$categorias): ?>
                        <p class="dica-campo">Nenhuma categoria cadastrada ainda. <a href="categorias.php">Cadastre uma categoria primeiro</a>.</p>
                    <?php else: ?>
                        <p class="dica-campo">Não encontrou a categoria? <a href="categorias.php">Cadastre uma nova</a>.</p>
                    <?php endif; ?>
                </label>
            </div>

            <label class="campo">
                <span>Unidade</span>
                <select name="unidade" id="unidade">
                    <?php foreach (['un' => 'Unidade (un)', 'kg' => 'Quilo (kg)', 'g' => 'Grama (g)', 'L' => 'Litro (L)', 'ml' => 'Mililitro (ml)', 'dz' => 'Dúzia (dz)', 'pct' => 'Pacote (pct)'] as $valor => $rotulo): ?>
                        <option value="<?= $valor ?>" <?= $valores['unidade'] === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </fieldset>
    <?php endif; ?>

    <fieldset class="grupo-campos">
        <legend>🏬 Mercado</legend>

        <label class="campo">
            <span>Mercado</span>
            <select name="mercado_id" id="mercado_id" required <?= !$mercadosExistentes ? 'disabled' : '' ?>>
                <option value="">Selecione um mercado</option>
                <?php foreach ($mercadosExistentes as $m): ?>
                    <option value="<?= (int) $m['id'] ?>" <?= (string) $valores['mercado_id'] === (string) $m['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <?php if (!$mercadosExistentes): ?>
            <p class="dica-campo">Nenhum mercado cadastrado ainda. <a href="mercados.php">Cadastre um mercado primeiro</a>.</p>
        <?php else: ?>
            <p class="dica-campo">Não encontrou o mercado? <a href="mercados.php">Cadastre um novo</a>.</p>
        <?php endif; ?>
    </fieldset>

    <fieldset class="grupo-campos">
        <legend>💰 Preço</legend>

        <div class="campo-linha">
            <label class="campo">
                <span>Preço encontrado</span>
                <div class="campo-preco">
                    <span aria-hidden="true">R$</span>
                    <input
                        type="text"
                        inputmode="decimal"
                        name="preco"
                        id="preco"
                        placeholder="0,00"
                        value="<?= htmlspecialchars($valores['preco']) ?>"
                        required
                    >
                </div>
            </label>

            <label class="campo">
                <span>Data da consulta</span>
                <input type="date" name="data_registro" id="data_registro"
                       value="<?= htmlspecialchars($valores['data_registro']) ?>" max="<?= date('Y-m-d') ?>">
            </label>
        </div>

        <label class="campo">
            <span>Observação (opcional)</span>
            <input type="text" name="observacao" id="observacao" placeholder="Ex: promoção, embalagem diferente..."
                   value="<?= htmlspecialchars($valores['observacao']) ?>">
        </label>
    </fieldset>

    <button type="submit" class="botao botao--primario botao--bloco botao--grande" <?= !$mercadosExistentes ? 'disabled' : '' ?>>
        <?= $precoEditando ? '💾 Salvar alterações' : '💾 Salvar preço' ?>
    </button>

    <?php if ($precoEditando): ?>
        <a href="produto.php?id=<?= (int) $precoEditando['produto_id'] ?>" class="botao botao--secundario botao--bloco">Cancelar edição</a>
    <?php endif; ?>
</form>

<script id="dados-produtos" type="application/json"><?= json_encode($produtosExistentes, JSON_UNESCAPED_UNICODE) ?></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
