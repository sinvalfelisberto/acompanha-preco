<?php
require_once __DIR__ . '/includes/functions.php';

$busca = trim($_GET['busca'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');

$produtos = listar_produtos($busca ?: null, $categoria ?: null);
$categorias = listar_categorias();

$tituloPagina = 'Início';
$paginaAtiva = 'inicio';
require __DIR__ . '/includes/header.php';
?>

<section class="secao-busca">
    <form method="get" class="form-busca" role="search">
        <label class="campo-busca">
            <span aria-hidden="true">🔎</span>
            <input
                type="search"
                name="busca"
                placeholder="Buscar produto ou marca..."
                value="<?= htmlspecialchars($busca) ?>"
                autocomplete="off"
            >
        </label>

        <select name="categoria" class="select-categoria" onchange="this.form.submit()">
            <option value="">Todas as categorias</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= $categoria === $cat ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="botao botao--secundario">Buscar</button>
    </form>
</section>

<section class="secao-resumo">
    <p class="texto-resumo">
        <?php if ($produtos): ?>
            <strong><?= count($produtos) ?></strong> produto<?= count($produtos) === 1 ? '' : 's' ?> encontrado<?= count($produtos) === 1 ? '' : 's' ?>
        <?php else: ?>
            Nenhum produto encontrado
        <?php endif; ?>
    </p>
</section>

<?php if (!$produtos): ?>
    <div class="estado-vazio">
        <span class="estado-vazio__icone" aria-hidden="true">🛍️</span>
        <p>Nenhum produto por aqui ainda.</p>
        <a href="adicionar.php" class="botao botao--primario">Cadastrar o primeiro preço</a>
    </div>
<?php else: ?>
    <section class="grade-produtos">
        <?php foreach ($produtos as $produto): ?>
            <a href="produto.php?id=<?= (int) $produto['id'] ?>" class="cartao-produto">
                <div class="cartao-produto__topo">
                    <h2 class="cartao-produto__nome"><?= htmlspecialchars($produto['nome']) ?></h2>
                    <?php if ($produto['marca']): ?>
                        <p class="cartao-produto__marca"><?= htmlspecialchars($produto['marca']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="cartao-produto__preco">
                    <span class="selo-menor-preco">🏆 Melhor preço</span>
                    <span class="valor"><?= formatar_preco((float) $produto['menor_preco']) ?></span>
                    <span class="unidade">/ <?= htmlspecialchars($produto['unidade']) ?></span>
                </div>

                <div class="cartao-produto__rodape">
                    <span>🏬 <?= htmlspecialchars($produto['mercado_menor_preco']) ?></span>
                    <span class="separador">·</span>
                    <span><?= (int) $produto['mercados_comparados'] ?> mercado<?= (int) $produto['mercados_comparados'] === 1 ? '' : 's' ?> comparado<?= (int) $produto['mercados_comparados'] === 1 ? '' : 's' ?></span>
                </div>

                <?php if ($produto['categoria']): ?>
                    <span class="etiqueta-categoria"><?= htmlspecialchars($produto['categoria']) ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<a href="adicionar.php" class="botao-flutuante" aria-label="Adicionar novo preço">
    <span aria-hidden="true">➕</span>
</a>

<?php require __DIR__ . '/includes/footer.php'; ?>
