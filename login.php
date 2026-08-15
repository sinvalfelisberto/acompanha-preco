<?php
require_once __DIR__ . '/includes/auth.php';

if (usuario_logado()) {
    header('Location: index.php');
    exit;
}

$redirecionar = $_GET['redirecionar'] ?? '/index.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
<meta name="theme-color" content="#7c3aed">
<title>Entrar · Compara Preços</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🛒</text></svg>">
</head>
<body class="corpo-login">
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

<button type="button" id="botao-tema" class="botao-tema-login" aria-label="Alternar tema claro e escuro">
    <span id="botao-tema-icone" aria-hidden="true">🌙</span>
</button>

<main class="tela-login">
    <div class="tela-login__cartao">
        <span class="tela-login__icone" aria-hidden="true">🛒</span>
        <h1>Compara Preços</h1>
        <p>Compare preços de produtos entre mercados, descubra onde está mais barato e economize nas suas compras.</p>

        <?php if (google_oauth_configurado()): ?>
            <a href="auth/iniciar.php?redirecionar=<?= urlencode($redirecionar) ?>" class="botao botao--primario botao--bloco botao--grande">
                <span aria-hidden="true">🔐</span> Entrar com Google
            </a>
            <p class="tela-login__aviso">Você precisa estar logado para acessar o app.</p>
        <?php else: ?>
            <div class="alerta alerta--erro" role="alert">
                <span aria-hidden="true">⚠️</span>
                <div>
                    <strong>Login com Google ainda não configurado.</strong>
                    <p>Defina GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET e GOOGLE_REDIRECT_URI no arquivo .env.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="assets/js/app.js"></script>
</body>
</html>
