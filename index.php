<?php
require_once __DIR__ . '/includes/auth.php';

exigir_login();

// Filtro inicial vem da URL (compartilhável), mas a lista completa é
// carregada para permitir filtragem instantânea no navegador, sem
// precisar recarregar a página a cada letra digitada.
$busca = trim($_GET['busca'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');

$produtos = listar_produtos();
$categorias = listar_categorias();

$tituloPagina = 'Início';
$paginaAtiva = 'inicio';
require __DIR__ . '/includes/header.php';
?>

<section class="secao-busca">
    <form method="get" class="form-busca" role="search" id="form-busca">
        <label class="campo-busca">
            <span aria-hidden="true">🔎</span>
            <input
                type="search"
                name="busca"
                id="busca-input"
                placeholder="Buscar produto ou marca..."
                value="<?= htmlspecialchars($busca) ?>"
                autocomplete="off"
            >
        </label>

        <select name="categoria" id="categoria-select" class="select-categoria">
            <option value="">Todas as categorias</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= $categoria === $cat ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="button" id="botao-limpar-busca" class="botao botao--secundario">✕ Limpar</button>
    </form>
</section>

<div id="resultados-produtos">
    <?php require __DIR__ . '/includes/resultados_produtos.php'; ?>
</div>

<a href="adicionar.php" class="botao-flutuante" aria-label="Adicionar novo preço">
    <span aria-hidden="true">➕</span>
</a>

<?php require __DIR__ . '/includes/footer.php'; ?>
