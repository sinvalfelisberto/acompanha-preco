<?php
require_once __DIR__ . '/includes/auth.php';

exigir_login();

$erros = [];
$sucesso = false;
$produtoSalvoId = null;

$produtoPreSelecionado = null;
if (!empty($_GET['produto_id'])) {
    $produtoPreSelecionado = obter_produto((int) $_GET['produto_id']);
}

$valores = [
    'produto_nome' => $produtoPreSelecionado['nome'] ?? '',
    'marca' => $produtoPreSelecionado['marca'] ?? '',
    'categoria' => $produtoPreSelecionado['categoria'] ?? '',
    'unidade' => $produtoPreSelecionado['unidade'] ?? 'un',
    'mercado_nome' => '',
    'endereco' => '',
    'preco' => '',
    'data_registro' => date('Y-m-d'),
    'observacao' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valores['produto_nome'] = trim($_POST['produto_nome'] ?? '');
    $valores['marca'] = trim($_POST['marca'] ?? '');
    $valores['categoria'] = trim($_POST['categoria'] ?? '');
    $valores['unidade'] = trim($_POST['unidade'] ?? 'un');
    $valores['mercado_nome'] = trim($_POST['mercado_nome'] ?? '');
    $valores['endereco'] = trim($_POST['endereco'] ?? '');
    $valores['preco'] = trim($_POST['preco'] ?? '');
    $valores['data_registro'] = trim($_POST['data_registro'] ?? date('Y-m-d'));
    $valores['observacao'] = trim($_POST['observacao'] ?? '');

    if ($valores['produto_nome'] === '') {
        $erros[] = 'Informe o nome do produto.';
    }

    if ($valores['mercado_nome'] === '') {
        $erros[] = 'Informe o mercado onde o preço foi consultado.';
    }

    $precoNormalizado = str_replace(',', '.', $valores['preco']);
    if ($valores['preco'] === '' || !is_numeric($precoNormalizado) || (float) $precoNormalizado <= 0) {
        $erros[] = 'Informe um preço válido, maior que zero.';
    }

    if (!$erros) {
        $produtoId = criar_ou_obter_produto(
            $valores['produto_nome'],
            $valores['marca'] ?: null,
            $valores['categoria'] ?: null,
            $valores['unidade'] ?: 'un'
        );

        $mercadoId = criar_ou_obter_mercado(
            $valores['mercado_nome'],
            $valores['endereco'] ?: null
        );

        inserir_preco(
            $produtoId,
            $mercadoId,
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
            'mercado_nome' => '',
            'endereco' => '',
            'preco' => '',
            'data_registro' => date('Y-m-d'),
            'observacao' => '',
        ];
    }
}

$produtosExistentes = listar_produtos();
$mercadosExistentes = listar_mercados();
$categorias = listar_categorias();

$tituloPagina = 'Adicionar preço';
$paginaAtiva = 'adicionar';
require __DIR__ . '/includes/header.php';
?>

<section class="cabecalho-secao">
    <h1>➕ Adicionar preço</h1>
    <p class="subtitulo">Cadastre um produto e o preço encontrado em um mercado para comparar depois.</p>
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
                <span>Categoria (opcional)</span>
                <input type="text" name="categoria" id="categoria" list="lista-categorias" placeholder="Ex: Mercearia"
                       value="<?= htmlspecialchars($valores['categoria']) ?>">
                <datalist id="lista-categorias">
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
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

    <fieldset class="grupo-campos">
        <legend>🏬 Mercado</legend>

        <label class="campo">
            <span>Nome do mercado</span>
            <input
                type="text"
                name="mercado_nome"
                id="mercado_nome"
                list="lista-mercados"
                placeholder="Ex: Supermercado Bom Preço"
                value="<?= htmlspecialchars($valores['mercado_nome']) ?>"
                required
            >
        </label>
        <datalist id="lista-mercados">
            <?php foreach ($mercadosExistentes as $m): ?>
                <option value="<?= htmlspecialchars($m['nome']) ?>"></option>
            <?php endforeach; ?>
        </datalist>

        <label class="campo">
            <span>Endereço (opcional)</span>
            <input type="text" name="endereco" id="endereco" placeholder="Ex: Av. Principal, 123"
                   value="<?= htmlspecialchars($valores['endereco']) ?>">
        </label>
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

    <button type="submit" class="botao botao--primario botao--bloco botao--grande">
        💾 Salvar preço
    </button>
</form>

<script id="dados-produtos" type="application/json"><?= json_encode($produtosExistentes, JSON_UNESCAPED_UNICODE) ?></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
