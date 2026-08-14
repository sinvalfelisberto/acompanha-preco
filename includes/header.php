<?php
require_once __DIR__ . '/auth.php';

/** @var string $tituloPagina */
/** @var string $paginaAtiva */
$tituloPagina = $tituloPagina ?? 'Compara Preços';
$paginaAtiva = $paginaAtiva ?? '';
$usuario = obter_usuario_atual();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
<meta name="theme-color" content="#7c3aed">
<title><?= htmlspecialchars($tituloPagina) ?> · Compara Preços</title>
<link rel="stylesheet" href="assets/css/style.css">
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

        <nav class="nav-desktop" aria-label="Navegação principal">
            <a href="index.php" class="<?= $paginaAtiva === 'inicio' ? 'ativo' : '' ?>">Início</a>
            <a href="adicionar.php" class="<?= $paginaAtiva === 'adicionar' ? 'ativo' : '' ?>">Adicionar preço</a>
            <a href="mercados.php" class="<?= $paginaAtiva === 'mercados' ? 'ativo' : '' ?>">Mercados</a>
        </nav>

        <div class="topo__acoes">
            <button type="button" id="botao-tema" class="botao-tema" aria-label="Alternar tema claro e escuro">
                <span class="botao-tema__icone" aria-hidden="true">🌙</span>
            </button>

            <?php if ($usuario): ?>
                <div class="menu-usuario">
                    <?php if ($usuario['avatar']): ?>
                        <img src="<?= htmlspecialchars($usuario['avatar']) ?>" alt="" class="menu-usuario__avatar">
                    <?php else: ?>
                        <span class="menu-usuario__avatar menu-usuario__avatar--vazio" aria-hidden="true">👤</span>
                    <?php endif; ?>
                    <span class="menu-usuario__nome"><?= htmlspecialchars(explode(' ', $usuario['nome'])[0]) ?></span>
                    <a href="logout.php" class="botao botao--pequeno botao--secundario">Sair</a>
                </div>
            <?php else: ?>
                <a href="login.php" class="botao botao--pequeno botao--primario">
                    <span aria-hidden="true">🔐</span> Entrar
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="conteudo">
