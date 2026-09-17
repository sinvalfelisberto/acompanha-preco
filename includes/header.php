<?php
require_once __DIR__ . '/auth.php';

/** @var string $tituloPagina */
/** @var string $paginaAtiva */
$tituloPagina = $tituloPagina ?? 'Compara Preços';
$paginaAtiva = $paginaAtiva ?? '';
$usuario = obter_usuario_atual();

$horaServidor = (int) date('G');
if ($horaServidor < 12) {
    $saudacao = 'Bom dia,';
} elseif ($horaServidor < 18) {
    $saudacao = 'Boa tarde,';
} else {
    $saudacao = 'Boa noite,';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
<meta name="theme-color" content="#7c3aed">
<title><?= htmlspecialchars($tituloPagina) ?> · Compara Preços</title>
<link rel="stylesheet" href="assets/css/style.css">
<?php if ($paginaAtiva === 'mercados'): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<?php endif; ?>
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🛒</text></svg>">
</head>
<body>
<script>
(function () {
    var temaSalvo = localStorage.getItem('tema');
    if (temaSalvo) {
        document.documentElement.setAttribute('data-tema', temaSalvo);
    } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.setAttribute('data-tema', 'escuro');
    }
})();
</script>

<header class="topo">
    <div class="topo__conteudo">
        <a href="index.php" class="marca">
            <span class="marca__icone" aria-hidden="true">🛒</span>
            <span class="marca__texto">Compara Preços</span>
        </a>

        <div class="topo__acoes">
            <button type="button" id="botao-menu" class="botao-menu" aria-label="Abrir menu" aria-expanded="false" aria-controls="menu-lateral">
                <span aria-hidden="true">☰</span>
            </button>
        </div>
    </div>
</header>

<div id="menu-overlay" class="menu-overlay"></div>

<aside id="menu-lateral" class="menu-lateral" aria-label="Menu" aria-hidden="true">
    <div class="menu-lateral__topo">
        <div class="menu-lateral__usuario">
            <?php if ($usuario['avatar']): ?>
                <img src="<?= htmlspecialchars($usuario['avatar']) ?>" alt="" class="menu-lateral__avatar">
            <?php else: ?>
                <span class="menu-lateral__avatar menu-lateral__avatar--vazio" aria-hidden="true">👤</span>
            <?php endif; ?>
            <div>
                <p class="menu-lateral__saudacao" id="saudacao-texto"><?= htmlspecialchars($saudacao) ?></p>
                <p class="menu-lateral__nome"><?= htmlspecialchars($usuario['nome']) ?></p>
            </div>
        </div>

        <button type="button" id="botao-fechar-menu" class="botao-fechar-menu" aria-label="Fechar menu">✕</button>
    </div>

    <button type="button" id="botao-tema" class="menu-lateral__tema" aria-label="Alternar tema claro e escuro">
        <span id="botao-tema-icone" aria-hidden="true">🌙</span>
        <span id="botao-tema-texto">Tema escuro</span>
    </button>

    <nav class="menu-lateral__nav" aria-label="Gerenciar">
        <a href="index.php" class="<?= $paginaAtiva === 'inicio' ? 'ativo' : '' ?>">
            <span aria-hidden="true">🏠</span> Início
        </a>
        <a href="analise.php" class="<?= $paginaAtiva === 'analise' ? 'ativo' : '' ?>">
            <span aria-hidden="true">📊</span> Compra do mês
        </a>
        <a href="adicionar.php" class="<?= $paginaAtiva === 'adicionar' ? 'ativo' : '' ?>">
            <span aria-hidden="true">🛒</span> Incluir produto
        </a>
        <a href="mercados.php" class="<?= $paginaAtiva === 'mercados' ? 'ativo' : '' ?>">
            <span aria-hidden="true">🏬</span> Incluir mercado
        </a>
        <a href="categorias.php" class="<?= $paginaAtiva === 'categorias' ? 'ativo' : '' ?>">
            <span aria-hidden="true">📂</span> Incluir categoria
        </a>
    </nav>

    <div class="menu-lateral__rodape">
        <a href="logout.php" class="botao botao--secundario botao--bloco">🚪 Sair</a>
    </div>
</aside>

<main class="conteudo">
